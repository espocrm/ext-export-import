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

namespace Espo\Modules\ExportImport\Tools\IdMapping\Processor\Collections;

use Espo\Entities\User as UserEntity;

use Espo\Modules\ExportImport\Tools\Processor\Data;
use Espo\Modules\ExportImport\Tools\IdMapping\Params;

use Espo\Modules\ExportImport\Tools\IdMapping\Util;
use Espo\Modules\ExportImport\Tools\IdMapping\CollectionProcessor;

class Preferences implements CollectionProcessor
{
    public function process(Params $params, Data $data): array
    {
        $data->rewind();

        $idMap = [];

        while (($row = $data->readRow()) !== null) {
            $rowIdMap = $this->getRowIdMap($params, $row);

            if (!$rowIdMap) {
                continue;
            }

            $idMap = Util::arrayMerge($idMap, $rowIdMap);
        }

        return $idMap;
    }

    private function getRowIdMap(Params $params, array $row): ?array
    {
        $id = $row['id'] ?? null;

        if (!$id) {
            return null;
        }

        $userIdMap = $params->getActualIdMap()[UserEntity::ENTITY_TYPE] ?? [];

        $newId = $userIdMap[$id] ?? null;

        if (!$newId) {
            return null;
        }

        return [
            $id => $newId
        ];
    }
}
