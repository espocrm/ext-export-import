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

        $format = $params->getFormat() ?? 'json';

        $processor = $this->processorFactory->create($format);

        $dataResource = fopen('php://temp', 'w');

        $processorData = new ProcessorData($dataResource);

        $warningList = [];

        $processor->process($params, $processorData);

        $processorEntity = $this->processorFactory->createEntityProcessor();
        $result = $processorEntity->process($params, $processorData);

        fclose($dataResource);

        if ($params->isCustomEntity() && !$params->getCustomization()) {
            $warningList[] = 'Use --customization option to be able to import custom entities.';
        }

        if (!empty($warningList)) {
            $result = $result->withWarningList($warningList);
        }

        return $result;
    }
}
