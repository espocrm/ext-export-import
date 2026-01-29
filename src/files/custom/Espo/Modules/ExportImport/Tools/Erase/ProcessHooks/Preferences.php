<?php
/************************************************************************
 * This file is part of Export Import extension for EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Modules\ExportImport\Tools\Erase\ProcessHooks;

use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Entities\Preferences as PreferencesEntity;

use Espo\Modules\ExportImport\Tools\Processor\Params;
use Espo\Modules\ExportImport\Tools\Processor\ProcessHook;
use Espo\Modules\ExportImport\Tools\Erase\Util as EraseUtil;
use Espo\Modules\ExportImport\Tools\Erase\Params as EraseParams;
use Espo\Modules\ExportImport\Tools\Backup\Params as RestoreParams;
use Espo\Modules\ExportImport\Tools\Backup\Processors\Restore as RestoreTool;
use Espo\Modules\ExportImport\Tools\Processor\Exceptions\Skip as SkipException;

class Preferences implements ProcessHook
{
    public function __construct(
        private EntityManager $entityManager,
        private RestoreTool $restoreTool
    ) {}

    public function process(Params $params, Entity $entity, array $row): void
    {
        $backupFile = $this->getBackupFile($params);

        if (!file_exists($backupFile)) {
            return;
        }

        $id = $entity->getId();

        $fileData = EraseUtil::getFileData($backupFile);

        $backupData = EraseUtil::getRowById($id, $fileData);

        if (!$backupData) {
            return;
        }

        $entity->set($backupData);

        $this->entityManager->saveEntity($entity, [
            'noStream' => true,
            'noNotifications' => true,
            SaveOption::IMPORT => true,
            SaveOption::SILENT => true,
            SaveOption::SKIP_HOOKS => true,
        ]);

        throw new SkipException('Returning the previous value');
    }

    private function getBackupFile(Params $params): string
    {
        /** @var EraseParams $params */

        $restoreParams = RestoreParams::create()
            ->withManifest($params->getManifest());

        $fileName = PreferencesEntity::ENTITY_TYPE . '.' . EraseParams::FILE_JSON;

        return $restoreParams->getFilePath($fileName, RestoreParams::TYPE_ENTITIES);
    }
}
