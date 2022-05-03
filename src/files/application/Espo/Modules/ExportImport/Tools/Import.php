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
    Import\EntityImport as EntityImportTool
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
        $defsSource = $params->getDefsSource() ?? null;
        $dataPath = $params->getDataPath() ?? null;

        if (!$format) {
            throw new Error('Option "format" is not defined.');
        }

        if (!$dataPath) {
            throw new Error('Data path is not defined.');
        }

        if (!$defsSource) {
            throw new Error('Option "defsSource" is not defined.');
        }

        $defs = $this->metadata->get([$defsSource]);

        $entityTypeList = $this->getEntityTypeList($params);

        $manifest = $this->injectableFactory->createWith(Manifest::class, [
            'params' => $params,
        ]);

        foreach ($entityTypeList as $entityType) {
            $importDisabled = $defs[$entityType]['importDisabled'] ?? false;

            if ($importDisabled) {

                continue;
            }

            try {
                $this->importEntity($entityType, $params, $manifest);
            } catch (Exception $e) {
                $this->log->warning(
                    'ExportImport [' . $entityType . ']:' . $e->getMessage()
                );
            }
        }
    }

    protected function getEntityTypeList(Params $params): array
    {
        if ($params->getEntityTypeList()) {
            return $params->getEntityTypeList();
        }

        $entityFileList = $this->fileManager->getFileList(
            $params->getDataEntityPath(),
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

    protected function importEntity(string $entityType, Params $params, Manifest $manifest): void
    {
        $importParams = ImportParams::create($entityType)
            ->withFormat($params->getFormat())
            ->withPath($params->getDataEntityPath())
            ->withDefsSource($params->getDefsSource())
            ->withManifest($manifest)
            ->withImportType($params->getImportType());

        $import = $this->injectableFactory->create(EntityImportTool::class);
        $import->setParams($importParams);

        $result = $import->run();
    }
}
