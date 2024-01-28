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

namespace Espo\Modules\ExportImport\Tools\Import\Processor\Attributes;

use Espo\Core\Utils\Log;
use Espo\ORM\Type\AttributeType;
use Espo\Entities\User as UserEntity;

use Espo\Modules\ExportImport\Tools\Import\Params;
use Espo\Modules\ExportImport\Tools\Core\User as UserTool;
use Espo\Modules\ExportImport\Tools\Import\ProcessorAttribute;

class Id implements ProcessorAttribute
{
    public function __construct(
        private Log $log,
        private UserTool $userTool
    ) {}

    public function process(Params $params, array &$row, string $attributeName): void
    {
        if ($params->getEntityType() == UserEntity::ENTITY_TYPE) {
            $this->processUser($params, $row, $attributeName);
        }
    }

    private function processUser(Params $params, array &$row, string $attributeName)
    {
        if ($attributeName != AttributeType::ID) {
            return;
        }

        $id = $row['id'] ?? null;
        $userName = $row['userName'] ?? null;

        if (!$userName) {
            return;
        }

        $user = $this->userTool->getByUserName($userName);

        if (!$user) {
            return;
        }

        $row[$attributeName] = $user->getId();

        $this->addUserReplaceId($params, $row, $id);
    }

    private function addUserReplaceId(Params $params, array $row, ?string $id): void
    {
        $actualId = $row['id'] ?? null;
        $userName = $row['userName'] ?? null;

        if (!$id || !$actualId) {
            return;
        }

        if ($id == $actualId) {
            return;
        }

        $params->addReplaceIdMapItem(
            UserEntity::ENTITY_TYPE,
            $id,
            $actualId
        );

        $this->log->debug(
            'Imported User [' . $id . '] is linked ' .
            'to the User [' . $actualId . '] ' .
            'identified by the username [' . $userName . '].'
        );
    }
}
