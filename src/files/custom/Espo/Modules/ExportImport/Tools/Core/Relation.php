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
use Espo\ORM\Defs\RelationDefs;
use Espo\Core\ORM\EntityManager;

class Relation
{
    public function __construct(
        private Defs $defs,
        private Metadata $metadata,
        private EntityManager $entityManager
    ) {}

    /**
     * Check if foreignId is related to an Entity
     */
    public function isAttributeRelatedTo(
        string $entityType,
        string $attributeName,
        string $foreignEntityType
    ): bool {
        if ($entityType == $foreignEntityType) {
            return true;
        }

        if ($this->isMiddleRelationEntity($entityType)) {
            return $this->isManyManyHasForeignEntity(
                $entityType,
                $attributeName,
                $foreignEntityType
            );
        }

        $isCustomRelation = $this->isCustomForeignEntity(
            $entityType,
            $attributeName,
            $foreignEntityType
        );

        if ($isCustomRelation) {
            return true;
        }

        $relationDefs = $this->getRelationDefsByAttributeName(
            $entityType,
            $attributeName
        );

        if (!$relationDefs) {
            return false;
        }

        if ($relationDefs->isBelongsToParent()) {
            return $this->isBelongsToParentHasForeignEntity(
                $entityType,
                $relationDefs,
                $foreignEntityType
            );
        }

        if (!$relationDefs->hasForeignEntityType()) {
            return false;
        }

        if ($relationDefs->getForeignEntityType() === $foreignEntityType) {
            return true;
        }

        return false;
    }

    private function isMiddleRelationEntity(string $entityType): bool
    {
        $isSkipRebuild = $this->entityManager
            ->getDefs()
            ->getEntity($entityType)
            ->getParam('skipRebuild');

        if ($isSkipRebuild) {
            return true;
        }

        $scope = $this->metadata->get(['scopes', $entityType]);

        if (!$scope) {
            return true;
        }

        return false;
    }

    /**
     * Get RelationDefs by an attribute name
     */
    public function getRelationDefsByAttributeName(
        string $entityType,
        string $attributeName
    ): ?RelationDefs {
        $relationList = $this->entityManager
            ->getDefs()
            ->getEntity($entityType)
            ->getRelationList();

        foreach ($relationList as $relationDefs) {
            if (!$relationDefs->hasKey()) {
                continue;
            }

            if ($relationDefs->getKey() != $attributeName) {
                continue;
            }

            return $relationDefs;
        }

        return null;
    }

    /**
     * Get a link name by an attribute name in order
     * to get the 'entityDefs.ENTITY.links.LINK_NAME'
     */
    public function getLinkNameByAttributeName(
        string $entityType,
        string $attributeName
    ): ?string {
        $relationDefs = $this->getRelationDefsByAttributeName(
            $entityType,
            $attributeName
        );

        if (!$relationDefs) {
            return null;
        }

        return $relationDefs->getName();
    }

    private function isBelongsToParentHasForeignEntity(
        string $entityType,
        RelationDefs $relationDefs,
        string $foreignEntityType
    ): bool {
        $entityTypeList = $this->metadata->get([
            'entityDefs', $entityType, 'fields', $relationDefs->getName(), 'entityList'
        ]);

        if (!$entityTypeList) {
            $entityTypeList = $this->metadata->get([
                'entityDefs', $entityType, 'links', $relationDefs->getName(), 'entityList'
            ]);
        }

        if (!$entityTypeList || !is_array($entityTypeList)) {
            return true;
        }

        if (in_array($foreignEntityType, $entityTypeList)) {
            return true;
        }

        return false;
    }

    private function isManyManyHasForeignEntity(
        string $entityType,
        string $attributeName,
        string $foreignEntityType
    ): bool {
        $entityTypeList = $this->getManyManyEntityTypeList(
            $entityType,
            $attributeName
        );

        if (in_array($foreignEntityType, $entityTypeList)) {
            return true;
        }

        return false;
    }

    private function isCustomForeignEntity(
        string $entityType,
        string $attributeName,
        string $foreignEntityType
    ): bool {
        $attributeList = $this->metadata->get([
            'exportImportDefs', $foreignEntityType, 'foreignAttributes', $entityType
        ]);

        if (!$attributeList) {
            return false;
        }

        if (!in_array($attributeName, $attributeList)) {
            return false;
        }

        return true;
    }

    private function getManyManyEntityTypeList(
        string $entityType,
        string $attributeName
    ): array {
        $relationName = lcfirst($entityType);

        $list = [];

        $entityTypeList = $this->entityManager
            ->getDefs()
            ->getEntityList();

        foreach ($entityTypeList as $entityDefs) {
            foreach ($entityDefs->getRelationList() as $relationDefs) {
                if (!$relationDefs->isManyToMany()) {
                    continue;
                }

                if (!$relationDefs->hasRelationshipName()) {
                    continue;
                }

                if (!$relationDefs->hasForeignEntityType()) {
                    continue;
                }

                if ($relationDefs->getRelationshipName() != $relationName) {
                    continue;
                }

                $parentEntityType = $relationDefs->getConditions()['entityType'] ?? null;

                if ($parentEntityType) {
                    $list[] = $parentEntityType;

                    continue;
                }

                $list[] = $relationDefs->getForeignEntityType();
            }
        }

        return $list;
    }
}
