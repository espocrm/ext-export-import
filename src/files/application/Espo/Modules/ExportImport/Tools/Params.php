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

namespace Espo\Modules\ExportImport\Tools;

use Espo\Core\{
    Console\IO,
    Utils\Util,
};

use DateTime;
use DateTimeZone;
use RuntimeException;

class Params
{
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
        $obj->exportImportDefs = $params['exportImportDefs'] ?? null;
        $obj->quiet = $params['q'] ?? false;
        $obj->userActive = $params['userActive'] ?? false;
        $obj->userPassword = $params['userPassword'] ?? null;
        $obj->customization = $params['customization'] ?? false;

        if (!$obj->exportImportDefs) {
            throw new RuntimeException('Incorrect "exportImportDefs" data.');
        }

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

        $obj->entityTypeList = $obj->normalizeEntityTypeList(
            $params['entityTypeList'] ?? null
        );

        $obj->prettyPrint = array_key_exists('prettyPrint', $params) ?
            (bool) $params['prettyPrint'] : false;

        $obj->updateCurrency = array_key_exists('updateCurrency', $params) ?
            (bool) $params['updateCurrency'] : false;

        $obj->exportTime = $obj->normalizeExportTime(
            $params['exportTime'] ?? null
        );

        return $obj;
    }

    private function normalizeEntityTypeList($value): ?array
    {
        if (!$value) {
            return $value;
        }

        if (is_string($value)) {
            $value = explode(",", $value);

            foreach ($value as &$item) {
                $item = trim($item);
            }
        }

        if (!is_array($value)) {
            return null;
        }

        return $value;
    }

    private function normalizeExportTime($value): DateTime
    {
        $value = $value ?? 'now';

        return new DateTime($value, new DateTimeZone('UTC'));
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

        $obj->entityTypeList = $obj->normalizeEntityTypeList(
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
}
