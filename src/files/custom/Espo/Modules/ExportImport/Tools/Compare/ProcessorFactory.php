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

use LogicException;
use Espo\Core\Utils\Metadata;
use Espo\Core\InjectableFactory;
use Espo\Modules\ExportImport\Tools\Compare\Processor;

class ProcessorFactory
{
    public function __construct(
        private InjectableFactory $injectableFactory,
        private Metadata $metadata
    ) {}

    public function create(string $format): Processor
    {
        if (!in_array($format, $this->metadata->get(['app', 'exportImport', 'formatList']))) {
            throw new LogicException("Not supported export format '{$format}'.");
        }

        $className = $this->metadata->get([
            'app', 'exportImport', 'compareDataProcessorClassNameMap', $format
        ]);

        if (!$className) {
            throw new LogicException("No implementation for format '{$format}'.");
        }

        return $this->injectableFactory->create($className);
    }
}
