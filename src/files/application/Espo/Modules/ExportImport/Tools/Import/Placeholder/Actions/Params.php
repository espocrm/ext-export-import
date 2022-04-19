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

namespace Espo\Modules\ExportImport\Tools\Import\Placeholder\Actions;

use Espo\Modules\ExportImport\Tools\{
    Manifest
};

class Params
{
    private $entityType;

    private $fieldName;

    private $fieldDefs;

    private $placeholderDefs = null;

    private $manifest = null;

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

    public function withPlaceholderDefs(?array $defs): self
    {
        $obj = clone $this;

        $obj->placeholderDefs = $defs;

        return $obj;
    }

    public function withManifest(Manifest $manifest): self
    {
        $obj = clone $this;

        $obj->manifest = $manifest;

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
    public function getFieldDefs(): string
    {
        return $this->fieldDefs;
    }

    /**
     * Get placeholder defs
     */
    public function getPlaceholderDefs(): array
    {
        return $this->placeholderDefs;
    }

    /**
     * Get a manifest
     */
    public function getManifest(): Manifest
    {
        return $this->manifest;
    }
}
