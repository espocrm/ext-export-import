<?php
/************************************************************************
 * This file is part of Export Import extension for EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Modules\ExportImport\Tools\Manifest;

use DateTime;
use Espo\Core\Utils\Util;
use Espo\Core\Utils\Config;
use Espo\Modules\ExportImport\Tools\Params;
use Espo\Core\Utils\File\Manager as FileManager;

class ManifestWriter
{
    private string $manifestFile;

    private string $id;

    private ?string $applicationName;

    private string $version;

    private DateTime $exportTime;

    public function __construct(
        private Config $config,
        private FileManager $fileManager,
        private Params $params
    ) {
        $this->loadData();
    }

    private function loadData(): void
    {
        $this->manifestFile = $this->params->getManifestFile();

        $this->id = $this->getGeneratedId();
        $this->applicationName = $this->config->get('applicationName');
        $this->version = $this->config->get('version');
        $this->exportTime = $this->params->getExportTime();
    }

    public function setManifestFile(string $manifestFile): self
    {
        $obj = clone $this;

        $obj->manifestFile = $manifestFile;

        return $obj;
    }

    public function setId(string $id): self
    {
        $obj = clone $this;

        $obj->id = $id;

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

    private function getGeneratedId(): string
    {
        return 'EI' . Util::generateId();
    }

    protected function getSaveData(): array
    {
        return [
            'id' => $this->id,
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
