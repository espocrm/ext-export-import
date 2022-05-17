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

namespace Espo\Modules\ExportImport\Tools\Import;

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
    Import\Params,
    Processor\Data as ProcessorData,
};

use RuntimeException;

class EntityImport
{
    /**
     * @var Params
     */
    private $params;

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
