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

namespace Espo\Modules\ExportImport\Tools\Import\Processor;

use Espo\Core\Di;
use Espo\Core\ORM\Repository\Option\SaveOption;

use Espo\Modules\ExportImport\Tools\Import\Result;
use Espo\Modules\ExportImport\Tools\Import\Processor;
use Espo\Modules\ExportImport\Tools\Processor\Data;
use Espo\Modules\ExportImport\Tools\Import\Params;
use Espo\Modules\ExportImport\Tools\Params as ToolParams;
use Espo\Modules\ExportImport\Tools\Processor\Utils as ToolUtils;
use Espo\Modules\ExportImport\Tools\Import\Placeholder\Handler as PlaceholderHandler;
use Espo\Modules\ExportImport\Tools\Processor\Exceptions\Skip as SkipException;

use Exception;

class Entity implements

    Processor,
    Di\LogAware,
    Di\ConfigAware,
    Di\MetadataAware,
    Di\EntityManagerAware,
    Di\InjectableFactoryAware
{
    use Di\LogSetter;
    use Di\ConfigSetter;
    use Di\MetadataSetter;
    use Di\EntityManagerSetter;
    use Di\InjectableFactorySetter;

    protected $placeholderHandler;

    public function __construct(PlaceholderHandler $placeholderHandler)
    {
        $this->placeholderHandler = $placeholderHandler;
    }

    public function process(Params $params, Data $data): Result
    {
        $data->rewind();

        $entityType = $params->getEntityType();
        $importType = $params->getImportType();

        $skipCount = 0;
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
                    $skipCount++;

                    $this->log->warning(
                        'ExportImport [Import] [Skip]: ' . $e->getMessage()
                    );

                    continue;
                }
            }

            try {
                $this->entityManager->saveEntity($entity, [
                    'noStream' => true,
                    'noNotifications' => true,
                    SaveOption::IMPORT => true,
                    SaveOption::SILENT => true,
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
            ->withSkipCount($skipCount)
            ->withFailCount($failCount)
            ->withSuccessCount($successCount);
    }

    private function prepareData(Params $params, array $row)
    {
        $attributeList = $this->entityManager
            ->getDefs()
            ->getEntity($params->getEntityType())
            ->getAttributeNameList();

        foreach ($row as $attributeName => $attributeValue) {

            if (!in_array($attributeName, $attributeList)) {
                unset($row[$attributeName]);

                continue;
            }

            $this->processAttribute($params, $row, $attributeName);
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
                return $record->getId();
            }
        }

        return $row['id'] ?? null;
    }

    private function processAttribute(
        Params $params,
        array $row,
        string $attributeName
    ): void {
        $attributeType = $this->entityManager
            ->getDefs()
            ->getEntity($params->getEntityType())
            ->getAttribute($attributeName)
            ?->getType();

        $className = $this->metadata->get([
            'app', 'exportImport', 'importProcessAttributeClassNameMap', $attributeType
        ]);

        if (!$className) {
            return;
        }

        $this->injectableFactory
            ->create($className)
            ?->process($params, $row, $attributeName);
    }
}
