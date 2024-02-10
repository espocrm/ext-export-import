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
use Espo\Core\DataManager;
use Espo\Core\Utils\Metadata;
use Espo\Core\InjectableFactory;
use Espo\Core\Utils\File\Manager as FileManager;

use Espo\Modules\ExportImport\Tools\Params;
use Espo\Modules\ExportImport\Tools\Processor\ProcessHook;
use Espo\Modules\ExportImport\Tools\Core\Entity as EntityTool;
use Espo\Modules\ExportImport\Tools\Import\Params as ImportParams;
use Espo\Modules\ExportImport\Tools\Import\EntityImport as EntityImportTool;
use Espo\Modules\ExportImport\Tools\Processor\Utils as ProcessorUtils;
use Espo\Modules\ExportImport\Tools\Customization\Params as CustomizationParams;
use Espo\Modules\ExportImport\Tools\Customization\Processors\Import as CustomizationImport;
use Espo\Modules\ExportImport\Tools\Config\Params as ConfigParams;
use Espo\Modules\ExportImport\Tools\Config\Processors\Import as ConfigImport;
use Espo\Modules\ExportImport\Tools\Processor\Utils as ToolUtils;
use Espo\Modules\ExportImport\Tools\Import\Result as EntityResult;
use Espo\Modules\ExportImport\Tools\Core\User as UserTool;

use Espo\Core\Exceptions\Error;

use Exception;

class Import implements Tool
{
    private array $warningList = [];

    public function __construct(
        private Log $log,
        private Defs $defs,
        private UserTool $userTool,
        private Metadata $metadata,
        private EntityTool $entityTool,
        private FileManager $fileManager,
        private DataManager $dataManager,
        private InjectableFactory $injectableFactory
    ) {}

    public function run(Params $params) : void
    {
        $format = $params->getFormat() ?? null;
        $importPath = $params->getPath() ?? null;

        if (!$format) {
            throw new Error('Option "format" is not defined.');
        }

        if (!$importPath) {
            throw new Error('Import path is not defined.');
        }

        if (!file_exists($importPath)) {
            throw new Error("Import path \"{$importPath}\" does not exist.");
        }

        $manifest = $this->injectableFactory->createWith(Manifest::class, [
            'params' => $params,
        ]);

        $this->importConfig($params, $manifest);

        $this->importCustomization($params, $manifest);

        $this->importData($params, $manifest);

        ProcessorUtils::writeList($params, $this->warningList, "Warnings:");

        ProcessorUtils::writeNewLine($params);
    }

    private function getEntityTypeList(Params $params): array
    {
        if ($params->getEntityTypeList()) {
            $list = $params->getEntityTypeList();
        }

        if (!isset($list)) {
            $list = $this->loadEntityTypeList($params);
        }

        $list = $this->filterEntityTypeList($params, $list);

        $list = ToolUtils::sortEntityTypeListByType($this->metadata, $list);

        return array_values($list);
    }

    private function loadEntityTypeList(Params $params): array
    {
        $entityFileList = $this->fileManager->getFileList(
            $params->getEntitiesPath(),
            false,
            '\.json$'
        );

        $availableEntityTypeList = $this->defs->getEntityTypeList();

        $entityTypeList = [];

        foreach ($entityFileList as $entityType) {
            $normalizedEntityType = preg_replace('/\.json$/i', '', $entityType);

            if (!in_array($normalizedEntityType, $availableEntityTypeList)) {

                continue;
            }

            $entityTypeList[] = $normalizedEntityType;
        }

        return $entityTypeList;
    }

    private function filterEntityTypeList(Params $params, array $list): array
    {
        $filteredList = [];

        $defs = $params->getExportImportDefs();

        foreach ($list as $entityType) {
            $isImportDisabled = $defs[$entityType]['importDisabled'] ?? false;

            if ($isImportDisabled) {
                continue;
            }

            if ($this->entityTool->isCategoryTreeAdditionalTable($entityType)) {
                continue;
            }

            $filteredList[] = $entityType;
        }

        return $filteredList;
    }

    private function importData(Params $params, Manifest $manifest): void
    {
        if ($params->getSkipData()) {
            return;
        }

        $entityTypeList = $this->getEntityTypeList($params);

        foreach ($entityTypeList as $entityType) {
            ProcessorUtils::writeLine($params, "{$entityType}...");

            try {
                $result = $this->importEntity($entityType, $params, $manifest);
            } catch (Exception $e) {
                ProcessorUtils::writeLine(
                    $params, "  Error: " . $e->getMessage()
                );

                $this->log->warning(
                    'ExportImport [' . $entityType . ']:' . $e->getMessage()
                );

                continue;
            }

            if ($result->hasReplaceIdMap()) {
                $params = $params->withReplaceIdMap(
                    $result->getReplaceIdMap()
                );
            }

            if ($result->getWarningList()) {
                $this->warningList = array_merge(
                    $this->warningList,
                    $result->getWarningList()
                );
            }
        }
    }

    private function importEntity(
        string $entityType,
        Params $params,
        Manifest $manifest
    ): EntityResult {
        $processHookClass = $this->getProcessHookClass($entityType);

        $importParams = ImportParams::create($entityType)
            ->withFormat($params->getFormat())
            ->withPath($params->getPath())
            ->withEntitiesPath($params->getEntitiesPath())
            ->withFilesPath($params->getFilesPath())
            ->withExportImportDefs($params->getExportImportDefs())
            ->withManifest($manifest)
            ->withImportType($params->getImportType())
            ->withCurrency($params->getCurrency())
            ->withUpdateCurrency($params->getUpdateCurrency())
            ->withProcessHookClass($processHookClass)
            ->withUserActive($params->getUserActive())
            ->withUserActiveIdList($this->userTool->getIdUserList(
                $params->getUserActiveList()
            ))
            ->withUpdateCreatedAt($params->getUpdateCreatedAt())
            ->withUserPassword($params->getUserPassword())
            ->withIsCustomEntity($this->entityTool->isCustom($entityType))
            ->withSkipCustomization($params->getSkipCustomization())
            ->withReplaceIdMap($params->getReplaceIdMap())
            ->withClearPassword($params->getClearPassword());

        $import = $this->injectableFactory->create(EntityImportTool::class);
        $import->setParams($importParams);

        $result = $import->run();

        ProcessorUtils::writeLine($params, $result->getMessage());

        return $result;
    }

    private function getProcessHookClass(string $entityType): ?ProcessHook
    {
        $processHookClassName = $this->metadata->get([
            'app', 'exportImport', 'importProcessHookClassNameMap', $entityType
        ]);

        if (!$processHookClassName || !class_exists($processHookClassName)) {
            return null;
        }

        return $this->injectableFactory->create($processHookClassName);
    }

    private function importCustomization(Params $params, Manifest $manifest): void
    {
        if ($params->getSkipCustomization()) {
            return;
        }

        $entityTypeList = $this->getEntityTypeList($params);

        $params = CustomizationParams::create()
            ->withPath($params->getPath())
            ->withManifest($manifest)
            ->withEntityTypeList($entityTypeList)
            ->withExportImportDefs($params->getExportImportDefs());

        $customizationImport = $this->injectableFactory->create(
            CustomizationImport::class
        );

        $customizationImport->process($params);

        $this->dataManager->rebuild();
    }

    private function importConfig(Params $params, Manifest $manifest): void
    {
        if ($params->getSkipConfig()) {
            return;
        }

        $entityTypeList = $this->getEntityTypeList($params);

        $params = ConfigParams::create()
            ->withPath($params->getPath())
            ->withManifest($manifest)
            ->withEntityTypeList($entityTypeList)
            ->withExportImportDefs($params->getExportImportDefs())
            ->withConfigIgnoreList($params->getConfigIgnoreList())
            ->withClearPassword($params->getClearPassword())
            ->withSkipInternalConfig($params->getSkipInternalConfig())
            ->withConfigHardList($params->getConfigHardList());

        $configImport = $this->injectableFactory->create(
            ConfigImport::class
        );

        $configImport->process($params);

        $this->dataManager->clearCache();
    }
}
