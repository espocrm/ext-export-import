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

namespace Espo\Modules\ExportImport\Tools\Erase\Processor;

use Exception;
use Espo\Core\Utils\Log;
use Espo\ORM\EntityManager;
use Espo\Core\Utils\Metadata;
use Espo\Core\InjectableFactory;
use Espo\Core\ORM\Repository\Option\SaveOption;

use Espo\Modules\ExportImport\Tools\Erase\Params;
use Espo\Modules\ExportImport\Tools\Erase\Result;
use Espo\Modules\ExportImport\Tools\Processor\Data;
use Espo\Modules\ExportImport\Tools\Erase\Processor;
use Espo\Modules\ExportImport\Tools\Core\Entity as EntityTool;
use Espo\Modules\ExportImport\Tools\Import\Helpers\Id as IdHelper;
use Espo\Modules\ExportImport\Tools\Processor\Exceptions\Skip as SkipException;

class Entity implements Processor
{
    public function __construct(
        private Log $log,
        private Metadata $metadata,
        private EntityTool $entityTool,
        private EntityManager $entityManager,
        private InjectableFactory $injectableFactory,
        private IdHelper $idHelper
    ) {}

    public function process(Params $params, Data $data): Result
    {
        $data->rewind();

        $entityType = $params->getEntityType();

        $skipCount = 0;
        $failCount = 0;
        $successCount = 0;

        while (($initRow = $data->readRow()) !== null) {
            $entity = null;

            $row = $this->prepareData($params, $initRow);

            $id = $this->idHelper->getEntityId($params, $row);

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
                        'ExportImport [Erase] [Skip]: ' . $e->getMessage()
                    );

                    continue;
                }
            }

            try {
                $this->entityManager->removeEntity($entity, [
                    'noStream' => true,
                    'noNotifications' => true,
                    SaveOption::SILENT => true,
                    SaveOption::IMPORT => true,
                ]);

                $successCount++;
            }
            catch (Exception $e) {
                $failCount++;

                $this->log->error(
                    "ExportImport [Erase]: Error erasing the record: " .
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
            'app', 'exportImport', 'eraseProcessAttributeClassNameMap', $attributeType
        ]);

        if (!$className) {
            return;
        }

        $this->injectableFactory
            ->create($className)
            ?->process($params, $row, $attributeName);
    }
}
