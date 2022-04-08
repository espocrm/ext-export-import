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

namespace Espo\Modules\ExportImport\Tools\Export;

use Espo\Core\{
    Select\SearchParams,
};

use Espo\Modules\ExportImport\Tools\Export\{
    Collection as CollectionClass
};

class Params
{
    private $entityType;

    private $attributeList = null;

    private $fieldList = null;

    private $path = null;

    private $format = null;

    private $searchParams = null;

    private $applyAccessControl = true;

    private $collectionClass = null;

    private $defsSource = null;

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

    public function withSearchParams(?SearchParams $searchParams): self
    {
        $obj = clone $this;

        $obj->searchParams = $searchParams;

        return $obj;
    }

    public function withFieldList(?array $fieldList): self
    {
        $obj = clone $this;

        $obj->fieldList = $fieldList;

        return $obj;
    }

    public function withAttributeList(?array $attributeList): self
    {
        $obj = clone $this;

        $obj->attributeList = $attributeList;

        return $obj;
    }

    public function withAccessControl(bool $applyAccessControl = true): self
    {
        $obj = clone $this;

        $obj->applyAccessControl = $applyAccessControl;

        return $obj;
    }

    public function withCollectionClass(?CollectionClass $collectionClass): self
    {
        $obj = clone $this;

        $obj->collectionClass = $collectionClass;

        return $obj;
    }

    public function withDefsSource(?string $defsSource): self
    {
        $obj = clone $this;

        $obj->defsSource = $defsSource;

        return $obj;
    }

    /**
     * Get search params.
     */
    public function getSearchParams(): SearchParams
    {
        if (!$this->searchParams) {
            return SearchParams::create();
        }

        return $this->searchParams;
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
     * Get attributes to be exported.
     */
    public function getAttributeList(): ?array
    {
        return $this->attributeList;
    }

    /**
     * Get fields to be exported.
     */
    public function getFieldList(): ?array
    {
        return $this->fieldList;
    }

    /**
     * Whether all fields should be exported.
     */
    public function allFields(): bool
    {
        return $this->fieldList === null && $this->attributeList === null;
    }

    /**
     * Whether to apply access control.
     */
    public function applyAccessControl(): bool
    {
        return $this->applyAccessControl;
    }

    /**
     * Get collection class
     */
    public function getCollectionClass(): ?CollectionClass
    {
        return $this->collectionClass;
    }

    /**
     * Get a source of exportImport defs
     */
    public function getDefsSource(): string
    {
        return $this->defsSource;
    }
}