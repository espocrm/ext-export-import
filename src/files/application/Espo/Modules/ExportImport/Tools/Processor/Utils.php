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

namespace Espo\Modules\ExportImport\Tools\Processor;

use Espo\{
    Core\Utils\Util,
    Core\Utils\Metadata,
    Entities\User,
};

use Espo\Modules\ExportImport\Tools\{
    Processor\Params,
    Params as ToolParams,
};

class Utils
{
    /**
     * Get a directory path in data/upload
     */
    public static function getDirPathInUpload(Params $params): string
    {
        return ToolParams::STORAGE_PATH;
    }

    /**
     * Get a files path in data/upload
     */
    public static function getFilePathInUpload(Params $params, string $id): string
    {
        return Util::concatPath(
            self::getDirPathInUpload($params),
            $id
        );
    }

    /**
     * Get a directory path in export/import directory
     */
    public static function getDirPathInData(Params $params): string
    {
        return Util::concatPath(
            $params->getFilesPath(),
            $params->getEntityType()
        );
    }

    /**
     * Get a files path in export/import directory
     */
    public static function getFilePathInData(Params $params, string $id): string
    {
        return Util::concatPath(
            self::getDirPathInData($params),
            $id
        );
    }

    /**
     * Write a message in a terminal
     */
    public static function writeLine(ToolParams $params, ?string $message): void
    {
        if ($params->isQuiet() || !$message) {
            return;
        }

        $io = $params->getIO();

        $io->writeLine($message);
    }

    /**
     * Quote a string for regular expression
     */
    public static function quotePattern(string $pattern): string
    {
        $pattern = preg_replace_callback(
            '/([^*])/',
            function ($matches) {
                return preg_quote($matches[1], "/");
            },
            $pattern
        );

        return str_replace('*', '.*', $pattern);
    }

    public static function isPatternMatched(array $patternList, string $value): bool
    {
        foreach ($patternList as $pattern) {
            $pattern = self::quotePattern($pattern);

            if (preg_match('/^' . $pattern . '$/', $value)) {
                return true;
            }
        }

        return false;
    }

    public static function isScopeEntity(Metadata $metadata, string $scope): bool
    {
        return (bool) $metadata->get(['scopes', $scope, 'entity']);
    }

    public static function normalizeList(
        $value,
        $default = null,
        string $delimiter = ','
    ): ?array
    {
        if (!$value) {
            return $default;
        }

        if (is_string($value)) {
            $value = explode($delimiter, $value);

            foreach ($value as &$item) {
                $item = trim($item);
            }
        }

        if (!is_array($value)) {
            return $default;
        }

        if (array_search(ToolParams::APPEND, $value, true) !== false) {
            $value = Util::unsetInArrayByValue(ToolParams::APPEND, $value);
            $value = array_merge($default ?? [], $value);
        }

        return $value;
    }

    public static function normalizeBoolFromArray(
        array $data,
        string $optionName,
        bool $default = false
    ): bool
    {
        if (!array_key_exists($optionName, $data)) {
            return $default;
        }

        return filter_var($data[$optionName], FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get a list of sorted EntityType
     */
    public static function sortEntityTypeListByType(
        Metadata $metadata,
        array $entityTypeList
    ): array
    {
        sort($entityTypeList);

        $priorityList = [];

        foreach ($entityTypeList as $entityType) {
            $scopeMetadata = $metadata->get(['scopes', $entityType]);

            $isEntity = $scopeMetadata['entity'] ?? false;
            $isObject = $scopeMetadata['object'] ?? false;
            $isTab = $scopeMetadata['tab'] ?? false;

            if ($isEntity && $isObject && $isTab) {
                $priorityList['p2'][] = $entityType;

                continue;
            }

            if ($isEntity && $isObject) {
                $priorityList['p3'][] = $entityType;

                continue;
            }

            if ($isEntity) {
                $priorityList['p1'][] = $entityType;

                continue;
            }

            $priorityList['p0'][] = $entityType;
        }

        $p2 = $priorityList['p2'] ?? [];

        if (in_array(User::ENTITY_TYPE, $p2)) {
            array_unshift($p2, User::ENTITY_TYPE);
            $priorityList['p2'] = array_unique($p2);
        }

        ksort($priorityList);

        $list = [];

        foreach ($priorityList as $rowList) {
            $list = array_merge($list, $rowList);
        }

        return $list;
    }
}
