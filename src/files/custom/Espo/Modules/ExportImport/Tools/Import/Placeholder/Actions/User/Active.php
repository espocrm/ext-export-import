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

use Espo\Core\Utils\Config;

use Espo\Modules\ExportImport\Tools\Import\Placeholder\Actions\Params;
use Espo\Modules\ExportImport\Tools\Import\Placeholder\Actions\Action;

class Active implements Action
{
    public function __construct(
        private Config $config
    ) {}

    public function normalize(Params $params, $actualValue)
    {
        $value = $this->getValueByUserActiveList($params);

        if (!is_null($value)) {
            return $value;
        }

        if ($this->config->get('restrictedMode')) {
            return false;
        }

        $value = $params->getUserActive();

        if (!is_null($value)) {
            return $value;
        }

        return $actualValue;
    }

    private function getValueByUserActiveList(Params $params): ?bool
    {
        $userActiveList = $params->getUserActiveList();

        if (empty($userActiveList)) {
            return null;
        }

        if ($this->isDefinedActive($params, 'id')) {
            return true;
        }

        if ($this->isDefinedActive($params, 'userName')) {
            return true;
        }

        return $params->getUserActive() ?? false;
    }

    private function isDefinedActive(Params $params, string $fieldName): bool
    {
        $value = $params->getRecordData()[$fieldName] ?? null;

        if (!$value) {
            return false;
        }

        if (!in_array($value, $params->getUserActiveList())) {
            return false;
        }

        return true;
    }
}
