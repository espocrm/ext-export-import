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

namespace Espo\Modules\ExportImport\Tools\Erase;

use Espo\Core\Utils\Json;

use Exception;

class Util
{
    /**
     * Get data of a json file
     */
    public static function getFileData(string $file, bool $associative = false): ?array
    {
        if (!file_exists($file)) {
            return null;
        }

        $content = file_get_contents($file);

        if ($content === false) {
            return null;
        }

        try {
            $data = $associative
                ? Json::decode($content, true)
                : Json::decode($content);

            if (!$associative && is_object($data)) {
                $data = get_object_vars($data);
            }
        }
        catch (Exception $e) {}

        if (empty($data) || !is_array($data)) {
            return null;
        }

        return $data;
    }

    public static function arrayDiffAssocRecursive(?array $array1, ?array $array2): array
    {
        $result = [];

        if (!$array1) {
            $array1 = [];
        }

        if (!$array2) {
            $array2 = [];
        }

        foreach ($array1 as $key => $value) {
            if (is_array($value)) {
                if (!isset($array2[$key]) || !is_array($array2[$key])) {
                    $result[$key] = $value;
                } else {
                    $newDiff = self::arrayDiffAssocRecursive($value, $array2[$key]);

                    if (!empty($newDiff)) {
                        $result[$key] = $newDiff;
                    }
                }
            } else if (!array_key_exists($key, $array2) || $array2[$key] !== $value) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Get row data by ID
     */
    public static function getRowById(string $id, ?array $data): ?array
    {
        if (!$data) {
            return null;
        }

        foreach ($data as $row) {
            if (is_object($row)) {
                $row = get_object_vars($row);
            }

            $rowId = $row['id'] ?? null;

            if (!$rowId) {
                continue;
            }

            if ($id !== $rowId) {
                continue;
            }

            return $row;
        }

        return null;
    }
}
