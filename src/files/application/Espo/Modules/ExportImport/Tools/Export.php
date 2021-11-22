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

use Espo\Modules\ExportImport\Tools\Export\{
    Params,
    Collection,
    EntityExport as EntityExportTool
};

class Export implements

    Tool,
    Di\MetadataAware,
    Di\FileManagerAware,
    Di\InjectableFactoryAware
{
    use Di\MetadataSetter;
    use Di\FileManagerSetter;
    use Di\InjectableFactorySetter;

    private $defs;

    public function __construct(Defs $defs)
    {
        $this->defs = $defs;
    }

    public function run() : void
    {
        $options = $this->metadata->get('app.exportImport');

        $exportPath = $options['exportPath'] ?? null;

        if (!$exportPath) {
            throw new Error('Export path is not defined.');
        }

        $this->fileManager->removeInDir($exportPath);

        $dataDefs = $this->metadata->get(['exportImportDefs']);

        foreach ($this->defs->getEntityTypeList() as $entityType) {
            $entityDataDefs = $dataDefs[$entityType] ?? [];

            $exportDisabled = $entityDataDefs['exportDisabled'] ?? false;

            if ($exportDisabled) {

                continue;
            }

            $this->exportEntity($entityType, $exportPath);
        }
    }

    protected function exportEntity(string $entityType, string $exportPath): void
    {
        $storagePath = $exportPath . '/Entities';
        $collectionClass = $this->getCollectionClass($entityType);

        $exportParams = Params::create($entityType)
            ->withFormat($this->metadata->get('app.exportImport.format'))
            ->withPath($storagePath)
            ->withAccessControl(false)
            ->withCollectionClass($collectionClass);

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
}
