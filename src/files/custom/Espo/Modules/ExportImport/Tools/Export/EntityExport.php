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

namespace Espo\Modules\ExportImport\Tools\Export;

use Espo\Core\Acl;
use RuntimeException;
use Espo\ORM\Collection;
use Espo\ORM\EntityManager;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\FieldUtil;
use Espo\Core\Exceptions\Error;
use Espo\Core\Record\ServiceContainer;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\Core\Utils\DateTime as DateTimeUtil;
use Espo\ORM\Query\Part\Where\AndGroupBuilder;
use Espo\Core\FieldProcessing\ListLoadProcessor;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Modules\ExportImport\Tools\Export\Util;
use Espo\Modules\ExportImport\Tools\Export\Params;
use Espo\Modules\ExportImport\Tools\Export\Result;
use Espo\ORM\Query\Part\Condition as WhereCondition;
use Espo\Modules\ExportImport\Tools\Export\ProcessorFactory;
use Espo\Core\FieldProcessing\Loader\Params as LoaderParams;
use Espo\Modules\ExportImport\Tools\Processor\Utils as ToolUtils;
use Espo\Modules\ExportImport\Tools\Processor\Data as ProcessorData;
use Espo\Modules\ExportImport\Tools\Processor\Exceptions\Skip as SkipException;
use Espo\Modules\ExportImport\Tools\Core\Entity as EntityTool;

class EntityExport
{
    private Params $params;

    private ?Collection $collection = null;

    public function __construct(
        private Util $util,
        private ProcessorFactory $processorFactory,
        private SelectBuilderFactory $selectBuilderFactory,
        private ServiceContainer $serviceContainer,
        private Acl $acl,
        private EntityManager $entityManager,
        private Metadata $metadata,
        private FileManager $fileManager,
        private ListLoadProcessor $listLoadProcessor,
        private FieldUtil $fieldUtil,
        private EntityTool $entityTool
    ) {}

    public function setParams(Params $params): self
    {
        $this->params = $params;

        return $this;
    }

    public function setCollection(Collection $collection): self
    {
        $this->collection = $collection;

        return $this;
    }

    /**
     * Run export.
     */
    public function run(): Result
    {
        if (!$this->params) {
            throw new Error("No params set.");
        }

        $params = $this->params;

        $entityType = $params->getEntityType();

        $format = $params->getFormat() ?? 'json';

        $processor = $this->processorFactory->create($format);

        $collection = $this->getCollection($params);

        $attributeList = $this->getAttributeList($params);

        $fieldList = $this->getFieldList($params, $processor);

        if ($fieldList !== null && method_exists($processor, 'addAdditionalAttributes')) {
            $processor->addAdditionalAttributes($entityType, $attributeList, $fieldList);
        }

        $dataResource = fopen('php://temp', 'w');

        $loaderParams = LoaderParams::create()
            ->withSelect($attributeList);

        $recordService = ToolUtils::isScopeEntity($this->metadata, $entityType) ?
            $this->serviceContainer->get($entityType) :
            null;

        $warningList = [];

        $successCount = 0;

        foreach ($collection as $entity) {
            $this->listLoadProcessor->process($entity, $loaderParams);

            if ($recordService && method_exists($recordService, 'loadAdditionalFieldsForExport')) {
                $recordService->loadAdditionalFieldsForExport($entity);
            }

            if (method_exists($processor, 'loadAdditionalFields') && $fieldList !== null) {
                $processor->loadAdditionalFields($entity, $fieldList);
            }

            $row = $this->util->getEntityData($params, $entity, $attributeList);

            $processHook = $params->getProcessHookClass();

            if ($processHook) {
                try {
                    $processHook->process($params, $entity, $row);
                } catch (SkipException $e) {
                    continue;
                }
            }

            $line = base64_encode(serialize($row)) . \PHP_EOL;

            fwrite($dataResource, $line);

            $successCount++;
        }

        $processorData = new ProcessorData($dataResource);

        $stream = $processor->process($params, $processorData);

        if ($stream->getSize() > 0) {
            $result = $this->fileManager->putContents(
                $params->getFile(),
                $stream->getContents()
            );

            if (!$result) {
                throw new Error(
                    "Could not store a file '{$params->getFile()}'."
                );
            }
        }

        fclose($dataResource);

        return Result::create($entityType)
            ->withStoragePath($params->getPath())
            ->withSuccessCount($successCount)
            ->withWarningList($warningList ?? null);
    }

    private function getCollection(Params $params): Collection
    {
        if ($this->collection) {
            return $this->collection;
        }

        $entityType = $params->getEntityType();

        $entityDefs = $this->entityManager
            ->getDefs()
            ->getEntity($entityType);

        $builder = new AndGroupBuilder();

        $defaultWhereItem = $this->entityTool->getCollectionWhereItem($entityType);

        if ($defaultWhereItem) {
            $builder->add($defaultWhereItem);
        }

        // TODO: Change to Espo\Core\Name\Field::MODIFIED_AT when espo min version >= 9.0
        if ($params->getFromDate() && $entityDefs->hasAttribute('modifiedAt')) {
            $after = $params->getFromDate()->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);

            $builder->add(
                WhereCondition::greater(WhereCondition::column('modifiedAt'), $after)
            );
        }

        $collectionClass = $params->getCollectionClass();

        if ($collectionClass) {
            return $collectionClass->getCollection($params, $builder);
        }

        $selectBuilder = $this->entityManager
            ->getRDBRepository($entityType)
            ->where($builder->build());

        if ($entityDefs->hasAttribute('modifiedAt')) {
            $selectBuilder->order('modifiedAt', 'ASC');
        }

        return $selectBuilder
            ->sth()
            ->find();
    }

    private function getAttributeList(Params $params): array
    {
        $list = [];

        $entityType = $params->getEntityType();

        $entityDefs = $this->entityManager
            ->getDefs()
            ->getEntity($entityType);

        $attributeListToSkip = [];

        // TODO: Change when espo min version >= 9.0
        // Attribute::DELETED
        // Field::VERSION_NUMBER
        $attributeListToSkip[] = 'deleted';
        $attributeListToSkip[] = 'versionNumber';

        $seed = $this->entityManager->getNewEntity($entityType);

        $initialAttributeList = $params->getAttributeList();

        if ($params->getAttributeList() === null && $params->getFieldList() !== null) {
            $initialAttributeList = $this->getAttributeListFromFieldList($params);
        }

        if ($params->getAttributeList() === null && $params->getFieldList() === null) {
            $initialAttributeList = $entityDefs->getAttributeNameList();
        }

        foreach ($initialAttributeList as $attribute) {
            if (in_array($attribute, $attributeListToSkip)) {
                continue;
            }

            if (!$this->util->isAttributeAllowedForExport($seed, $attribute, $params->allFields())) {
                continue;
            }

            $list[] = $attribute;
        }

        return $list;
    }

    private function getAttributeListFromFieldList(Params $params): array
    {
        $entityType = $params->getEntityType();

        $fieldList = $params->getFieldList();

        if ($fieldList === null) {
            throw new RuntimeException();
        }

        $attributeList = [];

        foreach ($fieldList as $field) {
            $attributeList = array_merge(
                $attributeList,
                $this->fieldUtil->getAttributeList($entityType, $field)
            );
        }

        return $attributeList;
    }

    private function getFieldList(Params $params, Processor $processor): ?array
    {
        $entityDefs = $this->entityManager
            ->getDefs()
            ->getEntity($params->getEntityType());

        $fieldList = $params->getFieldList();

        if ($params->allFields()) {
            $fieldList = $entityDefs->getFieldNameList();

            array_unshift($fieldList, 'id');
        }

        if ($fieldList === null) {
            return null;
        }

        if (method_exists($processor, 'filterFieldList')) {
            $fieldList = $processor->filterFieldList($params->getEntityType(), $fieldList, $params->allFields());
        }

        return array_values($fieldList);
    }
}
