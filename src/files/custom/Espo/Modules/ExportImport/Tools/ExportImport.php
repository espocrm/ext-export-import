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

namespace Espo\Modules\ExportImport\Tools;

use Espo\Core\{
    Utils\Log,
    Console\IO,
    Utils\Metadata,
    InjectableFactory,
    Exceptions\Error,
};

use Espo\Modules\ExportImport\Tools\Params;

class ExportImport
{
    private Log $log;

    private Metadata $metadata;

    private InjectableFactory $injectableFactory;

    public function __construct(
        Log $log,
        Metadata $metadata,
        InjectableFactory $injectableFactory
    ) {
        $this->log = $log;
        $this->metadata = $metadata;
        $this->injectableFactory = $injectableFactory;
    }

    public function runExport(array $extraParams = [], ?IO $io = null) : void
    {
        $this->runTool(Params::ACTION_EXPORT, $extraParams, $io);
    }

    public function runImport(array $extraParams = [], ?IO $io = null) : void
    {
        $this->runTool(Params::ACTION_IMPORT, $extraParams, $io);
    }

    public function runErase(array $extraParams = [], ?IO $io = null) : void
    {
        $this->runTool(Params::ACTION_ERASE, $extraParams, $io);
    }

    protected function getClass($name): string
    {
        $className = $this->metadata->get([
            'app', 'exportImport', 'toolClassNameMap', ucfirst($name)
        ]);

        if (!class_exists($className)) {
            throw new Error('Tool " ' . $name . '" is not found.');
        }

        return $className;
    }

    protected function runTool(
        string $action,
        array $extraParams = [],
        ?IO $io = null
    ): void
    {
        $className = $this->getClass($action);

        $tool = $this->injectableFactory->create($className);

        $params = $this->createParams(array_merge($extraParams, [
            'action' => $action
        ]), $io);

        $tool->run($params);
    }

    protected function createParams(?array $extraParams = null, ?IO $io = null): Params
    {
        $default = $this->metadata->get(['app', 'exportImport']);

        $params = array_merge($default, [
            'default' => $default
        ]);

        if ($extraParams) {
            $params = array_merge($params, $extraParams);
        }

        $defsSource = $params['defsSource'] ?? null;

        if (!$defsSource) {
            throw new Error('Incorrect "defsSource" option.');
        }

        $params['exportImportDefs'] = $this->metadata->get($defsSource);

        return Params::fromRaw($params)
            ->withIO($io);
    }
}
