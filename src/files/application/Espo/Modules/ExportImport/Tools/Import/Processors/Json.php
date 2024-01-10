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

namespace Espo\Modules\ExportImport\Tools\Import\Processors;

use Espo\Core\{
    Exceptions\Error,
};

use Espo\Modules\ExportImport\Tools\{
    Processor\Data,
    Import\Result,
    Import\Processor,
    Import\Params,
};

use GuzzleHttp\Psr7\Stream;

use JsonMachine\Items;

class Json implements Processor
{
    public function process(Params $params, Data $data): Result
    {
        $file = $params->getFile();

        if (!file_exists($file)) {
            throw new Error("Import: file [" . $file . "] does not exist.");
        }

        $records = Items::fromFile($file);

        foreach ($records as $record) {
            $row = get_object_vars($record);

            $data->writeRow($row);
        }

        return Result::create(
            $params->getEntityType()
        );
    }
}
