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

namespace Espo\Modules\ExportImport\Tools\Processor;

use Espo\{
    Core\Utils\Util,
};

use Espo\Modules\ExportImport\Tools\{
    Processor\Params,
    Params as ToolParams,
};

class Utils
{
    /**
     * Get a files path in data/upload
     */
    public static function getFilePathInUpload(Params $params, string $id): string
    {
        return Util::concatPath(
            ToolParams::STORAGE_PATH,
            $id
        );
    }

    /**
     * Get a directory path in export/import directory
     */
    public static function getDirPathInData(Params $params): string
    {
        return Util::concatPath(
            $params->getFilesPath(),
            $params->getEntityType()
        );
    }

    /**
     * Get a files path in export/import directory
     */
    public static function getFilePathInData(Params $params, string $id): string
    {
        $directoryPath = self::getDirPathInData($params);

        return Util::concatPath(
            $directoryPath,
            $id
        );
    }
}
