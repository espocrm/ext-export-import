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

namespace Espo\Modules\ExportImport\Tools\Core;

use Espo\Core\Utils\Util;
use Espo\Core\Utils\Config as Config;
use Espo\Core\Utils\File\Manager as FileManager;

use Exception;

class Backup
{
    public const BACKUP_PATH = 'data/.backup/export-import';

    public function __construct(
        private Config $config,
        private FileManager $fileManager
    ) {}

    public function clear(string $exportId): bool
    {
        $path = Util::concatPath(self::BACKUP_PATH, $exportId);

        if (!file_exists($path)) {
            return true;
        }

        return $this->fileManager->removeInDir($path);
    }

    public function backupFile(
        string $srcFile,
        string $exportId,
        bool $skipIfExists = false
    ): bool {
        if (!file_exists($srcFile)) {
            return true;
        }

        $destFile = Util::concatPath(self::BACKUP_PATH . '/' . $exportId, $srcFile);

        $dest = pathinfo($destFile, PATHINFO_DIRNAME);

        if ($skipIfExists && file_exists($destFile)) {
            return true;
        }

        try {
            $this->fileManager->copy($srcFile, $dest, false, null, true);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function restoreFile(string $destFile, string $exportId): bool
    {
        $srcFile = Util::concatPath(self::BACKUP_PATH . '/' . $exportId, $destFile);

        if (!file_exists($srcFile)) {
            return true;
        }

        $dest = pathinfo($destFile, PATHINFO_DIRNAME);

        try {
            $this->fileManager->copy($srcFile, $dest, false, null, true);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }
}
