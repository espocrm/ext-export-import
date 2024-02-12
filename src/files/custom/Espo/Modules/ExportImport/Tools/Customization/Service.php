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

namespace Espo\Modules\ExportImport\Tools\Customization;

use Espo\Core\Utils\Util;
use Espo\Core\Utils\File\Manager as FileManager;

use Espo\Modules\ExportImport\Tools\Customization\Params;
use Espo\Modules\ExportImport\Tools\Processor\Utils as ToolUtils;

class Service
{
    public function __construct(
        private FileManager $fileManager
    ) {}

    public function getFileList(Params $params, string $path): array
    {
        $list = [];

        $fileList = $this->fileManager->getFileList(
            $path, true, '', true, true
        );

        foreach ($params->getEntityTypeList() as $entityType) {
            $entityFileList = $params->getEntityFileList($entityType);

            foreach ($fileList as $file) {
                $fullPath = Util::concatPath($path, $file);

                if (!ToolUtils::isPatternMatched($entityFileList, $fullPath)) {
                    continue;
                }

                $list[] = $fullPath;
            }
        }

        return $list;
    }
}
