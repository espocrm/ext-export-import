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

namespace Espo\Modules\ExportImport\Tools\IdMapping\Processor;

use Espo\Core\Utils\Log;
use Espo\Core\Utils\Metadata;
use Espo\Core\Exceptions\Error;
use Espo\Core\InjectableFactory;
use Espo\Modules\ExportImport\Tools\Processor\Data;
use Espo\Modules\ExportImport\Tools\IdMapping\Params;
use Espo\Modules\ExportImport\Tools\IdMapping\DataProcessor;
use Espo\Modules\ExportImport\Tools\IdMapping\CollectionProcessor;

class Entity
{
    public function __construct(
        private Log $log,
        private Metadata $metadata,
        private InjectableFactory $injectableFactory
    ) {}

    public function run(Params $params): array
    {
        $format = $params->getFormat() ?? 'json';
        $entityType = $params->getEntityType();

        $dataProcessor = $this->createDataProcessor($format);

        $fp = fopen('php://temp', 'w');

        $data = new Data($fp);

        $dataProcessor->process($params, $data);

        $result = $this->createCollectionProcessor($entityType)
            ->process($params, $data);

        fclose($fp);

        return $result;
    }

    private function createDataProcessor(string $format): DataProcessor
    {
        $className = $this->metadata->get([
            'app', 'exportImport', 'idMappingDataProcessorClassNameMap', $format
        ]);

        if (!$className || !class_exists($className)) {
            throw new Error('Class "idMappingDataProcessor" is not found.');
        }

        return $this->injectableFactory->create($className);
    }

    private function createCollectionProcessor(string $entityType): CollectionProcessor
    {
        $className = $this->metadata->get([
            'app', 'exportImport', 'idMappingCollectionClassNameMap', $entityType
        ]);

        if (!$className || !class_exists($className)) {
            throw new Error('Class "idMappingCollection" is not found.');
        }

        return $this->injectableFactory->create($className);
    }
}
