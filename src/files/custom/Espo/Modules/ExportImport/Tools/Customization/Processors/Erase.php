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

namespace Espo\Modules\ExportImport\Tools\Customization\Processors;

use Espo\Core\Utils\Log;
use Espo\Core\Utils\Util;
use Espo\Core\Utils\Json;
use Espo\Core\Utils\File\Manager as FileManager;

use Espo\Modules\ExportImport\Tools\Customization\Params;
use Espo\Modules\ExportImport\Tools\Customization\Service;
use Espo\Modules\ExportImport\Tools\Customization\Processor;
use Espo\Modules\ExportImport\Tools\Processor\Utils as ToolUtils;

use Espo\Modules\ExportImport\Tools\IdMapping\IdReplacer;
use Espo\Modules\ExportImport\Tools\Core\Backup as BackupTool;

use Exception;

class Erase implements Processor
{
    private const FILE_JSON = 'json';

    public function __construct(
        private Log $log,
        private Service $service,
        private FileManager $fileManager,
        private IdReplacer $idReplacer,
        private BackupTool $backupTool
    ) {}

    public function process(Params $params): void
    {
        $src = $params->getCustomizationPath();
        $exportId = $params->getManifest()->getId();

        $fileList = $this->service->getCopyFileList($params, $src);

        foreach ($fileList as $file) {
            $sourceFile = Util::concatPath($src, $file);

            $fileExtension = pathinfo($file, PATHINFO_EXTENSION);

            if ($fileExtension != self::FILE_JSON) {
                if ($this->backupTool->hasFile($file, $exportId)) {
                    $this->backupTool->restoreFile($file, $exportId);

                    continue;
                }

                $this->removeFile($file);

                continue;
            }

            $this->clearContent($params, $sourceFile, $file);
        }
    }
    private function clearContent(Params $params, string $srcFile, string $destFile): bool
    {
        $this->log->debug(
            "ExportImport [Customization.Erase]: " .
            "ClearContent {$srcFile} > {$destFile}"
        );

        if (!file_exists($destFile)) {
            return true;
        }

        $srcData = $this->getFileData($srcFile);
        $destData = $this->getFileData($destFile);

        $exportId = $params->getManifest()->getId();

        $data = ToolUtils::arrayDiffAssocRecursive($destData, $srcData);

        if ($this->backupTool->hasFile($destFile, $exportId)) {
            $backupFile = $this->backupTool->getFilePath($destFile, $exportId);
            $backupData = $this->getFileData($backupFile);

            $data = Util::merge($backupData, $data);
        }

        if (empty($data)) {
            return $this->removeFile($destFile);
        }

        $stringData = Json::encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (!is_string($stringData)) {
            $this->log->warning(
                "ExportImport [Customization.Erase]: " .
                "Unable to ClearContent for the {$destFile}"
            );

            return false;
        }

        return $this->fileManager->putContents($destFile, $stringData);
    }

    private function removeFile(string $file): bool
    {
        $this->log->debug(
            "ExportImport [Customization.Erase]: " .
            "Remove file {$file}"
        );

        if (!file_exists($file)) {
            return true;
        }

        return $this->fileManager->remove($file, null, true);
    }

    private function getFileData(string $file): ?array
    {
        if (!file_exists($file)) {
            return null;
        }

        $content = $this->fileManager->getContents($file);

        try {
            return Json::decode($content, true);
        }
        catch (Exception $e) {}

        return null;
    }
}
