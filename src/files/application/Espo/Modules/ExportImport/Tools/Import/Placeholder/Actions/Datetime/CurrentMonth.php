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

namespace Espo\Modules\ExportImport\Tools\Import\Placeholder\Actions\Datetime;

use Espo\Core\{
    Di,
    Exceptions\Error,
};

use Espo\Modules\ExportImport\Tools\Import\{
    Placeholder\Actions\Action,
    Placeholder\Actions\Params,
    Placeholder\Actions\Helper
};

use DateTime;
use DateTimeZone;

class CurrentMonth implements

    Action,
    Di\ConfigAware,
    Di\MetadataAware
{
    use Di\ConfigSetter;
    use Di\MetadataSetter;

    protected $helper;

    public function __construct(Helper $helper)
    {
        $this->helper = $helper;
    }

    public function normalize(Params $params, mixed $actualValue)
    {
        $entityType = $params->getEntityType();
        $fieldName = $params->getFieldName();

        $fieldFormat = $this->helper->getFieldDateFormat(
            $entityType, $fieldName
        );

        $now = new DateTime('now', new DateTimeZone('UTC'));

        $fieldTime = new DateTime($actualValue, new DateTimeZone('UTC'));
        $fieldTime->setDate(
            $now->format('Y'),
            $now->format('m'),
            $fieldTime->format('d'),
        );

        return $fieldTime->format($fieldFormat);
    }
}
