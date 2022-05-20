<?php
/************************************************************************
 * This file is part of Export Import extension for EspoCRM.
 *
 * Export Import extension for EspoCRM.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Modules\ExportImport\Tools\Customization\Processors;

use Espo\Core\{
    Di,
    Utils\Util,
    Utils\Json,
};

use Espo\Modules\ExportImport\Tools\{
    Customization\Processor,
    Customization\Params,
    Processor\Utils as ToolUtils,
};

class Import implements

    Processor,
    Di\LogAware,
    Di\FileManagerAware
{
    use Di\LogSetter;
    use Di\FileManagerSetter;

    //todo: copy only for $params->getEntityTypeList()
    public function process(Params $params): void
    {
        $src = $params->getCustomizationPath();
        $commonFileList = Params::COMMON_FILE_LIST;

        $fileList = $this->fileManager->getFileList(
            $src, true, '', true, true
        );

        foreach ($fileList as $file) {
            $fullPath = Util::concatPath($src, $file);

            if (ToolUtils::isPatternMatched($commonFileList, $file)) {
                $this->copyMerged($fullPath, $file);
                continue;
            }

            $this->copy($fullPath, $file);
        }
    }

    private function copy(string $src, string $dest): bool
    {
        $this->log->debug(
            "ExportImport [Customization.Import]: " .
            "Copy {$src} > {$dest}"
        );

        $destDir = dirname($dest);

        return $this->fileManager->copy($src, $destDir, false, null, true);
    }

    private function copyMerged(string $src, string $dest): bool
    {
        if (!file_exists($dest)) {
            return $this->copy($src, $dest);
        }

        $this->log->debug(
            "ExportImport [Customization.Import]: " .
            "CopyMerged {$src} > {$dest}"
        );

        $content = $this->fileManager->getContents($src);
        $data = Json::decode($content, true);

        return $this->fileManager->mergeJsonContents($dest, $data);
    }
}
