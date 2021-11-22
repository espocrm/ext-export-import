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

namespace Espo\Modules\ExportImport\Services;

use Espo\Core\{
    Di,
    Exceptions\Error,
};

class ExportImport implements

    Di\MetadataAware,
    Di\InjectableFactoryAware
{
    use Di\InjectableFactorySetter;
    use Di\MetadataSetter;

    public function runExport() : void
    {
        $className = $this->getClass('export');

        $tool = $this->injectableFactory->create($className);

        $tool->run();
    }

    public function runImport() : void
    {
        $className = $this->getClass('import');

        $tool = $this->injectableFactory->create($className);

        $tool->run();
    }

    public function runErase() : void
    {
        $className = $this->getClass('erase');

        $tool = $this->injectableFactory->create($className);

        $tool->run();
    }

    protected function getClass($name)
    {
        $className = $this->metadata->get([
            'app', 'exportImport', 'toolClassNameMap', ucfirst($name)
        ]);

        if (!class_exists($className)) {
            throw new Error('Tool " ' . $name . '" is not found.');
        }

        return $className;
    }
}
