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

namespace Espo\Modules\ExportImport\Tools\Compare;

use Espo\Core\Utils\Util;
use Espo\Modules\ExportImport\Tools\Manifest;
use Espo\Modules\ExportImport\Tools\Processor\ProcessHook;
use Espo\Modules\ExportImport\Tools\Processor\Params as IParams;

class Params implements IParams
{
    public const FILE_JSON = 'json';

    private $entityType;

    private $path = null;

    private $format = null;

    private $exportImportDefs = null;

    private $manifest = null;

    private $processHookClass;

    private $entitiesPath;

    private $filesPath;

    private bool $isCustomEntity = false;

    private ?array $userSkipList;

    private array $idMap = [];

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

    public function withUserSkipList(array $list): self
    {
        $obj = clone $this;

        $obj->userSkipList = $list;

        return $obj;
    }

    public function withIsCustomEntity(bool $isCustomEntity): self
    {
        $obj = clone $this;

        $obj->isCustomEntity = $isCustomEntity;

        return $obj;
    }

    public function withIdMap(array $idMap): self
    {
        $obj = clone $this;

        $obj->idMap = $idMap;

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
            $this->entitiesPath,
            $this->entityType . '.' . $this->format
        );
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
     * List of skipped users. It can be ID or userName
     */
    public function getUserSkipList(): array
    {
        return $this->userSkipList ?? [];
    }

    /**
     * Is a custom entity
     */
    public function isCustomEntity(): bool
    {
        return $this->isCustomEntity;
    }

    /**
     * Get a map of entity ids
     * [
     *  ENTITY_TYPE => [
     *     IMPORT_USER_ID => ACTUAL_USER_ID
     *  ]
     * ]
     */
    public function getIdMap(): array
    {
        return $this->idMap;
    }
}
