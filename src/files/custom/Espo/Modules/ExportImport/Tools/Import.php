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
use Espo\Modules\ExportImport\Tools\Import\Result as EntityResult;
use Espo\Modules\ExportImport\Tools\Core\User as UserTool;
use Espo\Modules\ExportImport\Tools\Import\EntityType as EntityTypeHelper;

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
        private EntityTypeHelper $entityTypeHelper,
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

        if (!$params->isConfirmed()) {
            ProcessorUtils::writeLine(
                $params,
                "We recommend making a backup of your EspoCRM instance " .
                "before running the import."
            );

            sleep(1);

            ProcessorUtils::writeLine($params, "Do you want to continue? [y/n]");

            if (!ProcessorUtils::isPromptConfirmed($params)) {
                return;
            }
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
        return $this->entityTypeHelper
            ->getNormalizedList($params);
    }

    private function importData(Params $params, Manifest $manifest): void
    {
        if ($params->getSkipData()) {
            return;
        }

        ProcessorUtils::writeNewLine($params);

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
            ->withUserActiveList($params->getUserActiveList())
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
        $isListSpecified = $params->getEntityTypeList() ? true : false;

        if ($params->getAllCustomization()) {
            $isListSpecified = false;
        }

        $customizationParams = CustomizationParams::create()
            ->withPath($params->getPath())
            ->withManifest($manifest)
            ->withEntityTypeList($entityTypeList)
            ->withIsEntityTypeListSpecified($isListSpecified)
            ->withExportImportDefs($params->getExportImportDefs());

        if (!file_exists($customizationParams->getCustomizationPath())) {
            return;
        }

        ProcessorUtils::write($params, "Customization...");

        $customizationImport = $this->injectableFactory->create(
            CustomizationImport::class
        );

        $customizationImport->process($customizationParams);

        $this->dataManager->rebuild();

        ProcessorUtils::writeLine($params, " done");
    }

    private function importConfig(Params $params, Manifest $manifest): void
    {
        if ($params->getSkipConfig()) {
            return;
        }

        $entityTypeList = $this->getEntityTypeList($params);

        $configParams = ConfigParams::create()
            ->withPath($params->getPath())
            ->withManifest($manifest)
            ->withEntityTypeList($entityTypeList)
            ->withExportImportDefs($params->getExportImportDefs())
            ->withConfigIgnoreList($params->getConfigIgnoreList())
            ->withClearPassword($params->getClearPassword())
            ->withSkipInternalConfig($params->getSkipInternalConfig())
            ->withConfigHardList($params->getConfigHardList());

        if (!file_exists($configParams->getConfigPath())) {
            return;
        }

        ProcessorUtils::write($params, "Configuration...");

        $configImport = $this->injectableFactory->create(
            ConfigImport::class
        );

        $configImport->process($configParams);

        $this->dataManager->clearCache();

        ProcessorUtils::writeLine($params, " done");
    }
}
