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

namespace Espo\Modules\ExportImport\Tools\Import\Placeholder\Actions\DateTime;

use Espo\Core\Di;
use Espo\Core\Exceptions\Error;
use Espo\Modules\ExportImport\Tools\Import\Placeholder\Factory;
use Espo\Modules\ExportImport\Tools\Import\Placeholder\Actions\Action;
use Espo\Modules\ExportImport\Tools\Import\Placeholder\Actions\Params;

class ExportDifferenceField implements

    Action,
    Di\MetadataAware,
    Di\InjectableFactoryAware
{
    use Di\MetadataSetter;
    use Di\InjectableFactorySetter;

    public function normalize(Params $params, $actualValue)
    {
        $entityType = $params->getEntityType();
        $placeholderDefs = $params->getPlaceholderDefs();

        $field = $placeholderDefs['placeholderData']['field'] ?? null;
        $fieldList = $placeholderDefs['placeholderData']['fieldList'] ?? null;

        if (!$field && !$fieldList) {
            throw new Error('Option "field" or "fieldList" is not defined');
        }

        $recordData = $params->getRecordData();

        if ($fieldList) {
            foreach ($fieldList as $fieldItem) {
                $fieldName = $fieldItem;
                $fieldValue = $recordData[$fieldItem] ?? null;

                if ($fieldValue) {
                    break;
                }
            }
        }

        if ($field) {
            $fieldName = $field;
            $fieldValue = $recordData[$field] ?? null;
        }

        $fieldDefs = $this->metadata->get([
            'entityDefs', $entityType, 'fields', $fieldName
        ], []);

        $fieldParams = $params
            ->withFieldName($fieldName)
            ->withFieldDefs($fieldDefs);

        $placeholderFactory = $this->injectableFactory->create(Factory::class);
        $exportDifferenceAction = $placeholderFactory->get('DateTime\\ExportDifference');

        return $exportDifferenceAction->normalize($fieldParams, $fieldValue);
    }
}
