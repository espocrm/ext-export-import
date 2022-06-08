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

namespace Espo\Modules\ExportImport\Tools\Import\Placeholder;

use Espo\Core\{
    Di,
};

use Espo\Modules\ExportImport\Tools\Import\{
    Params,
    Placeholder\Factory as PlaceholderFactory,
    Placeholder\Actions\Params as ActionParams
};

use Exception;

class Handler implements

    Di\MetadataAware
{
    use Di\MetadataSetter;

    protected $placeholderFactory;

    public function __construct(PlaceholderFactory $placeholderFactory)
    {
        $this->placeholderFactory = $placeholderFactory;
    }

    public function process(Params $params, array $row): array
    {
        $entityType = $params->getEntityType();

        $placeholderData =
            $params->getExportImportDefs()[$entityType]['fields'] ?? null;

        if ($placeholderData) {

            foreach ($placeholderData as $fieldName => $placeholderFieldDefs) {
                $placeholderActionClassName =
                    $placeholderFieldDefs['placeholderAction'] ?? null;

                $fieldDefs = $this->metadata->get([
                    'entityDefs', $entityType, 'fields', $fieldName
                ], []);

                if (!$placeholderActionClassName) {

                    continue;
                }

                $placeholderAction = $this->placeholderFactory->get(
                    $placeholderActionClassName
                );

                $params = ActionParams::create($entityType)
                    ->withFieldName($fieldName)
                    ->withRecordData($row)
                    ->withFieldDefs($fieldDefs)
                    ->withExportImportDefs($params->getExportImportDefs())
                    ->withManifest($params->getManifest())
                    ->withUserActive($params->getUserActive())
                    ->withUserPassword($params->getUserPassword());

                try {
                    $row[$fieldName] = $placeholderAction->normalize(
                        $params, $params->getFieldValue()
                    );
                }
                catch (Exception $e) {
                    $GLOBALS['log']->debug(
                        "ExportImport [Import][Placeholder]: " .
                        "Error getting a value for the field {$entityType}.{$fieldName}," .
                        " error: " . $e->getMessage()
                    );
                }
            }

        }

        return $row;
    }
}
