<?php
/************************************************************************
 * This file is part of Export Import extension for EspoCRM.
 *
 * Export Import extension for EspoCRM.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Modules\ExportImport\Tools\Processor;

class Data
{
    private $resource;

    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    public function writeRow(array $row): void
    {
        $line = base64_encode(serialize($row)) . \PHP_EOL;

        fwrite($this->resource, $line);
    }

    public function readRow(): ?array
    {
        $line = fgets($this->resource);

        if ($line === false) {
            return null;
        }

        return unserialize(base64_decode($line));
    }

    public function rewind()
    {
        rewind($this->resource);
    }

    public function isEnd(): bool
    {
        return feof($this->resource);
    }

    public function isEmpty(): bool
    {
        $fstat = fstat($this->resource);

        if ($fstat['size'] > 0) {
            return false;
        }

        return true;
    }

    public function getResource()
    {
        return $this->resource;
    }
}
