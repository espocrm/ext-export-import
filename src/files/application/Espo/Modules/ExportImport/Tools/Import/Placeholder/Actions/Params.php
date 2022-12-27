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

namespace Espo\Modules\ExportImport\Tools\Import\Placeholder\Actions;

use Espo\Modules\ExportImport\Tools\{
    Manifest
};

class Params
{
    private $entityType;

    private $fieldName;

    private $fieldDefs;

    private $manifest = null;

    private $recordData = null;

    private $exportImportDefs = null;

    private $userActive = false;

    private $userPassword = null;

    public function __construct(string $entityType)
    {
        $this->entityType = $entityType;
    }

    public static function create(string $entityType): self
    {
        return new self($entityType);
    }

    public function withFieldName(string $fieldName): self
    {
        $obj = clone $this;

        $obj->fieldName = $fieldName;

        return $obj;
    }

    public function withFieldDefs(array $fieldDefs): self
    {
        $obj = clone $this;

        $obj->fieldDefs = $fieldDefs;

        return $obj;
    }

    public function withManifest(Manifest $manifest): self
    {
        $obj = clone $this;

        $obj->manifest = $manifest;

        return $obj;
    }

    public function withRecordData(array $data): self
    {
        $obj = clone $this;

        $obj->recordData = $data;

        return $obj;
    }

    public function withExportImportDefs(array $defs): self
    {
        $obj = clone $this;

        $obj->exportImportDefs = $defs;

        return $obj;
    }

    public function withUserActive(bool $userActive): self
    {
        $obj = clone $this;

        $obj->userActive = $userActive;

        return $obj;
    }

    public function withUserPassword(?string $userPassword): self
    {
        $obj = clone $this;

        $obj->userPassword = $userPassword;

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
     * Get a field name
     */
    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    /**
     * Get a field defs
     */
    public function getFieldDefs(): array
    {
        return $this->fieldDefs;
    }

    /**
     * Get placeholder defs
     */
    public function getPlaceholderDefs(): array
    {
        return $this->exportImportDefs[$this->entityType]['fields'][$this->fieldName]
            ?? [];
    }

    /**
     * Get a manifest
     */
    public function getManifest(): Manifest
    {
        return $this->manifest;
    }

    /**
     * Get record data
     */
    public function getRecordData(): array
    {
        return $this->recordData;
    }

    /**
     * Get exportImport defs
     */
    public function getExportImportDefs(): array
    {
        return $this->exportImportDefs;
    }

    /**
     * @return mixed
     */
    public function getFieldValue()
    {
        return $this->recordData[$this->fieldName] ?? null;
    }

    /**
     * User active status
     */
    public function getUserActive(): bool
    {
        return $this->userActive;
    }

    /**
     * User password
     */
    public function getUserPassword(): ?string
    {
        return $this->userPassword;
    }
}
