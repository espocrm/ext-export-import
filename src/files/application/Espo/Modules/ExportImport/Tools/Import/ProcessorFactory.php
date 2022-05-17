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
    InjectableFactory,
    Utils\Metadata,
};

use Espo\Modules\ExportImport\Tools\Import\{
    Processor\Entity as ProcessorEntity
};

use LogicException;

class ProcessorFactory
{
    private $injectableFactory;

    private $metadata;

    public function __construct(InjectableFactory $injectableFactory, Metadata $metadata)
    {
        $this->injectableFactory = $injectableFactory;
        $this->metadata = $metadata;
    }

    public function create(string $format): Processor
    {
        if (!in_array($format, $this->metadata->get(['app', 'exportImport', 'formatList']))) {
            throw new LogicException("Not supported export format '{$format}'.");
        }

        $className = $this->metadata->get(['app', 'exportImport', 'importProcessorClassNameMap', $format]);

        if (!$className) {
            throw new LogicException("No implementation for format '{$format}'.");
        }

        return $this->injectableFactory->create($className);
    }

    public function createEntityProcessor()
    {
        return $this->injectableFactory->create(ProcessorEntity::class);
    }
}
