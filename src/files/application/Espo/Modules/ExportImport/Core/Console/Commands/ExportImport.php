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

namespace Espo\Modules\ExportImport\Core\Console\Commands;

use Espo\Core\{
    Di,
    Console\IO,
    Console\Command,
    Console\Command\Params,
};

use Throwable;

class ExportImport implements

    Command,
    Di\ServiceFactoryAware
{
    use Di\ServiceFactorySetter;

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

        $service = $this->serviceFactory->create('ExportImport');
        $method = 'run' . ucfirst($action);

        if (!method_exists($service, $method)) {
            $io->writeLine(
                "Error: Unknow \"" . $action . "\" action."
            );

            return;
        }

        try {
            $service->$method($options, $io);
        } catch (Throwable $e) {
            $io->writeLine(
                "Error: " . $e->getMessage()
            );

            $GLOBALS['log']->error(
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
