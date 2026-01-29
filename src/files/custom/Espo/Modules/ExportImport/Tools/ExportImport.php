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

use Espo\Core\Utils\Log;
use Espo\Core\Console\IO;
use Espo\Core\Utils\Metadata;
use Espo\Core\Exceptions\Error;
use Espo\Core\InjectableFactory;
use Espo\Modules\ExportImport\Tools\Params;
use Espo\Modules\ExportImport\Tools\Core\System as SystemUtil;

class ExportImport
{
    public function __construct(
        private Log $log,
        private Metadata $metadata,
        private SystemUtil $systemUtil,
        private InjectableFactory $injectableFactory
    ) {}

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

    public function runCompare(array $extraParams = [], ?IO $io = null) : void
    {
        $this->runTool(Params::ACTION_COMPARE, $extraParams, $io);
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
    ): void {
        $className = $this->getClass($action);

        $tool = $this->injectableFactory->create($className);

        $params = $this->createParams(array_merge($extraParams, [
            'action' => $action
        ]), $io);

        if ($this->systemUtil->isEspoRootDirectory($params->getPath())) {
            throw new Error(
                'The path "' . $params->getPath() . '" cannot be ' .
                'the EspoCRM directory itself.'
            );
        }

        if ($this->systemUtil->isEspoRootDirectory($params->getResultPath())) {
            throw new Error(
                'The result path "' . $params->getResultPath() . '" cannot be ' .
                'the EspoCRM directory itself.'
            );
        }

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
