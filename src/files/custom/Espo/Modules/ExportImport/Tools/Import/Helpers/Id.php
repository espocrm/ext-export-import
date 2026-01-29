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

namespace Espo\Modules\ExportImport\Tools\Import\Helpers;

use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Query\SelectBuilder;
use Espo\Modules\ExportImport\Tools\Processor\Utils;
use Espo\Modules\ExportImport\Tools\Processor\Params;

class Id
{
    public function __construct(
        private Metadata $metadata,
        private EntityManager $entityManager
    ) {}

    /**
     * Get entity ID for a row
     */
    public function getEntityId(Params $params, array $row): ?string
    {
        $id = $row['id'] ?? null;

        $entityType = $params->getEntityType();

        if ($id && Utils::isScopeEntity($this->metadata, $entityType)) {
            return $id;
        }

        return $this->getIdByForeignId($params, $row);
    }

    private function getIdByForeignId(Params $params, array $row): ?string
    {
        $entityType = $params->getEntityType();

        $entityDefs = $this->entityManager
            ->getDefs()
            ->getEntity($entityType);

        $where = [];

        foreach ($entityDefs->getAttributeList() as $attribute) {
            $name = $attribute->getName();
            $type = $attribute->getType();

            switch ($type) {
                case 'foreignId':
                    $value = $row[$name] ?? null;

                    if ($value) {
                        $where[$name] = $value;
                    }
                    break;
            }
        }

        if (!empty($where)) {
            $query = SelectBuilder::create()
                ->from($entityType)
                ->withDeleted()
                ->build();

            $record = $this->entityManager
                ->getRDBRepository($entityType)
                ->clone($query)
                ->where($where)
                ->findOne();

            if ($record) {
                return $record->getId();
            }
        }

        return $row['id'] ?? null;
    }

    public function getDeletedEntityById(string $entityType, string $id): ?Entity
    {
        $query = SelectBuilder::create()
            ->from($entityType)
            ->withDeleted()
            ->build();

        return $this->entityManager
            ->getRDBRepository($entityType)
            ->clone($query)
            ->where(['id' => $id])
            ->findOne();
    }
}
