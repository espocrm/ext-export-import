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

use Espo\Core\Utils\Log;
use Espo\ORM\EntityManager;
use Espo\Core\Utils\Metadata;
use Espo\Core\InjectableFactory;
use Espo\Modules\ExportImport\Tools\Compare\Params;
use Espo\Modules\ExportImport\Tools\Compare\Result;
use Espo\Modules\ExportImport\Tools\Processor\Data;
use Espo\Modules\ExportImport\Tools\Compare\Processor;
use Espo\Modules\ExportImport\Tools\Core\Entity as EntityTool;
use Espo\Modules\ExportImport\Tools\Processor\Utils as ToolUtils;
use Espo\Modules\ExportImport\Tools\Processor\Exceptions\Skip as SkipException;

class Entity implements Processor
{
    public function __construct(
        private Log $log,
        private Metadata $metadata,
        private EntityTool $entityTool,
        private EntityManager $entityManager,
        private InjectableFactory $injectableFactory
    ) {}

    public function process(Params $params, Data $data): Result
    {
        $data->rewind();

        $entityType = $params->getEntityType();

        $skipCount = 0;
        $failCount = 0;
        $successCount = 0;

        while (($initRow = $data->readRow()) !== null) {
            $row = $this->prepareData($params, $initRow);

            $id = $this->getEntityId($params, $row);

            if (!$id) {
                continue;
            }

            $entity = $this->entityManager->getEntityById($entityType, $id);

            if (!$entity) {
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

            // TODO: code
        }

        return Result::create($entityType)
            ->withSkipCount($skipCount)
            ->withFailCount($failCount)
            ->withSuccessCount($successCount);
    }

    private function prepareData(Params $params, array $initRow): array
    {
        $attributeList = $this->entityManager
            ->getDefs()
            ->getEntity($params->getEntityType())
            ->getAttributeNameList();

        $row = $initRow;

        foreach ($row as $attributeName => $attributeValue) {

            if (!in_array($attributeName, $attributeList)) {
                unset($row[$attributeName]);

                continue;
            }

            $this->processAttribute($params, $row, $attributeName);
        }

        return $row;
    }

    private function getEntityId(Params $params, array $row): ?string
    {
        $id = $row['id'] ?? null;

        $entityType = $params->getEntityType();

        if ($id && ToolUtils::isScopeEntity($this->metadata, $entityType)) {
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
}
