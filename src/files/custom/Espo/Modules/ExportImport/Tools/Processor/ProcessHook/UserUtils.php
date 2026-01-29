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

namespace Espo\Modules\ExportImport\Tools\Processor\ProcessHook;

use Espo\Entities\User as UserEntity;
use Espo\Modules\ExportImport\Tools\Processor\Params;

class UserUtils
{
    public static function isSkipUser(
        Params $params,
        UserEntity $entity,
        array $row
    ): bool {
        if (self::isSkipByType($params, $entity, $row)) {
            return true;
        }

        if (self::isValueEqual($params, $entity, $row, 'id')) {
            return true;
        }

        if (self::isValueEqual($params, $entity, $row, 'userName')) {
            return true;
        }

        return false;
    }

    private static function isSkipByType(
        Params $params,
        UserEntity $entity,
        array $row
    ): bool {
        $skipTypeList = $params->getExportImportDefs()['User']['skipRecordList']['type']
            ?? [];

        foreach ($skipTypeList as $skipType) {
            $type = $row['type'] ?? null;

            if ($entity->get('type') === $skipType) {
                return true;
            }

            if ($type === $skipType) {
                return true;
            }
        }

        return false;
    }

    private static function isValueEqual(
        Params $params,
        UserEntity $entity,
        array $row,
        string $fieldName
    ): bool {
        $rowValue = $row[$fieldName] ?? null;
        $entityValue = $entity->get($fieldName) ?? null;

        $userSkipList = $params->getUserSkipList();

        if (empty($userSkipList)) {
            return false;
        }

        if ($rowValue && in_array($rowValue, $userSkipList)) {
            return true;
        }

        if ($entityValue && in_array($entityValue, $userSkipList)) {
            return true;
        }

        return false;
    }
}
