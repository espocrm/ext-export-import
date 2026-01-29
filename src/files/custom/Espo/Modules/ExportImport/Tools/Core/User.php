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

namespace Espo\Modules\ExportImport\Tools\Core;

use Espo\Core\ORM\EntityManager;
use Espo\Entities\User as UserEntity;

class User
{
    public function __construct(
        private EntityManager $entityManager
    ) {}

    public function getByUserName(string $userName): ?UserEntity
    {
        return $this->entityManager
            ->getRDBRepository(UserEntity::ENTITY_TYPE)
            ->where([
                'userName' => $userName,
            ])
            ->findOne();
    }

    /**
     * Get user ID list of $userList - list of ID or userName
     */
    public function getIdUserList(?array $userList): array
    {
        if (!$userList) {
            return [];
        }

        $userIdList = [];

        foreach ($userList as $user) {
            $entity = $this->entityManager->getEntityById(
                UserEntity::ENTITY_TYPE,
                $user
            );

            if (!$entity) {
                $entity = $this->getByUserName($user);
            }

            if (!$entity) {
                continue;
            }

            $userIdList[] = $entity->getId();
        }

        return $userIdList;
    }
}
