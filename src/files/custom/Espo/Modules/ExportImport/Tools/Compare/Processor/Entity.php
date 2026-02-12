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

namespace Espo\Modules\ExportImport\Tools\Compare\Processor;

use DateTime;
use Espo\Core\Utils\Log;
use Espo\ORM\EntityManager;
use Espo\Core\Utils\Metadata;
use Espo\Core\InjectableFactory;
use Espo\ORM\Type\AttributeType;
use Espo\Core\Utils\Util as CoreUtil;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Modules\ExportImport\Tools\Compare\Util;
use Espo\Modules\ExportImport\Tools\Compare\Params;
use Espo\Modules\ExportImport\Tools\Compare\Result;
use Espo\Modules\ExportImport\Tools\Processor\Data;
use Espo\Modules\ExportImport\Tools\Compare\Processor;
use Espo\Modules\ExportImport\Tools\Params as ToolParams;
use Espo\Modules\ExportImport\Tools\Core\Entity as EntityTool;
use Espo\Modules\ExportImport\Tools\Export\Util as ExportUtil;
use Espo\Modules\ExportImport\Tools\Export\Params as ExportParams;
use Espo\Modules\ExportImport\Tools\Import\Helpers\Id as IdHelper;
use Espo\Modules\ExportImport\Tools\Processor\Exceptions\Skip as SkipException;
use Espo\Modules\ExportImport\Tools\Export\ProcessorFactory as ExportProcessorFactory;

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
        private ExportUtil $exportUtil,
        private FileManager $fileManager,
        private ExportProcessorFactory $exportProcessorFactory
    ) {}

    public function process(Params $params, Data $data): Result
    {
        $entityType = $params->getEntityType();
        $fromDate = $params->getFromDate();

        $fpChangedPrev = fopen('php://temp', 'w');
        $fpChangedActual = fopen('php://temp', 'w');
        $fpSkippedPrev = fopen('php://temp', 'w');
        $fpSkippedActual = fopen('php://temp', 'w');

        $totalCount = 0;
        $skipCount = 0;
        $createdCount = 0;
        $modifiedCount = 0;
        $bothModifiedCount = 0;
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

            $this->log->debug('Compare ['. $params->getEntityType() . '.' . $id . ']');

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

            if ($params->isDeletedType() && $this->util->isDeleted($entity)) {
                $deletedCount++;

                $this->writeData($fpChangedPrev, $id, array_merge(['deleted' => false], $diffData));
                $this->writeData($fpChangedActual, $id, array_merge(['deleted' => true], $actualData));

                continue;
            }

            if ($params->getCompareType() === ToolParams::COMPARE_TYPE_DELETED) {
                continue;
            }

            if (empty($diffData)) {
                $skipCount++;

                continue;
            }

            $actualDataMin = $this->util->minifyData($actualData, array_keys($diffData));

            if (
                $fromDate &&
                (
                    $this->util->isModified($params, $entity, $fromDate) ||
                    $this->util->isModifiedInStream($params, $entity, $fromDate) ||
                    $this->util->isModifiedInActionHistory($params, $entity, $fromDate) ||
                    $this->util->isModifiedInWorkflowLog($params, $entity, $fromDate)
                )
            ) {
                $bothModifiedCount++;

                if (!$params->isInfoLevel()) {
                    continue;
                }

                $this->writeData($fpSkippedPrev, $id, $diffData);
                $this->writeData($fpSkippedActual, $id, $actualDataMin);

                continue;
            }

            $modifiedCount++;

            $this->writeData($fpChangedPrev, $id, $diffData);
            $this->writeData($fpChangedActual, $id, $actualDataMin);
        }

        $determinedFromDate = $this->getDeterminedFromDate($params, $data);

        if ($determinedFromDate && $params->isCreatedType()) {
            $collection = $this->util->getCreatedCollection($params, $determinedFromDate);

            if ($collection) {
                foreach ($collection as $entity) {
                    $totalCount++;
                    $createdCount++;

                    $data = $this->exportUtil->getEntityData($params, $entity);

                    $this->writeData($fpChangedActual, $entity->getId(), $data);
                }
            }
        }

        $this->exportToFile($params, $fpChangedPrev, $params->getChangedPrevPath());
        $this->exportToFile($params, $fpChangedActual, $params->getChangedActualPath());

        if ($params->isInfoLevel()) {
            $this->exportToFile($params, $fpSkippedPrev, $params->getSkippedPrevPath());
            $this->exportToFile($params, $fpSkippedActual, $params->getSkippedActualPath());
        }

        fclose($fpChangedPrev);
        fclose($fpChangedActual);
        fclose($fpSkippedPrev);
        fclose($fpSkippedActual);

        return Result::create($entityType)
            ->withTotalCount($totalCount)
            ->withSkipCount($skipCount)
            ->withCreatedCount($createdCount)
            ->withModifiedCount($modifiedCount)
            ->withBothModifiedCount($bothModifiedCount)
            ->withDeletedCount($deletedCount)
            ->withFromDate($fromDate);
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
    private function getDeterminedFromDate(Params $params, Data $data): ?DateTime
    {
        if ($params->getFromDate()) {
            return $params->getFromDate();
        }

        $lastModifiedAt = null;

        $data->rewind();

        while (($initRow = $data->readRow()) !== null) {
            $row = $this->prepareData($params, $initRow);

            $lastModifiedAt = $this->util->getLastModifiedAt($lastModifiedAt, $row);
        }

        return $lastModifiedAt;
    }

    private function writeData($fp, string $id, array $data): void
    {
        if (empty($data)) {
            return;
        }

        $data = array_merge(['id' => $id], $data);

        $line = base64_encode(serialize($data)) . \PHP_EOL;

        fwrite($fp, $line);
    }

    private function exportToFile(Params $params, $fp, string $path): void
    {
        $basePath = CoreUtil::concatPath($path, ToolParams::PATH_ENTITIES);

        $processor = $this->exportProcessorFactory->create($params->getFormat());

        $fileExtension = $this->metadata->get([
            'app', 'exportImport', 'formatDefs', $params->getFormat(), 'fileExtension'
        ]);

        $dataObj = new Data($fp);

        $exportParams = ExportParams::create($params->getEntityType())
            ->withFormat($params->getFormat())
            ->withFileExtension($fileExtension)
            ->withEntitiesPath($basePath)
            ->withPrettyPrint($params->getPrettyPrint());

        $stream = $processor->process($exportParams, $dataObj);

        if ($stream->getSize() <= 0) {
            return;
        }

        $this->fileManager->putContents($exportParams->getFile(), $stream->getContents());
    }
}
