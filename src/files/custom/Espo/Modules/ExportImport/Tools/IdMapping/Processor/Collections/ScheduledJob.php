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

namespace Espo\Modules\ExportImport\Tools\IdMapping\Processor\Collections;

use Espo\Core\Utils\Log;
use Espo\ORM\EntityManager;
use Espo\Modules\ExportImport\Tools\IdMapping\Util;
use Espo\Modules\ExportImport\Tools\Processor\Data;
use Espo\Modules\ExportImport\Tools\IdMapping\Params;
use Espo\Entities\ScheduledJob as ScheduledJobEntity;
use Espo\Modules\ExportImport\Tools\IdMapping\CollectionProcessor;

class ScheduledJob implements CollectionProcessor
{
    public function __construct(
        private Log $log,
        private EntityManager $entityManager
    ) {}

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
        $job = $row['job'] ?? null;
        $status = $row['status'] ?? ScheduledJobEntity::STATUS_ACTIVE;

        if (!$id || !$job) {
            return null;
        }

        $record = $this->entityManager
            ->getRDBRepository(ScheduledJobEntity::ENTITY_TYPE)
            ->where([
                'job' => $job,
                'status' => $status
            ])
            ->findOne();

        if (!$record) {
            return null;
        }

        $actualId = $record->getId();

        if ($id === $actualId) {
            return null;
        }

        $this->log->debug(
            'Imported ScheduledJob [' . $id . '] is linked ' .
            'to the ScheduledJob [' . $actualId . '] ' .
            'identified by the job [' . $job . '].'
        );

        return [
            $id => $actualId
        ];
    }
}
