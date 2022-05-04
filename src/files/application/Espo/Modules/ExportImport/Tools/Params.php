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

namespace Espo\Modules\ExportImport\Tools;

use RuntimeException;

use Espo\Core\Utils\Util;

class Params
{
    public const TYPE_CREATE = 'create';

    public const TYPE_CREATE_AND_UPDATE = 'createAndUpdate';

    public const TYPE_UPDATE = 'update';

    private $format = null;

    private $defsSource = null;

    private $entityTypeList = null;

    private $exportPath = null;

    private $dataPath = null;

    private $prettyPrint = false;

    private $manifestFile = null;

    private $importType= null;

    private $useDefaultCurrency= null;

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

        $obj->defsSource = $params['defsSource'] ?? null;
        $obj->exportPath = $params['exportPath'] ?? null;
        $obj->dataPath = $params['dataPath'] ?? null;
        $obj->manifestFile = $params['manifestFile'] ?? null;
        $obj->importType = $params['importType'] ?? self::TYPE_CREATE_AND_UPDATE;

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

        $obj->useDefaultCurrency = array_key_exists('useDefaultCurrency', $params) ?
            (bool) $params['useDefaultCurrency'] : false;

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

    public function withDefsSource(?string $defsSource): self
    {
        $obj = clone $this;

        $obj->defsSource = $defsSource;

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

    public function withDataPath(?string $dataPath): self
    {
        $obj = clone $this;

        $obj->dataPath = $dataPath;

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

    public function withUseDefaultCurrency(?bool $useDefaultCurrency): self
    {
        $obj = clone $this;

        $obj->useDefaultCurrency = $useDefaultCurrency;

        return $obj;
    }

    /**
     * Get a source of exportImport defs
     */
    public function getDefsSource(): ?string
    {
        return $this->defsSource;
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
    public function getDataPath(): string
    {
        return $this->dataPath;
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
     * Get export entity path
     */
    public function getExportEntityPath(): string
    {
        return Util::concatPath($this->exportPath, 'Entities');
    }

    /**
     * Get data entity path
     */
    public function getDataEntityPath(): string
    {
        return Util::concatPath($this->dataPath, 'Entities');
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
        return Util::concatPath($this->dataPath, $this->manifestFile);
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
    public function getUseDefaultCurrency(): string
    {
        return $this->useDefaultCurrency;
    }
}
