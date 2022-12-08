<?php
/************************************************************************
 * This file is part of Export Import extension for EspoCRM.
 *
 * Export Import extension for EspoCRM.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Modules\ExportImport\Tools\Import\Processor;

use Espo\{
    Core\Di,
    ORM\Entity as OrmEntity,
    Core\Utils\DateTime as DateTimeUtils,
};

use Espo\Modules\ExportImport\Tools\{
    Params as ToolParams,
    Import\Result,
    Import\Processor,
    Processor\Data,
    Import\Params,
    Import\Placeholder\Handler as PlaceholderHandler,
    Processor\Exceptions\Skip as SkipException,
    Processor\Utils as ToolUtils,
};

use Exception;

class Entity implements

    Processor,
    Di\LogAware,
    Di\ConfigAware,
    Di\MetadataAware,
    Di\EntityManagerAware
{
    use Di\LogSetter;
    use Di\ConfigSetter;
    use Di\MetadataSetter;
    use Di\EntityManagerSetter;

    protected $placeholderHandler;

    protected const CREATED_AT_LIST = [
        'createdAt'
    ];

    public function __construct(PlaceholderHandler $placeholderHandler)
    {
        $this->placeholderHandler = $placeholderHandler;
    }

    public function process(Params $params, Data $data): Result
    {
        $data->rewind();

        $entityType = $params->getEntityType();
        $importType = $params->getImportType();

        $failCount = 0;
        $successCount = 0;

        while (($row = $data->readRow()) !== null) {
            $preparedRow = $this->prepareData($params, $row);

            $id = $this->getEntityId($params, $preparedRow);

            if ($id) {
                $entity = $this->entityManager->getEntity($entityType, $id);

                if (!$entity) {
                    $query = $this->entityManager
                        ->getQueryBuilder()
                        ->delete()
                        ->from($entityType)
                        ->where([
                            'id' => $id,
                            'deleted' => true,
                        ])
                        ->build();

                    $this->entityManager
                        ->getQueryExecutor()
                        ->execute($query);
                }
            }

            if (isset($entity) && in_array($importType, [ToolParams::TYPE_CREATE])) {

                continue;
            }

            if (!isset($entity)) {
                if (in_array($importType, [ToolParams::TYPE_UPDATE])) {

                    continue;
                }

                $entity = $this->entityManager->getEntity($entityType);
            }

            $entity->set($preparedRow);

            $processHook = $params->getProcessHookClass();

            if ($processHook) {
                try {
                    $processHook->process($params, $entity, $row);
                }
                catch (SkipException $e) {
                    continue;
                }
            }

            try {
                $this->entityManager->saveEntity($entity, [
                    'noStream' => true,
                    'noNotifications' => true,
                    'import' => true,
                    'silent' => true,
                ]);

                $successCount++;
            }
            catch (Exception $e) {
                $failCount++;

                $this->log->error(
                    "ExportImport [Import]: Error saving the record: " .
                    $e->getMessage() . " at " . $e->getFile() .
                    ":" . $e->getLine()
                );
            }
        }

        return Result::create($entityType)
            ->withFailCount($failCount)
            ->withSuccessCount($successCount);
    }

    private function prepareData(Params $params, array $row)
    {
        $entityType = $params->getEntityType();

        $entityDefs = $this->entityManager
            ->getDefs()
            ->getEntity($entityType);

        $attributeList = $entityDefs->getAttributeNameList();

        foreach ($row as $attributeName => &$attributeValue) {

            if (!in_array($attributeName, $attributeList)) {
                unset($row[$attributeName]);

                continue;
            }

            $attributeType = $entityDefs
                ->getAttribute($attributeName)
                ->getType();

            switch ($attributeType) {
                case OrmEntity::FOREIGN_ID:
                    if ($attributeValue === null) {
                        unset($row[$attributeName]);
                    }
                    break;
            }

            $fieldType = $this->metadata->get([
                'entityDefs', $entityType, 'fields', $attributeName, 'type'
            ]);

            switch ($fieldType) {
                case 'currency':
                    $updateCurrency = $params->getUpdateCurrency();

                    if ($updateCurrency) {
                        $row[$attributeName . 'Currency'] =
                            $this->config->get('defaultCurrency');
                    }
                    break;

                case 'datetime':
                    if (
                        $params->getUpdateCreatedAt() &&
                        in_array($attributeName, self::CREATED_AT_LIST)
                    ) {
                        $attributeValue = DateTimeUtils::getSystemNowString();
                    }
                    break;
            }
        }

        return $this->placeholderHandler->process($params, $row);
    }

    private function getEntityId(Params $params, array $row): ?string
    {
        $id = $row['id'] ?? null;

        $entityType = $params->getEntityType();

        if (ToolUtils::isScopeEntity($this->metadata, $entityType)) {
            return $id;
        }

        return $this->getRelationId($params, $row);
    }

    private function getRelationId(Params $params, array $row): ?string
    {
        $entityType = $params->getEntityType();

        $entityDefs = $this->entityManager
            ->getDefs()
            ->getEntity($entityType);

        $whereClause = [];

        foreach ($entityDefs->getAttributeList() as $attribute) {
            $name = $attribute->getName();
            $type = $attribute->getType();

            switch ($type) {
                case 'foreignId':
                    $value = $row[$name] ?? null;

                    if ($value) {
                        $whereClause[$name] = $value;
                    }
                    break;
            }
        }

        if (!empty($whereClause)) {
            $record = $this->entityManager
                ->getRDBRepository($entityType)
                ->where($whereClause)
                ->findOne();

            if ($record) {
                return $record->id;
            }
        }

        return $row['id'] ?? null;
    }
}
