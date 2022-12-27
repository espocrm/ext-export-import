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

namespace Espo\Modules\ExportImport\Tools\Import\Placeholder\Actions;

use Espo\Core\{
    Exceptions\Error,
    Utils\DateTime as DateTimeUtil,
};

use Espo\Modules\ExportImport\Tools\Import\Placeholder\{
    Actions\Params,
};

use stdClass;

class Utils
{
    public static function replaceKeyInObject(stdClass $data, string $key, mixed $value): stdClass
    {
        foreach ($data as $objectKey => &$objectRow) {
            if ($objectKey == $key) {
                $objectRow = $value;
            }

            if (is_object($objectRow)) {
                $objectRow = static::replaceKeyInObject($objectRow, $key, $value);
            }
        }

        return $data;
    }

    public static function isCurrencyChangePermitted(Params $params): bool
    {
        $key = $params->getPlaceholderDefs()['placeholderData']['key'] ?? null;
        $updateCurrency = $params->getExportImportDefs()['updateCurrency'] ?? false;

        if (!$updateCurrency && $key == 'defaultCurrency') {
            return false;
        }

        return true;
    }

    public static function getDateFieldFormat(string $fieldType): string
    {
        switch ($fieldType) {
            case 'datetime':
            case 'datetimeOptional':
                return DateTimeUtil::SYSTEM_DATE_TIME_FORMAT;
                break;

            case 'date':
                return DateTimeUtil::SYSTEM_DATE_FORMAT;
                break;
        }

        throw new Error("Unknown datetime field type '{$fieldType}'");
    }
}
