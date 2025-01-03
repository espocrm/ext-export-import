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

namespace Espo\Modules\ExportImport\Tools\Customization;

use Espo\Core\{
    Utils\Util,
};

use Espo\Modules\ExportImport\Tools\{
    Manifest,
    Params as ToolParams,
};

class Params
{
    public const PATH_CUSTOM = 'custom/Espo/Custom';

    /**
     * Global file list
     * This is NOT a REGEX
     */
    public const GLOBAL_FILE_LIST = [
        'custom/Espo/Custom/Resources/i18n/*/*.json',
        'custom/Espo/Custom/Resources/metadata/app/*.json',
        'custom/Espo/Custom/Resources/metadata/*/*.json',
        'custom/Espo/Custom/Resources/routes.json',
    ];

    public const FORMULA_FILE_LIST = [
        'custom/Espo/Custom/Resources/metadata/formula/*.json',
    ];

    /**
     * A file list of a single entity
     * This is NOT a REGEX
     */
    private const ENTITY_FILE_LIST = [
        'custom/Espo/Custom/Resources/metadata/*/{ENTITY_TYPE}.json',
        'custom/Espo/Custom/Entities/{NORMALIZED_ENTITY_TYPE}.php',
        'custom/Espo/Custom/Services/{NORMALIZED_ENTITY_TYPE}.php',
        'custom/Espo/Custom/Controllers/{NORMALIZED_ENTITY_TYPE}.php',
        'custom/Espo/Custom/Hooks/{NORMALIZED_ENTITY_TYPE}/*.php',
        'custom/Espo/Custom/Repositories/{NORMALIZED_ENTITY_TYPE}.php',
        'custom/Espo/Custom/SelectManagers/{NORMALIZED_ENTITY_TYPE}.php',
        'custom/Espo/Custom/Resources/layouts/{ENTITY_TYPE}/*.json',
        'custom/Espo/Custom/Resources/i18n/*/{ENTITY_TYPE}.json',
        'custom/Espo/Custom/Resources/i18n/*/Global.json',
        'custom/Espo/Custom/Resources/routes.json',
    ];

    private string $path;

    private array $exportImportDefs;

    private ?Manifest $manifest;

    private array $entityTypeList;

    private bool $isEntityTypeListSpecified;

    private array $idMap = [];

    public static function create(): self
    {
        return new self();
    }

    public function withPath(string $path): self
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

    public function withEntityTypeList(array $List): self
    {
        $obj = clone $this;

        $obj->entityTypeList = $List;

        return $obj;
    }

    public function withIsEntityTypeListSpecified(bool $value): self
    {
        $obj = clone $this;

        $obj->isEntityTypeListSpecified = $value;

        return $obj;
    }

    public function withIdMap(array $idMap): self
    {
        $obj = clone $this;

        $obj->idMap = $idMap;

        return $obj;
    }

    /**
     * Get path
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get path
     */
    public function getCustomizationPath(): string
    {
        return Util::concatPath(
            $this->path,
            ToolParams::PATH_CUSTOMIZATION
        );
    }

    /**
     * Get custom path
     */
    public function getSystemCustomPath(): string
    {
        return self::PATH_CUSTOM;
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
    public function getManifest(): ?Manifest
    {
        return $this->manifest;
    }

    /**
     * Get an entity type list
     */
    public function getEntityTypeList(): array
    {
        return $this->entityTypeList ?? [];
    }

    /**
     * Is a entity type list specified
     */
    public function isEntityTypeListSpecified(): bool
    {
        return $this->isEntityTypeListSpecified;
    }

    /**
     * Get file list for a single entity
     */
    public function getEntityFileList(string $entityType): array
    {
        $normalizedEntityType = Util::normalizeClassName($entityType);

        $list = [];

        foreach (self::ENTITY_FILE_LIST as $file) {
            $list[] = str_replace(
                [
                    '{ENTITY_TYPE}',
                    '{NORMALIZED_ENTITY_TYPE}',
                ],
                [
                    $entityType,
                    $normalizedEntityType
                ],
                $file
            );
        }

        return $list;
    }

    /**
     * Get file list of all specified entities
     */
    public function getEntitiesFileList(): array
    {
        $list = [];

        foreach ($this->getEntityTypeList() as $entityType) {
            $list = array_merge($list, $this->getEntityFileList($entityType));
        }

        $list = array_unique($list);

        return array_values($list);
    }

    /**
     * Get a map of entity ids
     * [
     *  ENTITY_TYPE => [
     *     IMPORT_ID => ACTUAL_ID
     *  ]
     * ]
     */
    public function getIdMap(): array
    {
        return $this->idMap;
    }
}
