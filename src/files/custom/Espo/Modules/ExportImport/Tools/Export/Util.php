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

namespace Espo\Modules\ExportImport\Tools\Export;

use Espo\ORM\Entity;
use Espo\ORM\BaseEntity;
use Espo\Core\Utils\Json;
use Espo\ORM\EntityManager;

class Util
{
    public function __construct(
        private EntityManager $entityManager
    ) {}

    protected function getAttributeValue(
        Params $params,
        Entity $entity,
        string $attributeName
    ) {
        /** @var BaseEntity $entity */

        $methodName = 'getAttribute' . ucfirst($attributeName) . 'FromEntity';

        if (method_exists($this, $methodName)) {
            return $this->$methodName($entity);
        }

        $type = $entity->getAttributeType($attributeName);

        if ($type === Entity::FOREIGN) {
            $type = $this->getForeignAttributeType($entity, $attributeName) ?? $type;
        }

        switch ($type) {
            case Entity::JSON_OBJECT:
                if ($entity->getAttributeParam($attributeName, 'isLinkMultipleNameMap')) {
                    break;
                }

                $value = $entity->get($attributeName);

                if (!empty($value)) {
                    return Json::encode($value, JSON_UNESCAPED_UNICODE);
                }

                return null;

            case Entity::JSON_ARRAY:
                if ($entity->getAttributeParam($attributeName, 'isLinkMultipleIdList')) {
                    break;
                }

                $value = $entity->get($attributeName);

                if (is_array($value)) {
                    return Json::encode($value, JSON_UNESCAPED_UNICODE);
                }

                return null;

            case Entity::PASSWORD:
                if (method_exists($params, 'getSkipPassword') && $params->getSkipPassword()) {
                    return null;
                }
                break;
        }

        return $entity->get($attributeName);
    }

    private function getForeignAttributeType(Entity $entity, string $attribute): ?string
    {
        /** @var BaseEntity $entity */

        $defs = $this->entityManager->getDefs();

        $entityDefs = $defs->getEntity($entity->getEntityType());

        $relation = $entity->getAttributeParam($attribute, 'relation');
        $foreign = $entity->getAttributeParam($attribute, 'foreign');

        if (!$relation) {
            return null;
        }

        if (!$foreign) {
            return null;
        }

        if (!is_string($foreign)) {
            return Entity::VARCHAR;
        }

        if (!$entityDefs->getRelation($relation)->hasForeignEntityType()) {
            return null;
        }

        $entityType = $entityDefs->getRelation($relation)->getForeignEntityType();

        if (!$defs->hasEntity($entityType)) {
            return null;
        }

        $foreignEntityDefs = $defs->getEntity($entityType);

        if (!$foreignEntityDefs->hasAttribute($foreign)) {
            return null;
        }

        return $foreignEntityDefs->getAttribute($foreign)->getType();
    }

    public function isAttributeAllowedForExport(
        Entity $entity,
        string $attributeName,
        bool $exportAllFields = false
    ): bool {
        if (!$exportAllFields) {
            return true;
        }

        /** @var BaseEntity $entity */

        if ($entity->getAttributeParam($attributeName, 'isLinkMultipleIdList')) {
            return false;
        }

        if ($entity->getAttributeParam($attributeName, 'isLinkMultipleNameMap')) {
            return false;
        }

        if ($entity->getAttributeParam($attributeName, 'isLinkStub')) {
            return false;
        }

        $type = $entity->getAttributeParam($attributeName, 'type');

        switch ($type) {
            case 'foreign':
                return false;
        }

        if ($entity->getAttributeParam($attributeName, 'notStorable')) {
            $fieldType = $entity->getAttributeParam($attributeName, 'fieldType') ?? $type;

            switch ($fieldType) {
                case 'jsonArray':
                case 'jsonObject':
                case 'linkParent':
                    return false;
            }
        }

        return true;
    }
}
