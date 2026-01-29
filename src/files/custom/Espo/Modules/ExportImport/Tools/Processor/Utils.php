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

namespace Espo\Modules\ExportImport\Tools\Processor;

use DateTime;
use DateTimeZone;
use Espo\Entities\User;
use Espo\Core\Utils\Util;
use Espo\Core\Utils\Metadata;
use Espo\Modules\ExportImport\Tools\Processor\Params;
use Espo\Modules\ExportImport\Tools\Params as ToolParams;

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
     * Write a message in a terminal without a new line
     */
    public static function write(ToolParams $params, ?string $message): void
    {
        if ($params->isQuiet() || !$message) {
            return;
        }

        $params->getIO()->write($message);
    }

    public static function isPromptConfirmed(ToolParams $params): bool
    {
        if ($params->isQuiet()) {
            return true;
        }

        $result = $params->getIO()->readLine();

        if (in_array($result, ['y', 'Y', 'yes', 'YES'])) {
            return true;
        }

        return false;
    }

    /**
     * Write a message in a terminal
     */
    public static function writeLine(ToolParams $params, ?string $message): void
    {
        if ($params->isQuiet() || !$message) {
            return;
        }

        $params->getIO()->writeLine($message);
    }

    public static function writeNewLine(ToolParams $params): void
    {
        if ($params->isQuiet()) {
            return;
        }

        $params->getIO()->writeLine("");
    }

    /**
     * Write a list of messages
     */
    public static function writeList(
        ToolParams $params,
        ?array $list,
        ?string $title = null
    ): void {
        if (empty($list)) {

            return;
        }

        $list = array_unique($list);

        self::writeNewLine($params);

        if ($title) {
            self::writeLine($params, $title);
        }

        foreach ($list as $item) {
            self::writeLine($params, "  - " . $item);
        }
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

    public static function isPatternMatched(string $value, array $patternList): bool
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

    /**
     * Filter a list for a given entity type
     * Ex.: ["name", "Account.description", "Contact.email"] for Account will be ["name", "description"]
     */
    public static function getListForEntity(
        string $entityType,
        ?array $allList
    ): array {
        if (!$allList) {
            return [];
        }

        $list = [];

        foreach ($allList as $item) {
            if (strpos($item, '.') === false) {
                $list[] = $item;

                continue;
            }

            $slices = explode('.', $item);

            $sliceEntityType = isset($slices[0]) ? trim($slices[0]) : null;
            $sliceItem = isset($slices[1]) ? trim($slices[1]) : null;

            if (!$sliceEntityType || !$sliceItem) {
                continue;
            }

            if ($sliceEntityType != $entityType) {
                continue;
            }

            $list[] = $sliceItem;
        }

        return $list;
    }

    public static function normalizeBoolFromArray(
        array $data,
        string $optionName,
        ?bool $default = false
    ): ?bool
    {
        if (!array_key_exists($optionName, $data)) {
            return $default;
        }

        if (is_null($data[$optionName])) {
            return $default;
        }

        return filter_var($data[$optionName], FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Normalize date
     */
    public static function normalizeDateTime(
        $value,
        $default = null,
    ): ?DateTime {
        if (!$value) {
            return $default;
        }

        $date = new DateTime($value, new DateTimeZone('UTC'));

        if (!$date) {
            return $default;
        }

        return $date;
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

        if (($key = array_search(User::ENTITY_TYPE, $p2)) !== false) {
            unset($p2[$key]);

            $priorityList['p2'] = array_unique($p2);
            $priorityList['p0'] = $priorityList['p0'] ?? [];

            array_unshift($priorityList['p0'], User::ENTITY_TYPE);
        }

        ksort($priorityList);

        $list = [];

        foreach ($priorityList as $rowList) {
            $list = array_merge($list, $rowList);
        }

        return $list;
    }

    public static function getEntityTypeByMetadataFile(
        string $file,
        ?string $default = null
    ): ?string {
        if (!preg_match('/\/([^\/]+)\.json$/', $file, $matches)) {
            return $default;
        }

        return $matches[1] ?? $default;
    }
}
