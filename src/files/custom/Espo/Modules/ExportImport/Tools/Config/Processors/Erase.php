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

use Espo\Core\Utils\Config;
use Espo\Core\Utils\Config\ConfigWriter;
use Espo\Core\Utils\File\Manager as FileManager;

use Espo\Modules\ExportImport\Tools\Config\Util;
use Espo\Modules\ExportImport\Tools\Config\Params;
use Espo\Modules\ExportImport\Tools\Config\Processor;
use Espo\Modules\ExportImport\Tools\Erase\Util as EraseUtil;
use Espo\Modules\ExportImport\Tools\Backup\Params as RestoreParams;
use Espo\Modules\ExportImport\Tools\Backup\Processors\Restore as RestoreTool;

class Erase implements Processor
{
    public function __construct(
        private Config $config,
        private ConfigWriter $configWriter,
        private FileManager $fileManager,
        private RestoreTool $restoreTool,
        private Util $util
    ) {}

    public function process(Params $params): void
    {
        $this->processConfig($params);
        $this->processInternalConfig($params);
    }

    private function processConfig(Params $params): void
    {
        $file = $params->getConfigFile();
        $backupFile = $this->getBackupFile($params, Params::CONFIG_FILE);

        $this->processData($params, $file, $backupFile);
    }

    private function processInternalConfig(Params $params): void
    {
        if ($params->getSkipInternalConfig()) {
            return;
        }

        $file = $params->getInternalConfigFile();
        $backupFile = $this->getBackupFile($params, Params::INTERNAL_CONFIG_FILE);

        $this->processData($params, $file, $backupFile);
    }

    private function processData(
        Params $params,
        string $eraseFile,
        string $backupFile
    ): void {
        if (!file_exists($eraseFile) || !file_exists($backupFile)) {
            return;
        }

        $eraseData = EraseUtil::getFileData($eraseFile);
        $backupData = EraseUtil::getFileData($backupFile);

        if (!$eraseData || !$backupData) {
            return;
        }

        $data = $this->getChangedData($eraseData, $backupData);

        if (!$data) {
            return;
        }

        $data = $this->util->applyIgnoreList($params, $data);

        $this->configWriter->setMultiple($data);
        $this->configWriter->save();
    }

    private function getBackupFile(Params $params, string $file): string
    {
        $restoreParams = RestoreParams::create()
            ->withManifest($params->getManifest());

        return $restoreParams->getFilePath($file, $restoreParams::TYPE_CONFIG);
    }

    private function getChangedData(array $eraseData, array $backupData): ?array
    {
        $list = [];

        foreach ($eraseData as $key => $eraseValue) {
            if (!array_key_exists($key, $backupData)) {
                continue;
            }

            $actualValue = $this->config->get($key);
            $backupValue = $backupData[$key];

            if ($actualValue !== $eraseValue) {
                continue;
            }

            if ($actualValue === $backupValue) {
                continue;
            }

            $list[$key] = $backupValue;
        }

        if (empty($list)) {
            return null;
        }

        return $list;
    }
}
