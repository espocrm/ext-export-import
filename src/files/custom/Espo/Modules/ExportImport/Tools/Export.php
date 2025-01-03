<?php
/************************************************************************
 * This file is part of Export Import extension for EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Modules\ExportImport\Tools;

use Espo\ORM\Defs;
use Espo\Core\Utils\Log;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\Core\Exceptions\Error;
use Espo\Core\InjectableFactory;
use Espo\Core\Select\SearchParams;
use Espo\Core\Utils\File\Manager as FileManager;

use Espo\Modules\ExportImport\Tools\Params;
use Espo\Modules\ExportImport\Tools\Processor\ProcessHook;
use Espo\Modules\ExportImport\Tools\Manifest\ManifestWriter;
use Espo\Modules\ExportImport\Tools\Export\Processor\Collection;
use Espo\Modules\ExportImport\Tools\Core\Entity as EntityTool;
use Espo\Modules\ExportImport\Tools\Export\Result as EntityResult;
use Espo\Modules\ExportImport\Tools\Export\Params as ExportParams;
use Espo\Modules\ExportImport\Tools\Config\Params as ConfigParams;
use Espo\Modules\ExportImport\Tools\Processor\Utils as ProcessorUtils;
use Espo\Modules\ExportImport\Tools\Export\EntityExport as EntityExportTool;
use Espo\Modules\ExportImport\Tools\Config\Processors\Export as ConfigExport;
use Espo\Modules\ExportImport\Tools\Customization\Params as CustomizationParams;
use Espo\Modules\ExportImport\Tools\Customization\Processors\Export as CustomizationExport;

use Exception;

class Export implements Tool
{
    private array $warningList = [];

    public function __construct(
        private Log $log,
        private Defs $defs,
        private Config $config,
        private Metadata $metadata,
        private EntityTool $entityTool,
        private FileManager $fileManager,
        private InjectableFactory $injectableFactory
    ) {}

    public function run(Params $params) : void
    {
        $format = $params->getFormat() ?? null;
        $exportPath = $params->getPath() ?? null;

        if (!$format) {
            throw new Error('Option "format" is not defined.');
        }

        if (!$exportPath) {
            throw new Error('Export path is not defined.');
        }

        $this->fileManager->removeInDir($exportPath);

        $this->exportConfig($params);

        $this->exportCustomization($params);

        $this->exportData($params);

        $this->createManifest($params);

        if ($params->getEntityTypeList() && !$params->getSkipRelatedEntities()) {
            $this->warningList[] = 'The data is exported along with the ' .
                'related entities. Use --skip-related-entities option to ' .
                'skip related entities.';
        }

        ProcessorUtils::writeList($params, $this->warningList, "Warnings:");

        ProcessorUtils::writeNewLine($params);

        ProcessorUtils::writeLine($params, "Files saved at \"" . $exportPath ."\".");
    }

    private function getEntityTypeList(Params $params): array
    {
        $list = $params->getEntityTypeList() ??
            $this->defs->getEntityTypeList();

        $list = array_merge($list, $this->getRelatedEntityTypeList($params));

        $list = array_unique($list);

        $list = $this->filterEntityTypeList($params, $list);

        return array_values($list);
    }

    private function getRelatedEntityTypeList(Params $params): array
    {
        if ($params->getSkipRelatedEntities()) {
            return [];
        }

        if (!$params->getEntityTypeList()) {
            return [];
        }

        $entityTypeList = $params->getEntityTypeList();

        return $this->entityTool->getRelatedEntitiesTypeList(
            $entityTypeList
        );
    }

    private function filterEntityTypeList(Params $params, array $list): array
    {
        $filteredList = [];

        $defs = $params->getExportImportDefs();
        $skipList = $params->getEntityTypeSkipList() ?? [];

        foreach ($list as $entityType) {
            $isExportDisabled = $defs[$entityType]['exportDisabled'] ?? false;

            if ($isExportDisabled) {
                continue;
            }

            if (in_array($entityType, $skipList)) {
                continue;
            }

            if ($this->entityTool->isCategoryTreeAdditionalTable($entityType)) {
                continue;
            }

            $filteredList[] = $entityType;
        }

        return $filteredList;
    }

    private function exportData(Params $params): void
    {
        if ($params->getSkipData()) {
            return;
        }

        ProcessorUtils::writeNewLine($params);

        $entityTypeList = $this->getEntityTypeList($params);

        foreach ($entityTypeList as $entityType) {
            ProcessorUtils::writeLine($params, "{$entityType}...");

            try {
                $result = $this->exportEntity($params, $entityType);
            } catch (Exception $e) {
                ProcessorUtils::writeLine(
                    $params, "  Error: " . $e->getMessage()
                );

                $this->log->warning(
                    'ExportImport [' . $entityType . ']:' . $e->getMessage()
                );

                continue;
            }

            if ($result->getWarningList()) {
                $this->warningList = array_merge(
                    $this->warningList,
                    $result->getWarningList()
                );
            }
        }
    }

    private function exportEntity(Params $params, string $entityType): EntityResult
    {
        $format = $params->getFormat();
        $collectionClass = $this->getCollectionClass($entityType);
        $processHookClass = $this->getProcessHookClass($entityType);

        $fileExtension = $this->metadata->get([
            'app', 'exportImport', 'formatDefs', $format, 'fileExtension'
        ]);

        $searchParams = $this->getSearchParams($params, $entityType);

        $exportParams = ExportParams::create($entityType)
            ->withFormat($format)
            ->withAccessControl(false)
            ->withPath($params->getPath())
            ->withEntitiesPath($params->getEntitiesPath())
            ->withFilesPath($params->getFilesPath())
            ->withExportImportDefs($params->getExportImportDefs())
            ->withCollectionClass($collectionClass)
            ->withFileExtension($fileExtension)
            ->withProcessHookClass($processHookClass)
            ->withSearchParams($searchParams)
            ->withPrettyPrint($params->getPrettyPrint())
            ->withIsCustomEntity($this->entityTool->isCustom($entityType))
            ->withSkipCustomization($params->getSkipCustomization())
            ->withSkipPassword($params->getSkipPassword())
            ->withUserSkipList($params->getUserSkipList())
            ->withAllAttributes($params->getAllAttributes())
            ->withFromDate($params->getFromDate());

        $export = $this->injectableFactory->create(EntityExportTool::class);
        $export->setParams($exportParams);

        $result = $export->run();

        ProcessorUtils::writeLine($params, $result->getMessage());

        return $result;
    }

    private function getCollectionClass(string $entityType): ?Collection
    {
        $collectionClassName = $this->metadata->get([
            'app', 'exportImport', 'exportCollectionClassNameMap', $entityType
        ]);

        if (!$collectionClassName || !class_exists($collectionClassName)) {
            return null;
        }

        return $this->injectableFactory->create($collectionClassName);
    }

    private function createManifest(Params $params): bool
    {
        $manifestWriter = $this->injectableFactory->createWith(ManifestWriter::class, [
            'params' => $params,
        ]);

        return $manifestWriter->save();
    }

    private function getProcessHookClass(string $entityType): ?ProcessHook
    {
        $processHookClassName = $this->metadata->get([
            'app', 'exportImport', 'exportProcessHookClassNameMap', $entityType
        ]);

        if (!$processHookClassName || !class_exists($processHookClassName)) {
            return null;
        }

        return $this->injectableFactory->create($processHookClassName);
    }

    private function exportCustomization(Params $params): void
    {
        if ($params->getSkipCustomization()) {
            return;
        }

        ProcessorUtils::write($params, "Customization...");

        $entityTypeList = $this->getEntityTypeList($params);
        $isListSpecified = $params->getEntityTypeList() ? true : false;

        if ($params->getAllCustomization()) {
            $isListSpecified = false;
        }

        $customizationParams = CustomizationParams::create()
            ->withPath($params->getPath())
            ->withEntityTypeList($entityTypeList)
            ->withIsEntityTypeListSpecified($isListSpecified)
            ->withExportImportDefs($params->getExportImportDefs());

        $customizationExport = $this->injectableFactory->create(
            CustomizationExport::class
        );

        $customizationExport->process($customizationParams);

        ProcessorUtils::writeLine($params, " done");
    }

    private function exportConfig(Params $params): void
    {
        if ($params->getSkipConfig()) {
            return;
        }

        ProcessorUtils::write($params, "Configuration...");

        $entityTypeList = $this->getEntityTypeList($params);

        $configParams = ConfigParams::create()
            ->withPath($params->getPath())
            ->withEntityTypeList($entityTypeList)
            ->withExportImportDefs($params->getExportImportDefs())
            ->withConfigIgnoreList($params->getConfigIgnoreList())
            ->withSkipPassword($params->getSkipPassword())
            ->withSkipInternalConfig($params->getSkipInternalConfig())
            ->withConfigHardList($params->getConfigHardList());

        $configExport = $this->injectableFactory->create(
            ConfigExport::class
        );

        $configExport->process($configParams);

        ProcessorUtils::writeLine($params, " done");
    }

    private function getSearchParams(Params $params, string $entityType): ?SearchParams
    {
        $skipList = $params->getExportImportDefs()[$entityType]['skipRecordList'] ?? null;

        if (!$skipList || count($skipList) == 0) {
            return null;
        }

        $where = [];

        foreach ($skipList as $fieldName => $list) {
            if (count($list) == 0) {

                continue;
            }

            $where[] = [
                'type' => 'or',
                'value' => [
                    [
                        'type' => 'isNull',
                        'attribute' => $fieldName,
                    ],
                    [
                        'type' => 'notIn',
                        'attribute' => $fieldName,
                        'value' => $list,
                    ],
                ],
            ];
        }

        return SearchParams::fromRaw([
            'where' => $where
        ]);
    }
}
