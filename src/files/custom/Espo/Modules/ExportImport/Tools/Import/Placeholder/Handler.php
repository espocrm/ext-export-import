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

namespace Espo\Modules\ExportImport\Tools\Import\Placeholder;

use Exception;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Log;

use Espo\Modules\ExportImport\Tools\Import\Params;
use Espo\Modules\ExportImport\Tools\Processor\Exceptions\Skip as SkipException;
use Espo\Modules\ExportImport\Tools\Import\Placeholder\Factory as PlaceholderFactory;
use Espo\Modules\ExportImport\Tools\Import\Placeholder\Actions\Params as ActionParams;

class Handler
{
    private Log $log;

    private Metadata $metadata;

    protected $placeholderFactory;

    public function __construct(
        Log $log,
        Metadata $metadata,
        PlaceholderFactory $placeholderFactory
    ) {
        $this->log = $log;
        $this->metadata = $metadata;
        $this->placeholderFactory = $placeholderFactory;
    }

    public function process(Params $params, array $row): array
    {
        $entityType = $params->getEntityType();

        $defs = $params->getExportImportDefs()[$entityType]['fields'] ?? null;

        if (!$defs) {
            return $row;
        }

        $processedRow = $row;

        foreach ($defs as $fieldName => $fieldDefs) {
            $className = $fieldDefs['placeholderAction'] ?? null;

            if (!$className) {
                continue;
            }

            $action = $this->placeholderFactory->get($className);

            $metadataFieldDefs = $this->metadata->get([
                'entityDefs', $entityType, 'fields', $fieldName
            ], []);

            $params = ActionParams::create($entityType)
                ->withFieldName($fieldName)
                ->withRecordData($row)
                ->withFieldDefs($metadataFieldDefs)
                ->withExportImportDefs($params->getExportImportDefs())
                ->withManifest($params->getManifest())
                ->withUserActive($params->getUserActive())
                ->withUserActiveList($params->getUserActiveList())
                ->withUserPassword($params->getUserPassword())
                ->withSkipPassword($params->getSkipPassword());

            try {
                $processedRow[$fieldName] = $action->normalize(
                    $params,
                    $params->getFieldValue()
                );
            }
            catch (SkipException $e) {
                continue;
            }
            catch (Exception $e) {
                $this->log->warning(
                    "ExportImport [Import][Placeholder]: " .
                    "Error getting a value for the field {$entityType}.{$fieldName}," .
                    " error: " . $e->getMessage()
                );
            }
        }

        return $processedRow;
    }
}
