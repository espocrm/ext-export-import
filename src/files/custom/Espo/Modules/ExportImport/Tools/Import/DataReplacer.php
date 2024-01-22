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

namespace Espo\Modules\ExportImport\Tools\Import;

use Espo\Core\Utils\Json as JsonUtil;

use Espo\Modules\ExportImport\Tools\Import\Params;
use Espo\Modules\ExportImport\Tools\Metadata\Relation as RelationTool;

class DataReplacer
{
    public function __construct(
        private RelationTool $relationTool,
    ) {}

    public function processString(
        Params $params,
        array &$row,
        string $attributeName
    ): void {
        $id = $row[$attributeName] ?? null;

        if (!$id) {
            return;
        }

        if (!is_string($id)) {
            return;
        }

        $newId = $this->getNewReplaceId($params, $attributeName, $id);

        if (!$newId) {
            return;
        }

        $row[$attributeName] = $newId;
    }

    public function processObject(
        Params $params,
        array &$row,
        string $attributeName
    ): void {
        $data = $row[$attributeName] ?? null;

        if (!$data) {
            return;
        }

        $isEncode = false;

        if (is_string($data)) {
            $isEncode = true;
            $data = JsonUtil::decode($data);
        }

        if (!is_object($data)) {
            return;
        }

        $isSave = false;

        foreach ($data as &$actualId) {
            if (!is_string($actualId)) {
                continue;
            }

            $newId = $this->getNewReplaceId($params, $attributeName, $actualId);

            if (!$newId) {
                continue;
            }

            $isSave = true;
            $actualId = $newId;
        }

        if (!$isSave) {
            return;
        }

        if ($isEncode) {
            $data = JsonUtil::encode($data);
        }

        $row[$attributeName] = $data;
    }

    private function getNewReplaceId(
        Params $params,
        string $attributeName,
        string $actualId
    ): ?string {
        $idMap = $params->getReplaceIdMap();

        foreach ($idMap as $entityType => $entityIdMap) {
            $newId = $entityIdMap[$actualId] ?? null;

            if (!$newId) {
                continue;
            }

            $isRelatedToEntity = $this->relationTool
                ->isAttributeRelatedTo(
                    $params->getEntityType(),
                    $attributeName,
                    $entityType
                );

            if (!$isRelatedToEntity) {
                continue;
            }

            return $newId;
        }

        return null;
    }
}
