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

namespace Espo\Modules\ExportImport\Tools\Import;

use Espo\Core\{
    Utils\Util,
};

use Espo\Modules\ExportImport\Tools\{
    Manifest,
    Processor\ProcessHook,
    Processor\Params as IParams,
};

class Params implements IParams
{
    private $entityType;

    private $path = null;

    private $format = null;

    private $exportImportDefs = null;

    private $manifest = null;

    private $importType = null;

    private $updateCurrency= null;

    private $currency= null;

    private $processHookClass;

    private $entitiesPath;

    private $filesPath;

    private ?bool $userActive = null;

    private $userPassword = null;

    private $updateCreatedAt;

    private bool $isCustomEntity = false;

    private bool $customization = false;

    private array $replaceIdMap = [];

    private bool $clearPassword;

    private ?array $userActiveIdList;

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

    public function withUpdateCurrency(?bool $updateCurrency): self
    {
        $obj = clone $this;

        $obj->updateCurrency = $updateCurrency;

        return $obj;
    }

    public function withCurrency(?string $currency): self
    {
        $obj = clone $this;

        $obj->currency = strtoupper($currency);

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

    public function withUserActive(?bool $userActive): self
    {
        $obj = clone $this;

        $obj->userActive = $userActive;

        return $obj;
    }

    public function withUserActiveIdList(array $list): self
    {
        $obj = clone $this;

        $obj->userActiveIdList = $list;

        return $obj;
    }

    public function withUserPassword(?string $userPassword): self
    {
        $obj = clone $this;

        $obj->userPassword = $userPassword;

        return $obj;
    }

    public function withUpdateCreatedAt(bool $updateCreatedAt): self
    {
        $obj = clone $this;

        $obj->updateCreatedAt = $updateCreatedAt;

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

    public function withReplaceIdMap(array $idMap): self
    {
        $obj = clone $this;

        $obj->replaceIdMap = $idMap;

        return $obj;
    }

    public function withClearPassword(bool $clearPassword): self
    {
        $obj = clone $this;

        $obj->clearPassword = $clearPassword;

        return $obj;
    }

    public function addReplaceIdMapItem(
        string $entityType,
        string $fromId,
        string $toId
    ): void {
        $this->replaceIdMap[$entityType][$fromId] = $toId;
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
            $this->entitiesPath,
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
     * Is update a currency
     */
    public function getUpdateCurrency(): bool
    {
        return $this->updateCurrency ?? false;
    }

    /**
     * Currency
     */
    public function getCurrency(): ?string
    {
        return $this->currency ?? null;
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
     * User active status
     */
    public function getUserActive(): ?bool
    {
        return $this->userActive;
    }

    /**
     * List of user IDs
     */
    public function getUserActiveIdList(): array
    {
        return $this->userActiveIdList ?? [];
    }

    /**
     * User password
     */
    public function getUserPassword(): ?string
    {
        return $this->userPassword;
    }

    /**
     * Get updateCreatedAt
     */
    public function getUpdateCreatedAt(): bool
    {
        return $this->updateCreatedAt;
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
     * Get a map of users ids
     * [
     *  ENTITY_TYPE => [
     *     IMPORT_USER_ID => ACTUAL_USER_ID
     *  ]
     * ]
     */
    public function getReplaceIdMap(): array
    {
        return $this->replaceIdMap;
    }

    /**
     * Get clearPassword option
     */
    public function getClearPassword(): bool
    {
        return $this->clearPassword ?? false;
    }
}
