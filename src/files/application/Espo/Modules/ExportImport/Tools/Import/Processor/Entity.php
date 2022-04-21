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

namespace Espo\Modules\ExportImport\Tools\Import\Processor;

use Espo\Core\Di;

use Espo\Modules\ExportImport\Tools\Import\{
    Processor\Data,
    Processor\Params,
    Placeholder\Handler as PlaceholderHandler
};

use Exception;
use Psr\Http\Message\StreamInterface;

class Entity implements

    Processor,
    Di\EntityManagerAware,
    Di\InjectableFactoryAware
{
    use Di\EntityManagerSetter;
    Use Di\InjectableFactorySetter;

    protected $placeholderHandler;

    public function __construct()
    {
        $this->placeholderHandler = $this->injectableFactory->create(
            PlaceholderHandler::class
        );
    }

    public function process(Params $params, Data $data): StreamInterface
    {
        $data->rewind();

        $entityType = $params->getEntityType();

        while (($row = $data->readRow()) !== null) {
            $preparedRow = $this->prepareData($params, $row);

            $id = $row['id'] ?? null;

            if ($id) {
                $entity = $this->entityManager->getEntity($entityType, $id);

                if (!isset($entity)) {
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

            if (!isset($entity)) {
                $entity = $this->entityManager->getEntity($entityType);
            }

            $entity->set($preparedRow);

            try {
                $this->entityManager->saveEntity($entity, [
                    'noStream' => true,
                    'noNotifications' => true,
                    'import' => true,
                    'silent' => true,
                ]);
            }
            catch (Exception $e) {
                $this->log->error(
                    "ExportImport [Import]: Error saving the record: " .
                    $e->getMessage()
                );
            }
        }
    }

    private function prepareData(Params $params, array $row)
    {
        $entityDefs = $this->entityManager
            ->getDefs()
            ->getEntity($params->getEntityType());

        $attributeList = $entityDefs->getAttributeNameList();

        foreach ($row as $fieldName => $fieldValue) {
            if (!in_array($fieldName, $attributeList)) {
                unset($row[$fieldName]);
            }
        }

        return $this->placeholderHandler->process($params, $row);
    }
}
