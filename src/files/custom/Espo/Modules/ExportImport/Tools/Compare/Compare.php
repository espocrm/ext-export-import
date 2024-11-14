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

namespace Espo\Modules\ExportImport\Tools\Compare;

use Espo\Core\Exceptions\Error;
use Espo\Modules\ExportImport\Tools\Compare\Params;
use Espo\Modules\ExportImport\Tools\Processor\Data as DataProcessor;
use Espo\Modules\ExportImport\Tools\Compare\ProcessorFactory as Factory;
use Espo\Modules\ExportImport\Tools\Compare\Processor\Entity as EntityProcessor;

class Compare
{
    /**
     * @var Params
     */
    private $params;

    public function __construct(
        private Factory $factory,
        private EntityProcessor $entityProcessor
    ) {}

    public function setParams(Params $params): self
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Run action
     */
    public function run(): Result
    {
        if (!$this->params) {
            throw new Error("No params set.");
        }

        $params = $this->params;

        $format = $params->getFormat() ?? 'json';

        $dataResource = fopen('php://temp', 'w');

        $dataProcessor = new DataProcessor($dataResource);

        $this->factory
            ->create($format)
            ->process($params, $dataProcessor);

        $result = $this->entityProcessor
            ->process($params, $dataProcessor);

        fclose($dataResource);

        return $result;
    }
}
