<?php
/************************************************************************
 * This file is part of Export Import extension for EspoCRM.
 *
 * Export Import extension for EspoCRM.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Modules\ExportImport\Tools\Import;

use Espo\Core\{
    Exceptions\Error,
};

use Espo\Modules\ExportImport\Tools\{
    Import\Params,
    Processor\Data as ProcessorData,
};

class EntityImport
{
    /**
     * @var Params
     */
    private $params;

    private $processorFactory;

    public function __construct(
        ProcessorFactory $processorFactory
    ) {
        $this->processorFactory = $processorFactory;
    }

    public function setParams(Params $params): self
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Run import
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

        $dataResource = fopen('php://temp', 'w');

        $processorData = new ProcessorData($dataResource);

        $processor->process($params, $processorData);

        $processorEntity = $this->processorFactory->createEntityProcessor();
        $result = $processorEntity->process($params, $processorData);

        fclose($dataResource);

        return Result::create($entityType)
            ->withFailCount($result->getFailCount())
            ->withSuccessCount($result->getSuccessCount());
    }
}
