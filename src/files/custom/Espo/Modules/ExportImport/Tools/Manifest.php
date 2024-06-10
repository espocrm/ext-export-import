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
        $this->manifestFile = $this->params->getManifestFile();

        $contents = $this->fileManager->getContents($this->manifestFile);

        if (!$contents) {
            throw new RuntimeException(
                'Manifest file "' . $this->manifestFile . '" is not found.'
            );
        }

        $this->data = Json::decode($contents, true);

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

    public function getId(): string
    {
        return $this->get('id');
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
