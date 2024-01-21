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

namespace Espo\Modules\ExportImport\Tools\Import\ProcessHooks;

use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

use Espo\Entities\User as UserEntity;

use Espo\Modules\ExportImport\Tools\Processor\Params;
use Espo\Modules\ExportImport\Tools\Processor\ProcessHook;
use Espo\Modules\ExportImport\Tools\Processor\Exceptions\Skip as SkipException;

class User implements ProcessHook
{
    public function __construct(
        private EntityManager $entityManager
    ) {}

    public function process(Params $params, Entity $entity, array &$row): void
    {
        if (!$entity->isNew()) {
            return;
        }

        $actualEntity = $this->entityManager
            ->getRDBRepository(UserEntity::ENTITY_TYPE)
            ->where([
                'userName' => $entity->get('userName'),
            ])
            ->findOne();

        if (!$actualEntity) {
            return;
        }

        if ($entity->getId() === $actualEntity->getId()) {
            return;
        }

        $params->addUserIdMapItem($entity->getId(), $actualEntity->getId());

        throw new SkipException(
            'Imported User [' . $entity->getId() . '] is linked ' .
            'to the User [' . $actualEntity->getId() . '] ' .
            'identified by the username [' . $entity->get('userName') . '].'
        );
    }
}
