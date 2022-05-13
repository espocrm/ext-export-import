<?php
/************************************************************************
 * This file is part of Demo Data extension for EspoCRM.
 *
 * Demo Data extension for EspoCRM.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * Demo Data extension is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Demo Data extension is distributed in the hope that it will be useful,
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

namespace Espo\Modules\ExportImport\Tools\Import\Placeholder\Actions\Config;

use Espo\Core\{
    Di,
    Exceptions\Error,
};

use Espo\Modules\ExportImport\Tools\Import\Placeholder\Actions\{
    Action,
    Params,
    Utils,
};

class Get implements

    Action,
    Di\ConfigAware
{
    use Di\ConfigSetter;

    public function normalize(Params $params, $actualValue)
    {
        $placeholderDefs = $params->getPlaceholderDefs();
        $key = $placeholderDefs['placeholderData']['key'] ?? null;
        $default = $placeholderDefs['placeholderData']['default'] ?? null;

        if (!$key) {
            throw new Error('Config key is not defined');
        }

        if (in_array($key, $this->config->get('systemItems'))) {
            throw new Error("The option '{$key}' from systemItems is not permitted");
        }

        if (!Utils::isCurrencyChangePermitted($params)) {
            throw new Error('The setDefaultCurrency is disabled');
        }

        return $this->config->get($key, $default);
    }
}
