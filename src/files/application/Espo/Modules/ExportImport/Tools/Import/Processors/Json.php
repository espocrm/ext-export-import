<?php
/************************************************************************
 * This file is part of Export Import extension for EspoCRM.
 *
 * Export Import extension for EspoCRM.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Modules\ExportImport\Tools\Import\Processors;

use Espo\Core\ORM\Entity;

use Espo\Core\{
    Utils\Config,
    Utils\Metadata,
    Utils\Json as JsonUtil,
    Exceptions\Error,
};

use Espo\Modules\ExportImport\Tools\Import\{
    Processor,
    Processor\Params,
    Processor\Data,
};

use Espo\Entities\Preferences;

use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Psr7\Stream;

use const JSON_UNESCAPED_UNICODE;
use const JSON_UNESCAPED_SLASHES;
use const JSON_PRETTY_PRINT;

class Json implements Processor
{
    public function process(Params $params, Data $data): StreamInterface
    {
        $file = $params->getFile();

        if (!file_exists($file)) {
            throw new Error("Import: file [" . $file . "] does not exist.");
        }

        die($file);
    }
}