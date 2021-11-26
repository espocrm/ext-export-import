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

namespace Espo\Modules\ExportImport\Tools;

use Espo\{
    Core\Utils\Json,
    Modules\ExportImport\Tools\Params,
    Core\Utils\File\Manager as FileManager,
};

use DateTime;
use DateTimeZone;
use RuntimeException;

class Manifest
{
    private $fileManager;

    private $params;

    private $manifestFile;

    private $data;

    public function __construct(
        FileManager $fileManager,
        Params $params
    ) {
        $this->fileManager = $fileManager;
        $this->params = $params;

        $this->loadData();
    }

    protected function loadData(): void
    {
        $this->manifestFile = $this->params->getDataManifestFile();

        $contents = $this->fileManager->getContents($this->manifestFile);

        if (!$contents) {
            throw new RuntimeException(
                'Manifest file "' . $this->manifestFile . '" is not found.'
            );
        }

        $this->data = Json::encode($contents, true);

        if (!is_array($this->data)) {
            throw new RuntimeException(
                'Incorrect manifest data.'
            );
        }
    }

    protected function get($name, $default = null)
    {
        return $this->data[$name] ?? $default;
    }

    public function getRaw(): array
    {
        return $this->data;
    }

    public function getApplicationName(): string
    {
        return $this->get('applicationName');
    }

    public function getVersion(): string
    {
        return $this->get('version');
    }

    public function getExportTime(): DateTime
    {
        $exportTime =  $this->get('exportTime');

        if (!$exportTime) {
            throw new RuntimeException(
                'Incorrect export time.'
            );
        }

        return new DateTime($exportTime, new DateTimeZone('UTC'));
    }
}
