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

namespace Espo\Modules\ExportImport\Core\Console\Commands;

use Throwable;
use Espo\Core\Utils\Log;
use Espo\Core\Console\IO;
use Espo\Core\Console\Command;
use Espo\Core\Console\Command\Params;
use Espo\Modules\ExportImport\Tools\ExportImport as Tool;

class ExportImport implements Command
{
    private Log $log;

    private Tool $tool;

    public function __construct(Log $log, Tool $tool)
    {
        $this->log = $log;
        $this->tool = $tool;
    }

    public function run(Params $params, IO $io): void
    {
        $action = $params->getArgument(0);
        $options = $params->getOptions();

        $options = array_merge(
            $this->createOptionsFromFlags($params),
            $options
        );

        if (!$action) {
            $io->writeLine(
                "Error: action is not specified."
            );

            $io->writeLine(
                "Available actions: export | import | erase"
            );

            return;
        }

        $method = 'run' . ucfirst($action);

        if (!method_exists($this->tool, $method)) {
            $io->writeLine(
                "Error: Unknown \"" . $action . "\" action."
            );

            return;
        }

        try {
            $this->tool->$method($options, $io);
        } catch (Throwable $e) {
            $io->writeLine("");

            $io->writeLine(
                "Error: " . $e->getMessage()
            );

            $this->log->error(
                'ExportImport Error: ' . $e->getMessage() .
                ' at '. $e->getFile() . ':' . $e->getLine()
            );

            return;
        }

        if (!$params->hasFlag('q')) {
            $io->writeLine("Done.");
        }
    }

    private function createOptionsFromFlags(Params $params): array
    {
        $flagList = $params->getFlagList();

        $options = [];

        foreach ($flagList as $flagName) {
            $options[$flagName] = true;
        }

        return $options;
    }
}
