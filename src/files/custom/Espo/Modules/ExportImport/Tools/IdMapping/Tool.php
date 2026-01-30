<?php
/************************************************************************
 * This file is part of Export Import extension for EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Modules\ExportImport\Tools\IdMapping;

use Exception;
use Espo\Core\Utils\Log;
use Espo\Core\Utils\Metadata;
use Espo\Core\Exceptions\Error;
use Espo\Core\InjectableFactory;
use Espo\Modules\ExportImport\Tools\Params;
use Espo\Modules\ExportImport\Tools\IdMapping\Params as IdMappingParams;
use Espo\Modules\ExportImport\Tools\IdMapping\Processor\Entity as EntityProcessor;

class Tool
{
    public function __construct(
        private Log $log,
        private Metadata $metadata,
        private InjectableFactory $injectableFactory
    ) {}

    public function getIdMap(Params $params): array
    {
        $path = $params->getPath() ?? null;
        $format = $params->getFormat() ?? null;

        if (!$format) {
            throw new Error('Option "format" is not defined.');
        }

        if (!$path) {
            throw new Error('Import path is not defined.');
        }

        if (!file_exists($path)) {
            throw new Error("Import path \"{$path}\" does not exist.");
        }

        $entityTypeList = $this->getEntityTypeList($params);

        return $this->getEntitiesIdMap($params, $entityTypeList);
    }

    private function getEntityTypeList(Params $params): array
    {
        $classMap =  $this->metadata->get([
            'app', 'exportImport', 'idMappingCollectionClassNameMap'
        ]) ?? [];

        return array_keys($classMap);
    }

    private function getEntitiesIdMap(Params $params, array $entityTypeList): array
    {
        $idMap = [];

        foreach ($entityTypeList as $entityType) {
            try {
                $map = $this->getEntityIdMap($params, $entityType, $idMap);
            } catch (Exception $e) {
                $this->log->warning(
                    'ExportImport [IdMapping][' . $entityType . ']: ' . $e->getMessage()
                );

                continue;
            }

            $idMap[$entityType] = $map;
        }

        return $idMap;
    }

    private function getEntityIdMap(
        Params $params,
        string $entityType,
        array $actualIdMap
    ): array {
        $idMappingParams = IdMappingParams::create($entityType)
            ->withFormat($params->getFormat())
            ->withPath($params->getPath())
            ->withEntitiesPath($params->getEntitiesPath())
            ->withExportImportDefs($params->getExportImportDefs())
            ->withActualIdMap($actualIdMap);

        return $this->injectableFactory
            ->create(EntityProcessor::class)
            ?->run($idMappingParams) ?? [];
    }
}
