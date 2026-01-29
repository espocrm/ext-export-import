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

namespace Espo\Modules\ExportImport\Tools\Core;

use Espo\ORM\Defs;
use Espo\Core\Utils\Metadata;
use Espo\Core\ORM\EntityManager;
use Espo\Modules\ExportImport\Tools\Core\Relation as RelationTool;

class Entity
{
    private const SCOPE_CATEGORY_TREE = 'CategoryTree';

    public function __construct(
        private Defs $defs,
        private Metadata $metadata,
        private EntityManager $entityManager,
        private RelationTool $relationTool
    ) {}

    public function isCustom(string $entityType): bool
    {
        $isCustom = $this->metadata->get(['scopes', $entityType, 'isCustom']);

        if ($isCustom) {
            return true;
        }

        return false;
    }

    /**
     * Get all additional tables
     * Result = [
     *     parentEntityType => string,
     *     attributes => array,
     * ]
     */
    private function getAdditionalTables(): array
    {
        $data = [];

        foreach ($this->defs->getEntityTypeList() as $parentEntityType) {
            $additionalTables = $this->metadata->get([
                'entityDefs', $parentEntityType, 'additionalTables'
            ]);

            if (!$additionalTables || !is_array($additionalTables)) {
                continue;
            }

            foreach ($additionalTables as $tableName => $tableData) {
                $data[$tableName] = array_merge(
                    [
                        'parentEntityType' => $parentEntityType
                    ],
                    $tableData
                );
            }
        }

        return $data;
    }

    /**
     * Is $entityType an additionalTables
     */
    public function isAdditionalTable(string $entityType): bool
    {
        $scope = $this->metadata->get(['scopes', $entityType]);

        if ($scope) {
            return false;
        }

        $additionalTables = $this->getAdditionalTables();

        if (isset($additionalTables[$entityType])) {
            return true;
        }

        return false;
    }

    /**
     * Is $entityType an additionalTables of the CategoryTree entity
     */
    public function isCategoryTreeAdditionalTable(string $entityType): bool
    {
        $scope = $this->metadata->get(['scopes', $entityType]);

        if ($scope) {
            return false;
        }

        $additionalTables = $this->getAdditionalTables();

        $parentEntityType = $additionalTables[$entityType]['parentEntityType'] ?? null;

        if (!$parentEntityType) {
            return false;
        }

        $parentScopeType = $this->metadata->get(['scopes', $parentEntityType, 'type']);

        if (!$parentScopeType || $parentScopeType !== self::SCOPE_CATEGORY_TREE) {
            return false;
        }

        return true;
    }

    /**
     * Get a list of related EntityType for a single EntityType
     */
    public function getRelatedEntityTypeList(string $entityType): array
    {
        if (!$this->defs->hasEntity($entityType)) {
            return [];
        }

        $relationList = $this->defs
            ->getEntity($entityType)
            ->getRelationList();

        $list = [];

        foreach ($relationList as $relationDefs) {
            if ($relationDefs->hasForeignEntityType()) {
                $list[] = $relationDefs->getForeignEntityType();
            }

            if (!$relationDefs->hasRelationshipName()) {
                continue;
            }

            $name = ucfirst($relationDefs->getRelationshipName());

            if (!$this->defs->hasEntity($name)) {
                continue;
            }

            $list[] = $name;
        }

        $list = array_unique($list);

        return $list;
    }

    /**
     * Get a list of related EntityType
     */
    public function getRelatedEntitiesTypeList(array $entityTypeList): array
    {
        $list = [];

        foreach ($entityTypeList as $entityType) {
            $list = array_merge($list, $this->getRelatedEntityTypeList($entityType));
        }

        $list = array_unique($list);

        return array_values($list);
    }

    public function isIdAutoincrement(string $entityType): bool
    {
        $isId = $this->defs
            ->getEntity($entityType)
            ->hasAttribute('id');

        if (!$isId) {
            return false;
        }

        return $this->defs
            ->getEntity($entityType)
            ->getAttribute('id')
            ?->isAutoincrement() ?? false;
    }
}
