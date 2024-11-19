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

namespace Espo\Modules\ExportImport\Tools\Compare;

use DateTime;
use Espo\ORM\Entity;
use Espo\Entities\Note;
use Espo\ORM\Collection;
use Espo\ORM\EntityManager;
use Espo\Entities\ActionHistoryRecord;
use Espo\Core\Utils\DateTime as DateTimeUtil;
use Espo\Core\FieldProcessing\ListLoadProcessor;
use Espo\Modules\ExportImport\Tools\Compare\Params;
use Espo\Core\FieldProcessing\Loader\Params as LoaderParams;
use Espo\Modules\ExportImport\Tools\Export\Util as ExportUtil;

class Util
{
    private const ENTITY_WORKFLOW_LOG = 'WorkflowLogRecord';

    public function __construct(
        private ExportUtil $exportUtil,
        private EntityManager $entityManager,
        private ListLoadProcessor $listLoadProcessor
    ) {}

    /**
     * Get actual data for a record.
     */
    public function getActualData(Params $params, Entity $entity, array $attributeList): array
    {
        $loaderParams = LoaderParams::create()
            ->withSelect($attributeList);

        $this->listLoadProcessor->process($entity, $loaderParams);

        $row = [];

        foreach ($attributeList as $attributeName) {
            $row[$attributeName] = $this->exportUtil
                ->getAttributeValue($params, $entity, $attributeName);
        }

        return $row;
    }

    public function getCreatedData(Params $params, Entity $entity, array $attributeList): array
    {
        $loaderParams = LoaderParams::create()
            ->withSelect($attributeList);

        $this->listLoadProcessor->process($entity, $loaderParams);

        $row = [];

        foreach ($attributeList as $attributeName) {
            $row[$attributeName] = $this->exportUtil
                ->getAttributeValue($params, $entity, $attributeName);
        }

        return $row;
    }

    public function getCreatedCollection(Params $params, DateTime $fromDate): ?Collection
    {
        $entityType = $params->getEntityType();

        $entityDefs = $this->entityManager
            ->getDefs()
            ->getEntity($entityType);

        if (!$entityDefs->hasAttribute('createdAt')) {
            return null;
        }

         return $this->entityManager
            ->getRDBRepository($entityType)
            ->where([
                'createdAt>' => $fromDate->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT),
            ])
            ->order('createdAt', 'ASC')
            ->find();
    }

    /**
     * Get diff data for a record.
     */
    public function getDiffData(array $master, array $slave): array
    {
        $data = [];

        foreach ($master as $attributeName => $value) {
            $slaveValue = $slave[$attributeName] ?? null;

            if ($value === $slaveValue) {
                continue;
            }

            $data[$attributeName] = $value;
        }

        return $data;
    }

    /**
     * Compare and get last modified datetime
     */
    public function getLastModifiedAt(?DateTime $lastModifiedAt, array $row): ?DateTime
    {
        $modifiedAt = $this->getModifiedAt($row);

        if (!$modifiedAt) {
            return $lastModifiedAt;
        }

        if (!$lastModifiedAt) {
            return $modifiedAt;
        }

        if ($modifiedAt > $lastModifiedAt) {
            return $modifiedAt;
        }

        return $lastModifiedAt;
    }

    private function getModifiedAt(array $row): ?DateTime
    {
        $modifiedAt = $row['modifiedAt'] ?? null;

        if (!$modifiedAt) {
            return null;
        }

        return new DateTime($modifiedAt);
    }

    /**
     * Check if entity was modified by modifiedAt attribute
     */
    public function isModified(
        Params $params,
        Entity $entity,
        DateTime $fromDate
    ): bool {
        if ($params->getSkipModifiedAt()) {
            return false;
        }

        if (!$entity->hasAttribute('modifiedAt')) {
            return false;
        }

        $modifiedAtValue = $entity->get('modifiedAt');

        if (!$modifiedAtValue) {
            return false;
        }

        $modifiedAt = new DateTime($modifiedAtValue);

        if (!$modifiedAt) {
            return false;
        }

        return $modifiedAt >= $fromDate;
    }

    /**
     * Check if an entity has a modified note in stream
     */
    public function isModifiedInStream(
        Params $params,
        Entity $entity,
        DateTime $fromDate
    ): bool {
        if ($params->getSkipStream()) {
            return false;
        }

        $last = $this->entityManager
            ->getRDBRepository(Note::ENTITY_TYPE)
            ->where([
                'action' => [
                    Note::TYPE_UPDATE,
                    Note::TYPE_CREATE,
                ],
                'createdAt>' => $fromDate->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT),
                'parentType' => $entity->getEntityType(),
                'parentId' => $entity->getId(),
                'userId!=' => 'system',
            ])
            ->order('createdAt', 'DESC')
            ->findOne();

        if (!$last) {
            return false;
        }

        return true;
    }

    /**
     * Check if an entity was modified in action history
     */
    public function isModifiedInActionHistory(
        Params $params,
        Entity $entity,
        DateTime $fromDate
    ): bool {
        if ($params->getSkipActionHistory()) {
            return false;
        }

        $last = $this->entityManager
            ->getRDBRepository(ActionHistoryRecord::ENTITY_TYPE)
            ->where([
                'action' => [
                    ActionHistoryRecord::ACTION_CREATE,
                    ActionHistoryRecord::ACTION_UPDATE,
                ],
                'createdAt>' => $fromDate->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT),
                'targetType' => $entity->getEntityType(),
                'targetId' => $entity->getId(),
                'userId!=' => 'system',
            ])
            ->order('createdAt', 'DESC')
            ->findOne();

        if (!$last) {
            return false;
        }

        return true;
    }

    /**
     * Check if an entity was modified in a workflow log
     */
    public function isModifiedInWorkflowLog(
        Params $params,
        Entity $entity,
        DateTime $fromDate
    ): bool {
        if ($params->getSkipWorkflowLog()) {
            return false;
        }

        $entityDefs = $this->entityManager->getDefs();

        if (!$entityDefs->hasEntity(self::ENTITY_WORKFLOW_LOG)) {
            return false;
        }

        $last = $this->entityManager
            ->getRDBRepository(self::ENTITY_WORKFLOW_LOG)
            ->where([
                'createdAt>' => $fromDate->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT),
                'targetType' => $entity->getEntityType(),
                'targetId' => $entity->getId(),
                'createdById!=' => 'system',
            ])
            ->order('createdAt', 'DESC')
            ->findOne();

        if (!$last) {
            return false;
        }

        return true;
    }

    public function isDeleted(Entity $entity): bool
    {
        if (!$entity->hasAttribute('deleted')) {
            return false;
        }

        return $entity->get('deleted');
    }

    public function minifyData(array $array, array $keyList): array
    {
        return array_intersect_key($array, array_flip($keyList));
    }
}
