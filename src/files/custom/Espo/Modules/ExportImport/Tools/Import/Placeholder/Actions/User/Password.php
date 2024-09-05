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

namespace Espo\Modules\ExportImport\Tools\Import\Placeholder\Actions\User;

use Espo\Entities\User;
use Espo\ORM\EntityManager;
use Espo\Core\Utils\PasswordHash;

use Espo\Modules\ExportImport\Tools\Import\Placeholder\Actions\Action;
use Espo\Modules\ExportImport\Tools\Import\Placeholder\Actions\Params;
use Espo\Modules\ExportImport\Tools\Processor\Exceptions\Skip as SkipException;

class Password implements Action
{
    public function __construct(
        private PasswordHash $passwordHash,
        private EntityManager $entityManager
    ) {}

    public function normalize(Params $params, $actualValue)
    {
        if ($params->getSkipPassword()) {
            return $this->processSkipPassword($params, $actualValue);
        }

        return $this->processPassword($params, $actualValue);
    }

    private function processPassword(Params $params, $actualValue): string
    {
        $password = $params->getUserPassword();

        if (!$password) {
            $password = $params->getPlaceholderDefs()['placeholderData']['value']
                ?? null;
        }

        if (!$password && !empty($actualValue)) {
            return $actualValue;
        }

        return $this->normalizePassword($password);
    }

    private function processSkipPassword(Params $params, $actualValue): string
    {
        $id = $params->getRecordData()['id'] ?? null;

        if (!$id) {
            return $this->normalizePassword(null);
        }

        $user = $this->entityManager->getEntityById(User::ENTITY_TYPE, $id);

        if (!$user) {
            return $this->normalizePassword(null);
        }

        throw new SkipException();
    }

    private function normalizePassword(?string $password): string
    {
        if (!$password) {
            $password = uniqid('', true);
        }

        return $this->passwordHash->hash($password);
    }
}
