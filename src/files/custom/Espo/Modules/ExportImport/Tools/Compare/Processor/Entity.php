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

namespace Espo\Modules\ExportImport\Tools\Compare\Processor;

use DateTime;
use Espo\Core\Utils\Log;
use Espo\ORM\EntityManager;
use Espo\Core\Utils\Metadata;
use Espo\Core\InjectableFactory;
use Espo\Modules\ExportImport\Tools\Compare\Util;
use Espo\Modules\ExportImport\Tools\Compare\Params;
use Espo\Modules\ExportImport\Tools\Compare\Result;
use Espo\Modules\ExportImport\Tools\Processor\Data;
use Espo\Modules\ExportImport\Tools\Compare\Processor;
use Espo\Modules\ExportImport\Tools\Core\Entity as EntityTool;
use Espo\Modules\ExportImport\Tools\Export\Util as ExportUtil;
use Espo\Modules\ExportImport\Tools\Import\Helpers\Id as IdHelper;
use Espo\Modules\ExportImport\Tools\Processor\Exceptions\Skip as SkipException;

class Entity implements Processor
{
    public function __construct(
        private Log $log,
        private Util $util,
        private Metadata $metadata,
        private EntityTool $entityTool,
        private EntityManager $entityManager,
        private InjectableFactory $injectableFactory,
        private IdHelper $idHelper,
        private ExportUtil $exportUtil
    ) {}

    public function process(Params $params, Data $data): Result
    {
        $entityType = $params->getEntityType();

        $fromDate = $this->getFromDate($params, $data);

        $totalCount = 0;
        $skipCount = 0;
        $createdCount = 0;
        $modifiedCount = 0;
        $deletedCount = 0;

        $data->rewind();

        while (($initRow = $data->readRow()) !== null) {
            $totalCount++;

            if (!$params->isUpdatedType() && !$params->isDeletedType()) {
                $skipCount++;

                continue;
            }

            $entity = null;

            $row = $this->prepareData($params, $initRow);

            $id = $this->idHelper->getEntityId($params, $row);

            if (!$id) {
                $skipCount++;

                continue;
            }

            $entity = $this->entityManager->getEntityById($entityType, $id);

            if (!$entity) {
                $entity = $this->idHelper->getDeletedEntityById($entityType, $id);
            }

            if (!$entity) {
                $skipCount++;

                continue;
            }

            $processHook = $params->getProcessHookClass();

            if ($processHook) {
                try {
                    $processHook->process($params, $entity, $initRow);
                }
                catch (SkipException $e) {
                    $skipCount++;

                    $this->log->debug(
                        'ExportImport [Compare] [Skip]: ' . $e->getMessage()
                    );

                    continue;
                }
            }

            $actualData = $this->util->getActualData($params, $entity, array_keys($row));

            $diffData = $this->util->getDiffData($row, $actualData);

            if (empty($diffData)) {
                $skipCount++;

                continue;
            }

            if (
                $fromDate &&
                (
                    $this->util->isModified($params, $entity, $fromDate) ||
                    $this->util->isModifiedInStream($params, $entity, $fromDate) ||
                    $this->util->isModifiedInActionHistory($params, $entity, $fromDate) ||
                    $this->util->isModifiedInWorkflowLog($params, $entity, $fromDate)
                )
            ) {
                $skipCount++;

                $this->saveInfoData($params, $id, $diffData, $actualData);

                continue;
            }

            if ($this->util->isDeleted($entity)) {
                $deletedCount++;
            } else {
                $modifiedCount++;
            }

            $this->saveEntityData($params, $id, $diffData, $actualData);
        }

        if ($fromDate && $params->isCreatedType()) {
            $collection = $this->util->getCreatedCollection($params, $fromDate);

            if ($collection) {
                foreach ($collection as $entity) {
                    $totalCount++;
                    $createdCount++;

                    $data = $this->exportUtil->getEntityData($params, $entity);

                    $this->saveEntityData($params, $entity->getId(), $data, []);
                }
            }
        }

        return Result::create($entityType)
            ->withTotalCount($totalCount)
            ->withSkipCount($skipCount)
            ->withCreatedCount($createdCount)
            ->withModifiedCount($modifiedCount)
            ->withDeletedCount($deletedCount);
    }

    private function prepareData(Params $params, array $initRow): array
    {
        $entityType = $params->getEntityType();

        $entityDefs = $this->entityManager
            ->getDefs()
            ->getEntity($entityType);

        $row = [];

        foreach ($initRow as $attributeName => $attributeValue) {
            if (!$entityDefs->hasAttribute($attributeName)) {
                continue;
            }

            if ($entityDefs->getAttribute($attributeName)->isNotStorable()) {
                continue;
            }

            if ($params->isAttributeSkipped($attributeName)) {
                continue;
            }

            $row[$attributeName] = $attributeValue;

            $this->processAttribute($params, $row, $attributeName);
        }

        return $row;
    }

    private function processAttribute(
        Params $params,
        array &$row,
        string $attributeName
    ): void {
        $attributeType = $this->entityManager
            ->getDefs()
            ->getEntity($params->getEntityType())
            ->getAttribute($attributeName)
            ?->getType();

        $className = $this->metadata->get([
            'app', 'exportImport', 'compareProcessAttributeClassNameMap', $attributeType
        ]);

        if (!$className) {
            return;
        }

        $this->injectableFactory
            ->create($className)
            ?->process($params, $row, $attributeName);
    }

    /**
     * Get last modified datetime from initial data.
     * This value is used as the start date for comparison.
     */
    private function getFromDate(Params $params, Data $data): ?DateTime
    {
        if ($params->getFromDate()) {
            return $params->getFromDate();
        }

        $lastModifiedAt = null;

        $data->rewind();

        while (($initRow = $data->readRow()) !== null) {
            $row = $this->prepareData($params, $initRow);

            $lastModifiedAt = $this->getLastModifiedAt($lastModifiedAt, $row);
        }

        return $lastModifiedAt;
    }

    /**
     * Compare and get last modified datetime
     */
    private function getLastModifiedAt(?DateTime $lastModifiedAt, array $row): ?DateTime
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

    private function saveEntityData(
        Params $params,
        string $id,
        array $diffData,
        array $actualData
    ): void {
        // TODO: implement
    }

    private function saveInfoData(
        Params $params,
        string $id,
        array $diffData,
        array $actualData
    ): void {
        // TODO: implement
    }
}
