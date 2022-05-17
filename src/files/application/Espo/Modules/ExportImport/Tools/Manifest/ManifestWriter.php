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

namespace Espo\Modules\ExportImport\Tools\Manifest;

use Espo\{
    Core\Utils\Config,
    Modules\ExportImport\Tools\Params,
    Core\Utils\File\Manager as FileManager,
};

use DateTime;
use DateTimeZone;

class ManifestWriter
{
    private $config;

    private $fileManager;

    private $params;

    private $manifestFile;

    private $applicationName;

    private $version;

    private $exportTime;

    public function __construct(
        Config $config,
        FileManager $fileManager,
        Params $params
    ) {
        $this->config = $config;
        $this->fileManager = $fileManager;
        $this->params = $params;

        $this->loadData();
    }

    protected function loadData(): void
    {
        $this->manifestFile = $this->params->getExportManifestFile();
        $this->applicationName = $this->config->get('applicationName');
        $this->version = $this->config->get('version');
        $this->exportTime = new DateTime('now', new DateTimeZone('UTC'));
    }

    public function setManifestFile(string $manifestFile): self
    {
        $obj = clone $this;

        $obj->manifestFile = $manifestFile;

        return $obj;
    }

    public function setApplicationName(string $applicationName): self
    {
        $obj = clone $this;

        $obj->applicationName = $applicationName;

        return $obj;
    }

    public function setVersion(string $version): self
    {
        $obj = clone $this;

        $obj->version = $version;

        return $obj;
    }

    public function setExportTime(DateTime $exportTime): self
    {
        $obj = clone $this;

        $obj->exportTime = $exportTime;

        return $obj;
    }

    protected function getSaveData(): array
    {
        return [
            'applicationName' => $this->applicationName,
            'version' => $this->version,
            'exportTime' => $this->exportTime->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Save changes to the manifest file.
     */
    public function save(): bool
    {
        return $this->fileManager->putJsonContents(
            $this->manifestFile,
            $this->getSaveData()
        );
    }
}
