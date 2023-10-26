<?php
/************************************************************************
 * This file is part of Export Import extension for EspoCRM.
 *
 * Export Import extension for EspoCRM.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * Export Import extension is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Export Import extension is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 ************************************************************************/

namespace Espo\Modules\ExportImport\Tools;

use Espo\{
    Core\Di,
    ORM\Defs,
    Core\Exceptions\Error,
};

use Espo\Modules\ExportImport\Tools\{
    Params,
    Import\Params as ImportParams,
    Import\EntityImport as EntityImportTool,
    Processor\ProcessHook,
    Processor\Utils as ProcessorUtils,
    Customization\Params as CustomizationParams,
    Customization\Processors\Import as CustomizationImport,
    Config\Params as ConfigParams,
    Config\Processors\Import as ConfigImport,
    Processor\Utils as ToolUtils,
    Import\Result as EntityResult,
};

use Exception;

class Import implements

    Tool,
    Di\LogAware,
    Di\MetadataAware,
    Di\FileManagerAware,
    Di\DataManagerAware,
    Di\InjectableFactoryAware
{
    use Di\LogSetter;
    use Di\MetadataSetter;
    use Di\FileManagerSetter;
    use Di\DataManagerSetter;
    use Di\InjectableFactorySetter;

    private $defs;

    private array $warningList = [];

    public function __construct(Defs $defs)
    {
        $this->defs = $defs;
    }

    public function run(Params $params) : void
    {
        $format = $params->getFormat() ?? null;
        $importPath = $params->getImportPath() ?? null;

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

            if ($result->getWarningList()) {
                $this->warningList = array_merge(
                    $this->warningList,
                    $result->getWarningList()
                );
            }
        }

        ProcessorUtils::writeList($params, $this->warningList, "Warnings:");

        ProcessorUtils::writeNewLine($params);

        ProcessorUtils::writeLine($params, $result->getGlobalMessage());
    }

    private function getEntityTypeList(Params $params): array
    {
        if ($params->getEntityTypeList()) {
            $list = $params->getEntityTypeList();
        }

        if (!isset($list)) {
            $list = $this->loadEntityTypeList($params);
        }

        $list = ToolUtils::sortEntityTypeListByType($this->metadata, $list);

        $defs = $params->getExportImportDefs();

        foreach ($list as $key => $entityType) {
            $importDisabled = $defs[$entityType]['importDisabled'] ?? false;

            if ($importDisabled) {
                unset($list[$key]);
            }
        }

        return array_values($list);
    }

    private function loadEntityTypeList(Params $params): array
    {
        $entityFileList = $this->fileManager->getFileList(
            $params->getDataEntitiesPath(),
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

    private function importEntity(
        string $entityType,
        Params $params,
        Manifest $manifest
    ): EntityResult {
        $processHookClass = $this->getProcessHookClass($entityType);

        $isCustomEntity = $this->metadata->get([
            'scopes', $entityType, 'isCustom'
        ], false);

        $importParams = ImportParams::create($entityType)
            ->withFormat($params->getFormat())
            ->withPath($params->getImportPath())
            ->withEntitiesPath($params->getDataEntitiesPath())
            ->withFilesPath($params->getDataFilesPath())
            ->withExportImportDefs($params->getExportImportDefs())
            ->withManifest($manifest)
            ->withImportType($params->getImportType())
            ->withCurrency($params->getCurrency())
            ->withUpdateCurrency($params->getUpdateCurrency())
            ->withProcessHookClass($processHookClass)
            ->withUserActive($params->getUserActive())
            ->withUpdateCreatedAt($params->getUpdateCreatedAt())
            ->withUserPassword($params->getUserPassword())
            ->withIsCustomEntity($isCustomEntity)
            ->withCustomization($params->getCustomization());

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
        if (!$params->getCustomization()) {

            return;
        }

        $entityTypeList = $this->getEntityTypeList($params);

        $params = CustomizationParams::create()
            ->withPath($params->getImportPath())
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
        if (!$params->getConfig()) {

            return;
        }

        $entityTypeList = $this->getEntityTypeList($params);

        $params = ConfigParams::create()
            ->withPath($params->getImportPath())
            ->withManifest($manifest)
            ->withEntityTypeList($entityTypeList)
            ->withExportImportDefs($params->getExportImportDefs())
            ->withConfigIgnoreList($params->getConfigIgnoreList());

        $configImport = $this->injectableFactory->create(
            ConfigImport::class
        );

        $configImport->process($params);

        $this->dataManager->clearCache();
    }
}
