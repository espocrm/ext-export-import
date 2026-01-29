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

namespace Espo\Modules\ExportImport\Tools\Backup;

use Espo\Core\Utils\Util;
use Espo\Modules\ExportImport\Tools\Manifest;
use Espo\Entities\Preferences as PreferencesEntity;
use Espo\Modules\ExportImport\Tools\Params as ToolParams;

class Params
{
    public const BACKUP_PATH = 'data/.backup/export-import';

    public const ENTITY_TYPE_LIST = [
        PreferencesEntity::ENTITY_TYPE,
    ];

    public const TYPE_CONFIG = ToolParams::PATH_CONFIG;

    public const TYPE_ENTITIES = ToolParams::PATH_ENTITIES;

    public const TYPE_CUSTOMIZATION = ToolParams::PATH_CUSTOMIZATION;

    private ?Manifest $manifest;

    private bool $skipData;

    private bool $skipCustomization;

    private bool $skipConfig;

    private bool $skipInternalConfig;

    public static function create(): self
    {
        return new self();
    }

    public function withManifest(Manifest $manifest): self
    {
        $obj = clone $this;

        $obj->manifest = $manifest;

        return $obj;
    }

    public function withSkipData(bool $skip): self
    {
        $obj = clone $this;

        $obj->skipData = $skip;

        return $obj;
    }

    public function withSkipCustomization(bool $skip): self
    {
        $obj = clone $this;

        $obj->skipCustomization = $skip;

        return $obj;
    }

    public function withSkipConfig(bool $skip): self
    {
        $obj = clone $this;

        $obj->skipConfig = $skip;

        return $obj;
    }

    public function withSkipInternalConfig(bool $isSkip): self
    {
        $obj = clone $this;

        $obj->skipInternalConfig = $isSkip;

        return $obj;
    }

    /**
     * Get an ID
     */
    public function getId(): string
    {
        return $this->getManifest()->getId();
    }

    public function getRootPath(): string
    {
        return Util::concatPath(
            self::BACKUP_PATH,
            $this->getId()
        );
    }

    public function getFilePath(
        string $file,
        string $type = self::TYPE_CUSTOMIZATION
    ): string {
        $path = Util::concatPath($this->getRootPath(), $type);

        return Util::concatPath($path, $file);
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
        return self::ENTITY_TYPE_LIST;
    }

    public function getSkipData(): bool
    {
        return $this->skipData;
    }

    public function getSkipCustomization(): bool
    {
        return $this->skipCustomization;
    }

    public function getSkipConfig(): bool
    {
        return $this->skipConfig;
    }

    public function getSkipInternalConfig(): bool
    {
        return $this->skipInternalConfig ?? false;
    }
}
