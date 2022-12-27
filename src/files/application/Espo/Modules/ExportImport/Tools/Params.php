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

namespace Espo\Modules\ExportImport\Tools;

use Espo\Core\{
    Console\IO,
    Utils\Util,
};

use Espo\Modules\ExportImport\Tools\{
    Processor\Utils as ToolUtils,
};

use DateTime;
use DateTimeZone;
use RuntimeException;

class Params
{
    public const APPEND = '__APPEND__';

    public const PATH_ENTITIES = 'Entities';

    public const PATH_FILES = 'Files';

    public const PATH_CUSTOMIZATION = 'Customization';

    public const PATH_CONFIG = 'Config';

    public const TYPE_CREATE = 'create';

    public const TYPE_CREATE_AND_UPDATE = 'createAndUpdate';

    public const TYPE_UPDATE = 'update';

    public const DEFAULT_STORAGE = 'EspoUploadDir';

    public const STORAGE_PATH = 'data/upload';

    private $format = null;

    private $exportImportDefs;

    private $entityTypeList = null;

    private $exportPath = null;

    private $importPath = null;

    private $prettyPrint = false;

    private $manifestFile = null;

    private $importType= null;

    private $updateCurrency= null;

    private $quiet;

    private $userActive = false;

    private $userPassword = null;

    private $io = null;

    private $customization;

    private $exportTime;

    private $updateCreatedAt;

    private $config;

    private $configIgnoreList;

    public function __construct(string $format)
    {
        $this->format = $format;
    }

    public static function create($format): self
    {
        return new self($format);
    }

    public static function fromRaw(array $params): self
    {
        $format = $params['format'] ?? null;

        if (!$format) {
            throw new RuntimeException('Option "format" is not defined.');
        }

        $obj = new self($format);

        $obj->exportPath = $params['exportPath'] ?? null;
        $obj->importPath = $params['importPath'] ?? null;
        $obj->manifestFile = $params['manifestFile'] ?? null;
        $obj->importType = $params['importType'] ?? self::TYPE_CREATE_AND_UPDATE;
        $obj->exportImportDefs = $obj->normalizeExportImportDefs($params);
        $obj->userPassword = $params['userPassword'] ?? null;

        if (!in_array(
            $obj->importType,
            [
                self::TYPE_CREATE,
                self::TYPE_CREATE_AND_UPDATE,
                self::TYPE_UPDATE,
            ]
        )) {
            throw new RuntimeException('Incorrect "importType" option.');
        }

        $obj->entityTypeList = ToolUtils::normalizeList(
            $params['entityTypeList'] ?? null,
            $params['default']['entityTypeList'] ?? null,
        );

        $obj->configIgnoreList = ToolUtils::normalizeList(
            $params['configIgnoreList'] ?? null,
            $params['default']['configIgnoreList'] ?? null,
        );

        $obj->quiet = ToolUtils::normalizeBoolFromArray(
            $params, 'q'
        );

        $obj->customization = ToolUtils::normalizeBoolFromArray(
            $params, 'customization'
        );

        $obj->config = ToolUtils::normalizeBoolFromArray(
            $params, 'config'
        );

        $obj->prettyPrint = ToolUtils::normalizeBoolFromArray(
            $params, 'prettyPrint'
        );

        $obj->updateCurrency = ToolUtils::normalizeBoolFromArray(
            $params, 'updateCurrency'
        );

        $obj->updateCreatedAt = ToolUtils::normalizeBoolFromArray(
            $params, 'updateCreatedAt'
        );

        $obj->userActive = ToolUtils::normalizeBoolFromArray(
            $params, 'userActive'
        );

        $obj->exportTime = $obj->normalizeExportTime(
            $params['exportTime'] ?? null
        );

        return $obj;
    }

    private function normalizeExportTime($value): DateTime
    {
        $value = $value ?? 'now';

        return new DateTime($value, new DateTimeZone('UTC'));
    }

    private function normalizeExportImportDefs(array $params): array
    {
        $exportImportDefs = $params['exportImportDefs'] ?? null;

        if (!$exportImportDefs) {
            throw new RuntimeException('Incorrect "exportImportDefs" data.');
        }

        $hardExportList = isset($params['hardExportList']) ?
            ToolUtils::normalizeList($params['hardExportList'], []) : [];

        $hardImportList = isset($params['hardImportList']) ?
            ToolUtils::normalizeList($params['hardImportList'], []) : [];

        foreach ($hardExportList as $entityType) {
            $exportImportDefs[$entityType]['exportDisabled'] = false;
        }

        foreach ($hardImportList as $entityType) {
            $exportImportDefs[$entityType]['importDisabled'] = false;
        }

        return $exportImportDefs;
    }

    public function withExportImportDefs(array $exportImportDefs): self
    {
        $obj = clone $this;

        $obj->exportImportDefs = $exportImportDefs;

        return $obj;
    }

    public function withEntityTypeList($entityTypeList): self
    {
        $obj = clone $this;

        $obj->entityTypeList = ToolUtils::normalizeList(
            $entityTypeList
        );

        return $obj;
    }

    public function withExportPath(?string $exportPath): self
    {
        $obj = clone $this;

        $obj->exportPath = $exportPath;

        return $obj;
    }

    public function withImportPath(?string $importPath): self
    {
        $obj = clone $this;

        $obj->importPath = $importPath;

        return $obj;
    }

    public function withPrettyPrint(?bool $prettyPrint): self
    {
        $obj = clone $this;

        $obj->prettyPrint = $prettyPrint;

        return $obj;
    }

    public function withManifest(?string $file): self
    {
        $obj = clone $this;

        $obj->manifestFile = $file;

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

    public function withQuiet(?bool $quiet): self
    {
        $obj = clone $this;

        $obj->quiet = $quiet;

        return $obj;
    }

    public function withIO(?IO $io): self
    {
        $obj = clone $this;

        $obj->io = $io;

        return $obj;
    }

    public function withUserActive(bool $userActive): self
    {
        $obj = clone $this;

        $obj->userActive = $userActive;

        return $obj;
    }

    public function withUserPassword(string $userPassword): self
    {
        $obj = clone $this;

        $obj->userPassword = $userPassword;

        return $obj;
    }

    public function withCustomization(bool $customization): self
    {
        $obj = clone $this;

        $obj->customization = $customization;

        return $obj;
    }

    public function withExportTime(DateTime $exportTime): self
    {
        $obj = clone $this;

        $obj->exportTime = $exportTime;

        return $obj;
    }

    public function withUpdateCreatedAt(bool $updateCreatedAt): self
    {
        $obj = clone $this;

        $obj->updateCreatedAt = $updateCreatedAt;

        return $obj;
    }

    public function withConfig(bool $config): self
    {
        $obj = clone $this;

        $obj->config = $config;

        return $obj;
    }

    public function withConfigIgnoreList(array $configIgnoreList): self
    {
        $obj = clone $this;

        $obj->configIgnoreList = $configIgnoreList;

        return $obj;
    }

    /**
     * Get exportImport defs
     */
    public function getExportImportDefs(): array
    {
        return $this->exportImportDefs;
    }

    /**
     * Get a list of entity type
     */
    public function getEntityTypeList(): ?array
    {
        return $this->entityTypeList;
    }

    /**
     * Get a path for an export
     */
    public function getExportPath(): string
    {
        return $this->exportPath;
    }

    /**
     * Get a path of stored data
     */
    public function getImportPath(): string
    {
        return $this->importPath;
    }

    /**
     * Get a prettyPrint option
     */
    public function getPrettyPrint(): string
    {
        return $this->prettyPrint;
    }

    /**
     * Get format for data handling
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * Get export ENTITIES path
     */
    public function getExportEntitiesPath(): string
    {
        return Util::concatPath($this->exportPath, self::PATH_ENTITIES);
    }

    /**
     * Get data ENTITIES path
     */
    public function getDataEntitiesPath(): string
    {
        return Util::concatPath($this->importPath, self::PATH_ENTITIES);
    }

    /**
     * Get export FILES path
     */
    public function getExportFilesPath(): string
    {
        return Util::concatPath($this->exportPath, self::PATH_FILES);
    }

    /**
     * Get data FILES path
     */
    public function getDataFilesPath(): string
    {
        return Util::concatPath($this->importPath, self::PATH_FILES);
    }

    /**
     * Get a manifest file
     */
    public function getExportManifestFile(): string
    {
        return Util::concatPath($this->exportPath, $this->manifestFile);
    }

    /**
     * Get a manifest file
     */
    public function getDataManifestFile(): string
    {
        return Util::concatPath($this->importPath, $this->manifestFile);
    }

    /**
     * Get import type
     */
    public function getImportType(): string
    {
        return $this->importType;
    }

    /**
     * Use a default currency
     */
    public function getUpdateCurrency(): string
    {
        return $this->updateCurrency;
    }

    /**
     * Is quiet
     */
    public function isQuiet(): bool
    {
        if (!$this->quiet && $this->io) {
            return false;
        }

        return true;
    }

    /**
     * Get IO
     */
    public function getIO(): ?IO
    {
        return $this->io;
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

    /**
     * Export / import customization
     */
    public function getCustomization(): bool
    {
        return $this->customization;
    }

    /**
     * Get Export time
     */
    public function getExportTime(): DateTime
    {
        return $this->exportTime;
    }

    /**
     * Get updateCreatedAt
     */
    public function getUpdateCreatedAt(): bool
    {
        return $this->updateCreatedAt;
    }

    /**
     * Export / import config
     */
    public function getConfig(): bool
    {
        return $this->config;
    }

    /**
     * List of ignore config params
     */
    public function getConfigIgnoreList(): array
    {
        return $this->configIgnoreList ?? [];
    }
}
