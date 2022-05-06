<?php
/************************************************************************
 * This file is part of Export Import extension for EspoCRM.
 *
 * Export Import extension for EspoCRM.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * Export Import extension is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Export Import extension is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 ************************************************************************/

namespace Espo\Modules\ExportImport\Tools\Export;

use Espo\Core\{
    Exceptions\Error,
    Utils\Json,
    Select\SelectBuilderFactory,
    Acl,
    Acl\Table,
    Acl\GlobalRestricton,
    Record\ServiceContainer,
    Utils\Metadata,
    Utils\File\Manager as FileManager,
    FieldProcessing\ListLoadProcessor,
    FieldProcessing\Loader\Params as LoaderParams,
    Utils\FieldUtil,
};

use Espo\{
    ORM\Entity,
    ORM\Collection,
    ORM\EntityManager,
};

use Espo\Modules\ExportImport\Tools\{
    Export\Params,
    Processor\Data as ProcessorData,
    Processor\Exceptions\Skip as SkipException,
};

use RuntimeException;

class EntityExport
{
    /**
     * @var Params
     */
    private $params;

    /**
     * @var Collection
     */
    private $collection = null;

    private $processorFactory;

    private $selectBuilderFactory;

    private $serviceContainer;

    private $acl;

    private $entityManager;

    private $metadata;

    private $fileManager;

    private $listLoadProcessor;

    private $fieldUtil;

    public function __construct(
        ProcessorFactory $processorFactory,
        SelectBuilderFactory $selectBuilderFactor,
        ServiceContainer $serviceContainer,
        Acl $acl,
        EntityManager $entityManager,
        Metadata $metadata,
        FileManager $fileManager,
        ListLoadProcessor $listLoadProcessor,
        FieldUtil $fieldUtil
    ) {
        $this->processorFactory = $processorFactory;
        $this->selectBuilderFactory = $selectBuilderFactor;
        $this->serviceContainer = $serviceContainer;
        $this->acl = $acl;
        $this->entityManager = $entityManager;
        $this->metadata = $metadata;
        $this->fileManager = $fileManager;
        $this->listLoadProcessor = $listLoadProcessor;
        $this->fieldUtil = $fieldUtil;
    }

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

        $recordService = $this->isScopeEntity($entityType) ?
            $this->serviceContainer->get($entityType) :
            null;

        $successCount = 0;

        foreach ($collection as $entity) {
            $this->listLoadProcessor->process($entity, $loaderParams);

            if ($recordService && method_exists($recordService, 'loadAdditionalFieldsForExport')) {
                $recordService->loadAdditionalFieldsForExport($entity);
            }

            if (method_exists($processor, 'loadAdditionalFields') && $fieldList !== null) {
                $processor->loadAdditionalFields($entity, $fieldList);
            }

            $row = [];

            foreach ($attributeList as $attribute) {
                $value = $this->getAttributeFromEntity($entity, $attribute);

                if ($this->skipValue($entity, $attribute, $value)) {

                    continue;
                }

                $row[$attribute] = $value;
            }

            $processHook = $params->getProcessHookClass();

            if ($processHook) {
                try {
                    $processHook->process($params, $entity, $row);
                }
                catch (SkipException $e) {
                    continue;
                }
            }

            $line = base64_encode(serialize($row)) . \PHP_EOL;

            fwrite($dataResource, $line);

            $successCount++;
        }

        rewind($dataResource);

        $processorData = new ProcessorData($dataResource);

        $stream = $processor->process($params, $processorData);

        if ($stream->getSize() > 0) {
            $result = $this->fileManager->putContents(
                $params->getFilePath(),
                $stream->getContents()
            );

            if (!$result) {
                throw new Error(
                    "Could not store a file '{$params->getFilePath()}'."
                );
            }
        }

        fclose($dataResource);

        return Result::create($entityType)
            ->withStoragePath($params->getPath())
            ->withSuccessCount($successCount);
    }

    protected function getAttributeFromEntity(Entity $entity, string $attribute)
    {
        $methodName = 'getAttribute' . ucfirst($attribute). 'FromEntity';

        if (method_exists($this, $methodName)) {
            return $this->$methodName($entity);
        }

        $type = $entity->getAttributeType($attribute);

        if ($type === Entity::FOREIGN) {
            $type = $this->getForeignAttributeType($entity, $attribute) ?? $type;
        }

        switch ($type) {
            case Entity::JSON_OBJECT:
                if ($entity->getAttributeParam($attribute, 'isLinkMultipleNameMap')) {
                    break;
                }

                $value = $entity->get($attribute);

                return Json::encode($value, \JSON_UNESCAPED_UNICODE);

            case Entity::JSON_ARRAY:
                if ($entity->getAttributeParam($attribute, 'isLinkMultipleIdList')) {
                    break;
                }

                $value = $entity->get($attribute);

                if (is_array($value)) {
                    return Json::encode($value, \JSON_UNESCAPED_UNICODE);
                }

                return null;

            case Entity::PASSWORD:
                return null;
        }

        return $entity->get($attribute);
    }

    private function getForeignAttributeType(Entity $entity, string $attribute): ?string
    {
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
            return self::VARCHAR;
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

    protected function checkAttributeIsAllowedForExport(
        Entity $entity,
        string $attribute,
        bool $exportAllFields = false
    ): bool {

        if ($entity->getAttributeParam($attribute, 'notExportable')) {
            return false;
        }

        if (!$exportAllFields) {
            return true;
        }

        if ($entity->getAttributeParam($attribute, 'isLinkMultipleIdList')) {
            return false;
        }

        if ($entity->getAttributeParam($attribute, 'isLinkMultipleNameMap')) {
            return false;
        }

        if ($entity->getAttributeParam($attribute, 'isLinkStub')) {
            return false;
        }

        $attributeType = $entity->getAttributeParam($attribute, 'type');

        switch ($attributeType) {
            case 'foreign':
                return false;
                break;
        }

        return true;
    }

    private function getCollection(Params $params): Collection
    {
        if ($this->collection) {
            return $this->collection;
        }

        $entityType = $params->getEntityType();

        $searchParams = $params->getSearchParams();

        $builder = $this->selectBuilderFactory
            ->create()
            ->from($entityType)
            ->withSearchParams($searchParams);

        if ($params->applyAccessControl()) {
            $builder->withStrictAccessControl();
        }

        $query = $builder->build();

        $collectionClass = $params->getCollectionClass();

        if ($collectionClass) {
            return $collectionClass->getCollection($query);
        }

        return $this->entityManager
            ->getRepository($entityType)
            ->clone($query)
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

        $attributeListToSkip = $params->applyAccessControl() ?
            $this->acl->getScopeForbiddenAttributeList($entityType, Table::ACTION_READ) :
            $this->acl->getScopeRestrictedAttributeList($entityType, [
                GlobalRestricton::TYPE_FORBIDDEN,
                GlobalRestricton::TYPE_INTERNAL,
            ]);

        $attributeListToSkip[] = 'deleted';

        $seed = $this->entityManager->getEntity($entityType);

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

            if (!$this->checkAttributeIsAllowedForExport($seed, $attribute, $params->allFields())) {
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

        foreach ($fieldList as $i => $field) {
            if ($field === 'id') {
                continue;
            }

            if ($entityDefs->getField($field)->getParam('exportDisabled')) {
                unset($fieldList[$i]);
            }
        }

        if (method_exists($processor, 'filterFieldList')) {
            $fieldList = $processor->filterFieldList($params->getEntityType(), $fieldList, $params->allFields());
        }

        return array_values($fieldList);
    }

    protected function isScopeEntity(string $scope): bool
    {
        return (bool) $this->metadata->get(['scopes', $scope, 'entity']);
    }

    protected function skipValue(Entity $entity, string $attribute, $value)
    {
        $type = $entity->getAttributeType($attribute);

        switch ($type) {

            case Entity::BOOL:
            case Entity::TEXT:
            case Entity::VARCHAR:
            case Entity::FOREIGN_ID:
                if ($value === null) {
                    return true;
                }
                break;

        }

        return false;
    }
}
