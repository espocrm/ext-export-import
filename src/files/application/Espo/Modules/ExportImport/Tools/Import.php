<?php
/************************************************************************
 * This file is part of Export Import extension for EspoCRM.
 *
 * Export Import extension for EspoCRM.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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
};

use Exception;

class Import implements

    Tool,
    Di\MetadataAware,
    Di\FileManagerAware,
    Di\InjectableFactoryAware,
    Di\LogAware
{
    use Di\MetadataSetter;
    use Di\FileManagerSetter;
    use Di\InjectableFactorySetter;
    use Di\LogSetter;

    private $defs;

    public function __construct(Defs $defs)
    {
        $this->defs = $defs;
    }

    public function run(Params $params) : void
    {
        $format = $params->getFormat() ?? null;
        $dataPath = $params->getDataPath() ?? null;
        $defs = $params->getExportImportDefs();

        if (!$format) {
            throw new Error('Option "format" is not defined.');
        }

        if (!$dataPath) {
            throw new Error('Data path is not defined.');
        }

        $entityTypeList = $this->getEntityTypeList($params);

        $manifest = $this->injectableFactory->createWith(Manifest::class, [
            'params' => $params,
        ]);

        foreach ($entityTypeList as $entityType) {
            $importDisabled = $defs[$entityType]['importDisabled'] ?? false;

            if ($importDisabled) {

                continue;
            }

            ProcessorUtils::writeLine($params, "{$entityType}...");

            try {
                $globalMessage = $this->importEntity($entityType, $params, $manifest);
            } catch (Exception $e) {
                ProcessorUtils::writeLine(
                    $params, "  Error: " . $e->getMessage()
                );

                $this->log->warning(
                    'ExportImport [' . $entityType . ']:' . $e->getMessage()
                );
            }
        }

        ProcessorUtils::writeLine($params, $globalMessage);
    }

    protected function getEntityTypeList(Params $params): array
    {
        if ($params->getEntityTypeList()) {
            return $params->getEntityTypeList();
        }

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

    protected function importEntity(string $entityType, Params $params, Manifest $manifest): ?string
    {
        $processHookClass = $this->getProcessHookClass($entityType);

        $importParams = ImportParams::create($entityType)
            ->withFormat($params->getFormat())
            ->withPath($params->getDataPath())
            ->withEntitiesPath($params->getDataEntitiesPath())
            ->withFilesPath($params->getDataFilesPath())
            ->withExportImportDefs($params->getExportImportDefs())
            ->withManifest($manifest)
            ->withImportType($params->getImportType())
            ->withSetDefaultCurrency($params->getSetDefaultCurrency())
            ->withProcessHookClass($processHookClass)
            ->withUserActive($params->getUserActive())
            ->withUserPassword($params->getUserPassword());

        $import = $this->injectableFactory->create(EntityImportTool::class);
        $import->setParams($importParams);

        $result = $import->run();

        ProcessorUtils::writeLine($params, $result->getMessage());

        return $result->getGlobalMessage();
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
}
