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

namespace Espo\Modules\ExportImport\Tools\Export;

use Espo\Core\{
    Select\SearchParams,
    Utils\Util,
};

use Espo\Modules\ExportImport\Tools\{
    Processor\ProcessHook,
    Processor\Params as IParams,
    Export\Processor\Collection as CollectionClass,
};

class Params implements IParams
{
    private $entityType;

    private $attributeList = null;

    private $fieldList = null;

    private $path = null;

    private $format = null;

    private $searchParams = null;

    private $applyAccessControl = true;

    private $collectionClass = null;

    private $exportImportDefs;

    private $fileExtension;

    private $processHookClass;

    private $entitiesPath;

    private $filesPath;

    private $prettyPrint = false;

    private bool $isCustomEntity = false;

    private bool $customization = false;

    private bool $clearPassword;

    private bool $skipInternalConfig;

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

    public function withExportImportDefs(array $exportImportDefs): self
    {
        $obj = clone $this;

        $obj->exportImportDefs = $exportImportDefs;

        return $obj;
    }

    public function withFileExtension(string $fileExtension): self
    {
        $obj = clone $this;

        $obj->fileExtension = $fileExtension;

        return $obj;
    }

    public function withProcessHookClass(?ProcessHook $processHookClass): self
    {
        $obj = clone $this;

        $obj->processHookClass = $processHookClass;

        return $obj;
    }

    public function withEntitiesPath(string $entitiesPath): self
    {
        $obj = clone $this;

        $obj->entitiesPath = $entitiesPath;

        return $obj;
    }

    public function withFilesPath(string $filesPath): self
    {
        $obj = clone $this;

        $obj->filesPath = $filesPath;

        return $obj;
    }

    public function withPrettyPrint(?bool $prettyPrint): self
    {
        $obj = clone $this;

        $obj->prettyPrint = $prettyPrint;

        return $obj;
    }

    public function withIsCustomEntity(bool $isCustomEntity): self
    {
        $obj = clone $this;

        $obj->isCustomEntity = $isCustomEntity;

        return $obj;
    }

    public function withCustomization(bool $customization): self
    {
        $obj = clone $this;

        $obj->customization = $customization;

        return $obj;
    }

    public function withClearPassword(bool $clearPassword): self
    {
        $obj = clone $this;

        $obj->clearPassword = $clearPassword;

        return $obj;
    }

    public function withSkipInternalConfig(bool $isSkip): self
    {
        $obj = clone $this;

        $obj->skipInternalConfig = $isSkip;

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
     * Get exportImport defs
     */
    public function getExportImportDefs(): array
    {
        return $this->exportImportDefs;
    }

    /**
     * Get file name
     */
    public function getFileName(): string
    {
        return $this->entityType . '.' . $this->fileExtension;
    }

    /**
     * Get file path
     */
    public function getFilePath(): string
    {
        return Util::concatPath(
            $this->entitiesPath,
            $this->getFileName()
        );
    }

    /**
     * Get file extension
     */
    public function getFileExtension(): array
    {
        return $this->fileExtension;
    }

    /**
     * Get process hook class
     */
    public function getProcessHookClass(): ?ProcessHook
    {
        return $this->processHookClass;
    }

    /**
     * Get entities path
     */
    public function getEntitiesPath(): string
    {
        return $this->entitiesPath;
    }

    /**
     * Get files path
     */
    public function getFilesPath(): string
    {
        return $this->filesPath;
    }

    /**
     * Get a prettyPrint option
     */
    public function getPrettyPrint(): bool
    {
        return $this->prettyPrint;
    }

    /**
     * Is a custom entity
     */
    public function isCustomEntity(): bool
    {
        return $this->isCustomEntity;
    }

    /**
     * Export / import customization
     */
    public function getCustomization(): bool
    {
        return $this->customization;
    }

    /**
     * Get clearPassword option
     */
    public function getClearPassword(): bool
    {
        return $this->clearPassword ?? false;
    }

    /**
     * Get skipInternalConfig option
     */
    public function getSkipInternalConfig(): bool
    {
        return $this->skipInternalConfig ?? false;
    }
}
