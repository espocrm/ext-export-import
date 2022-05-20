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

namespace Espo\Modules\ExportImport\Tools\Customization;

use Espo\Core\{
    Utils\Util,
};

use Espo\Modules\ExportImport\Tools\{
    Manifest,
    Params as ToolParams,
};

class Params
{
    public const PATH_CUSTOM = 'custom/Espo/Custom';

    public const COMMON_FILE_LIST = [
        'custom/Espo/Custom/Resources/i18n/*/Global.json',
        'custom/Espo/Custom/Resources/metadata/app/*.json',
    ];

    private $path;

    private $exportImportDefs;

    private $manifest;

    private $entityTypeList;

    public static function create(): self
    {
        return new self();
    }

    public function withPath(string $path): self
    {
        $obj = clone $this;

        $obj->path = $path;

        return $obj;
    }

    public function withExportImportDefs(array $exportImportDefs): self
    {
        $obj = clone $this;

        $obj->exportImportDefs = $exportImportDefs;

        return $obj;
    }

    public function withManifest(Manifest $manifest): self
    {
        $obj = clone $this;

        $obj->manifest = $manifest;

        return $obj;
    }

    public function withEntityTypeList(array $entityTypeList): self
    {
        $obj = clone $this;

        $obj->entityTypeList = $entityTypeList;

        return $obj;
    }

    /**
     * Get path
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get path
     */
    public function getCustomizationPath(): string
    {
        return Util::concatPath(
            $this->path,
            ToolParams::PATH_CUSTOMIZATION
        );
    }

    /**
     * Get custom path
     */
    public function getSystemCustomPath(): string
    {
        return self::PATH_CUSTOM;
    }

    /**
     * Get exportImport defs
     */
    public function getExportImportDefs(): array
    {
        return $this->exportImportDefs;
    }

    /**
     * Get a manifest
     */
    public function getManifest(): ?Manifest
    {
        return $this->manifest;
    }

    /**
     * Get entity type list
     */
    public function getEntityTypeList(): array
    {
        return $this->entityTypeList;
    }
}
