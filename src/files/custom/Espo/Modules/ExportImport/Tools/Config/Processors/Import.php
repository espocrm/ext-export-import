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

namespace Espo\Modules\ExportImport\Tools\Config\Processors;

use Espo\Core\Utils\Json;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Config\ConfigWriter;
use Espo\Core\Utils\File\Manager as FileManager;

use Espo\Modules\ExportImport\Tools\Config\Params;
use Espo\Modules\ExportImport\Tools\Config\Processor;

class Import implements Processor
{
    public function __construct(
        private Config $config,
        private ConfigWriter $configWriter,
        private FileManager $fileManager,
    ) {}

    public function process(Params $params): void
    {
        $this->processConfig($params);
        $this->processInternalConfig($params);
    }

    private function processConfig(Params $params): void
    {
        $file = $params->getConfigFile();

        $this->processData($params, $file);
    }

    private function processInternalConfig(Params $params): void
    {
        if ($params->getSkipInternalConfig()) {
            return;
        }

        $file = $params->getInternalConfigFile();

        $this->processData($params, $file);
    }

    private function applySkipPassword(Params $params, array $configData): array
    {
        if (!$params->getSkipPassword()) {
            return $configData;
        }

        $ignoreList = Params::PASSWORD_PARAM_LIST;

        return array_diff_key($configData, array_flip($ignoreList));
    }

    private function processData(Params $params, string $file): void
    {
        if (!file_exists($file)) {
            return;
        }

        $contents = $this->fileManager->getContents($file);

        $data = get_object_vars(
            Json::decode($contents)
        );

        if (empty($data)) {
            return;
        }

        $data = $this->applySkipPassword($params, $data);

        $this->configWriter->setMultiple($data);
        $this->configWriter->save();
    }
}
