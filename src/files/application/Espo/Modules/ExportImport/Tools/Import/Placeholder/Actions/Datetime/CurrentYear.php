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

namespace Espo\Modules\ExportImport\Tools\Import\Placeholder\Actions\Datetime;

use Espo\Modules\ExportImport\Tools\Import\Placeholder\{
    Actions\Action,
    Actions\Params,
    Actions\Utils,
};

use DateTime;
use DateTimeZone;

class CurrentYear implements Action
{
    public function normalize(Params $params, $actualValue)
    {
        $fieldType = $params->getFieldDefs()['type'] ?? null;

        $fieldFormat = Utils::getDateFieldFormat($fieldType);

        $now = new DateTime('now', new DateTimeZone('UTC'));

        $fieldTime = new DateTime($actualValue, new DateTimeZone('UTC'));
        $fieldTime->setDate(
            $now->format('Y'),
            $fieldTime->format('m'),
            $fieldTime->format('d'),
        );

        return $fieldTime->format($fieldFormat);
    }
}
