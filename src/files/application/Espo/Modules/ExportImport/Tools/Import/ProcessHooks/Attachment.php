<?php
/************************************************************************
 * This file is part of Export Import extension for EspoCRM.
 *
 * Export Import extension for EspoCRM.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Modules\ExportImport\Tools\Import\ProcessHooks;

use Espo\{
    Core\Di,
    ORM\Entity,
};

use Espo\Modules\ExportImport\Tools\{
    Params as ToolParams,
    Processor\Params,
    Processor\ProcessHook,
    Processor\Utils,
};

class Attachment implements

    ProcessHook,
    Di\LogAware,
    Di\FileManagerAware
{
    use Di\LogSetter;
    use Di\FileManagerSetter;

    public function process(Params $params, Entity $entity, array &$row): void
    {
        if ($entity->get('storage') != ToolParams::DEFAULT_STORAGE) {
            return;
        }

        $srcFile = Utils::getFilePathInData($params, $entity->id);
        $destDir = Utils::getDirPathInUpload($params);

        $result = $this->fileManager->copy(
            $srcFile, $destDir, false, null, true
        );

        if (!$result) {
            $this->log->error(
                "ExportImport [Import]: Unable to copy the file from " .
                "'{$srcFile}' to '{$destDir}'."
            );
        }
    }
}
