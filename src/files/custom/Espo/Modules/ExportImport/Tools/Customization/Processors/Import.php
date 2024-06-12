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

use Espo\Modules\ExportImport\Tools\Import\Params as ImportParams;

use Espo\Modules\ExportImport\Tools\IdMapping\IdReplacer;
use Espo\Modules\ExportImport\Tools\Core\Backup as BackupTool;

class Import implements Processor
{
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

        $this->backupTool->clear($exportId);

        $fileList = $this->service->getCopyFileList($params, $src);

        foreach ($fileList as $file) {
            $sourceFile = Util::concatPath($src, $file);

            $this->backupTool->backupFile($file, $exportId);

            if (ToolUtils::isPatternMatched($file, Params::FORMULA_FILE_LIST)) {
                $this->copyFormula($params, $sourceFile, $file);
                continue;
            }

            if (ToolUtils::isPatternMatched($file, Params::GLOBAL_FILE_LIST)) {
                $this->copyMerged($sourceFile, $file);
                continue;
            }

            $this->copy($sourceFile, $file);
        }
    }
    private function copy(string $srcFile, string $destFile): bool
    {
        $this->log->debug(
            "ExportImport [Customization.Import]: " .
            "Copy {$srcFile} > {$destFile}"
        );

        $dest = dirname($destFile);

        return $this->fileManager->copy($srcFile, $dest, false, null, true);
    }

    private function copyMerged(string $srcFile, string $destFile): bool
    {
        if (!file_exists($destFile)) {
            return $this->copy($srcFile, $destFile);
        }

        $this->log->debug(
            "ExportImport [Customization.Import]: " .
            "CopyMerged {$srcFile} > {$destFile}"
        );

        $content = $this->fileManager->getContents($srcFile);
        $data = Json::decode($content, true);

        return $this->fileManager->mergeJsonContents($destFile, $data);
    }

    private function copyFormula(Params $params, string $srcFile, string $destFile): bool
    {
        $this->log->debug(
            "ExportImport [Customization.Import]: " .
            "CopyFormula {$srcFile} > {$destFile}"
        );

        $content = $this->fileManager->getContents($srcFile);

        $content = $this->getReplacedString($params, $srcFile, $content);

        $data = Json::decode($content, true);

        return $this->fileManager->mergeJsonContents($destFile, $data);
    }

    private function getReplacedString(
        Params $params,
        string $file,
        string $content
    ): string {
        $entityType = ToolUtils::getEntityTypeByMetadataFile($file, 'Import');

        $importParams = ImportParams::create($entityType)
            ->withIdMap($params->getIdMap());

        return $this->idReplacer->getReplacedString($importParams, $content);
    }
}
