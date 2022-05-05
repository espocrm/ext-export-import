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
    Export\Params as ExportParams,
    Export\Processor\Collection,
    Export\EntityExport as EntityExportTool,
    Manifest\ManifestWriter,
};

use Exception;

class Export implements

    Tool,
    Di\MetadataAware,
    Di\FileManagerAware,
    Di\InjectableFactoryAware,
    Di\LogAware,
    Di\ConfigAware
{
    use Di\MetadataSetter;
    use Di\FileManagerSetter;
    use Di\InjectableFactorySetter;
    use Di\LogSetter;
    use Di\ConfigSetter;

    private $defs;

    public function __construct(Defs $defs)
    {
        $this->defs = $defs;
    }

    public function run(Params $params) : void
    {
        $format = $params->getFormat() ?? null;
        $exportPath = $params->getExportPath() ?? null;
        $defs = $params->getExportImportDefs();

        if (!$format) {
            throw new Error('Option "format" is not defined.');
        }

        if (!$exportPath) {
            throw new Error('Export path is not defined.');
        }

        $this->fileManager->removeInDir($exportPath);

        $entityList = $params->getEntityTypeList() ??
            $this->defs->getEntityTypeList();

        foreach ($entityList as $entityType) {
            $exportDisabled = $defs[$entityType]['exportDisabled'] ?? false;

            if ($exportDisabled) {

                continue;
            }

            try {
                $this->exportEntity($entityType, $params);
            } catch (Exception $e) {
                $this->log->warning(
                    'ExportImport [' . $entityType . ']:' . $e->getMessage()
                );
            }
        }

        $this->createManifest($params);
    }

    protected function exportEntity(string $entityType, Params $params): void
    {
        $format = $params->getFormat();
        $collectionClass = $this->getCollectionClass($entityType);

        $fileExtension = $this->metadata->get([
            'app', 'exportImport', 'formatDefs', $format, 'fileExtension'
        ]);

        $exportParams = ExportParams::create($entityType)
            ->withFormat($format)
            ->withAccessControl(false)
            ->withPath($params->getExportEntityPath())
            ->withExportImportDefs($params->getExportImportDefs())
            ->withCollectionClass($collectionClass)
            ->withFileExtension($fileExtension);

        $export = $this->injectableFactory->create(EntityExportTool::class);
        $export->setParams($exportParams);

        $export->run();
    }

    protected function getCollectionClass(string $entityType): ?Collection
    {
        $collectionClassName = $this->metadata->get([
            'app', 'exportImport', 'exportCollectionClassNameMap', $entityType
        ]);

        if (!$collectionClassName || !class_exists($collectionClassName)) {
            return null;
        }

        return $this->injectableFactory->create($collectionClassName);
    }

    protected function createManifest(Params $params): bool
    {
        $manifestWriter = $this->injectableFactory->createWith(ManifestWriter::class, [
            'params' => $params,
        ]);

        return $manifestWriter->save();
    }
}
