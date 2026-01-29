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

namespace Espo\Modules\ExportImport\Tools\Backup\Processors;

use Exception;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Modules\ExportImport\Tools\Backup\Params;
use Espo\Modules\ExportImport\Tools\Backup\Processor;
use Espo\Modules\ExportImport\Tools\ExportImport as ExportImportTool;

class Backup implements Processor
{
    public function __construct(
        private FileManager $fileManager,
        private ExportImportTool $exportImportTool
    ) {}

    public function process(Params $params): void
    {
        $this->exportImportTool->runExport([
            'path' => $params->getRootPath(),
            'entityList' => $params->getEntityTypeList(),
            'skipData' => $params->getSkipData(),
            'skipConfig' => $params->getSkipConfig(),
            'skipInternalConfig' => $params->getSkipInternalConfig(),
            'skipCustomization' => $params->getSkipCustomization(),
            'skipRelatedEntities' => true,
            'allCustomization' => true,
        ]);
    }

    public function processFile(
        Params $params,
        string $srcFile,
        string $type = Params::TYPE_CUSTOMIZATION
    ): void {
        if (!file_exists($srcFile)) {
            return;
        }

        $destFile = $params->getFilePath($srcFile, $type);

        $dest = pathinfo($destFile, PATHINFO_DIRNAME);

        try {
            $this->fileManager->copy($srcFile, $dest, false, null, true);
        } catch (Exception $e) {}
    }
}
