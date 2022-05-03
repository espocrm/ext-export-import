<?php
/************************************************************************
 * This file is part of Export Import extension for EspoCRM.
 *
 * Export Import extension for EspoCRM.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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
    Di,
    Exceptions\Error,
};

class Helper implements

    Di\ConfigAware,
    Di\MetadataAware
{
    use Di\ConfigSetter;
    use Di\MetadataSetter;

    public function getFieldDateFormat(string $entityType, string $fieldName): string
    {
        $fieldType = $this->metadata->get([
            'entityDefs', $entityType, 'fields', $fieldName, 'type'
        ]);

        switch ($fieldType) {
            case 'datetime':
                return $this->config->get('dateFormat') . ' ' . $this->config->get('timeFormat');
                break;

            case 'date':
                return $this->config->get('dateFormat');
                break;
        }

        throw new Error("Unknown datetime field type '{$fieldType}'");
    }
}
