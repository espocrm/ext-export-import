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

namespace Espo\Modules\ExportImport\Tools\Import;

use Espo\Core\{
    Utils\Util,
};

use Espo\Modules\ExportImport\Tools\{
    Manifest,
    Processor\ProcessHook,
};

class Params
{
    private $entityType;

    private $path = null;

    private $format = null;

    private $exportImportDefs = null;

    private $manifest = null;

    private $importType = null;

    private $processHookClass;

    public function __construct(string $entityType)
    {
        $this->entityType = $entityType;
    }

    public static function create(string $entityType): self
    {
        return new self($entityType);
    }

    public function withFormat(?string $format): self
    {
        $obj = clone $this;

        $obj->format = $format;

        return $obj;
    }

    public function withPath(?string $path): self
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

    public function withImportType(string $importType): self
    {
        $obj = clone $this;

        $obj->importType = $importType;

        return $obj;
    }

    public function withProcessHookClass(?ProcessHook $processHookClass): self
    {
        $obj = clone $this;

        $obj->processHookClass = $processHookClass;

        return $obj;
    }

    /**
     * Get a target entity type.
     */
    public function getEntityType(): string
    {
        return $this->entityType;
    }

    /**
     * Get storage path.
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * Get a format.
     */
    public function getFormat(): ?string
    {
        return $this->format;
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
    public function getManifest(): Manifest
    {
        return $this->manifest;
    }

    /**
     * Get a file
     */
    public function getFile(): string
    {
        return Util::concatPath(
            $this->path,
            $this->entityType . '.' . $this->format
        );
    }

    /**
     * Get import type
     */
    public function getImportType(): string
    {
        return $this->importType;
    }

    /**
     * Get process hook class
     */
    public function getProcessHookClass(): ?ProcessHook
    {
        return $this->processHookClass;
    }
}
