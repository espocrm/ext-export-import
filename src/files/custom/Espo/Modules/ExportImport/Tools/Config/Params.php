<?php
/************************************************************************
 * This file is part of Export Import extension for EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Modules\ExportImport\Tools\Config;

use Espo\Core\Utils\Util;
use Espo\Modules\ExportImport\Tools\Manifest;
use Espo\Modules\ExportImport\Tools\Params as ToolParams;

class Params
{
    public const CONFIG_FILE = 'config.json';

    public const INTERNAL_CONFIG_FILE = 'config-internal.json';

    public const PASSWORD_PARAM_LIST = [
        'passwordSalt',
        'cryptKey',
        'hashSecretKey',
        'apiSecretKeys',
        'smtpPassword',
        'internalSmtpPassword',
        'ldapPassword',
    ];

    private $path;

    private $exportImportDefs;

    private $manifest;

    private $entityTypeList;

    private $configIgnoreList;

    private bool $skipInternalConfig;

    private bool $skipPassword;

    private ?array $configHardList;

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

    public function withEntityTypeList(array $entityTypeList): self
    {
        $obj = clone $this;

        $obj->entityTypeList = $entityTypeList;

        return $obj;
    }

    public function withConfigIgnoreList(array $configIgnoreList): self
    {
        $obj = clone $this;

        $obj->configIgnoreList = $configIgnoreList;

        return $obj;
    }

    public function withSkipInternalConfig(bool $isSkip): self
    {
        $obj = clone $this;

        $obj->skipInternalConfig = $isSkip;

        return $obj;
    }

    public function withConfigHardList(array $list): self
    {
        $obj = clone $this;

        $obj->configHardList = $list;

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
    public function getConfigPath(): string
    {
        return Util::concatPath(
            $this->path,
            ToolParams::PATH_CONFIG
        );
    }

    /**
     * Get path
     */
    public function getConfigFile(): string
    {
        return Util::concatPath(
            $this->getConfigPath(),
            self::CONFIG_FILE
        );
    }

    public function withSkipPassword(bool $skipPassword): self
    {
        $obj = clone $this;

        $obj->skipPassword = $skipPassword;

        return $obj;
    }

    /**
     * Get path
     */
    public function getInternalConfigFile(): string
    {
        return Util::concatPath(
            $this->getConfigPath(),
            self::INTERNAL_CONFIG_FILE
        );
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
     * Get entity type list
     */
    public function getEntityTypeList(): array
    {
        return $this->entityTypeList;
    }

    /**
     * List of ignore config params
     */
    public function getConfigIgnoreList(): array
    {
        return $this->configIgnoreList ?? [];
    }

    /**
     * Get skipPassword option
     */
    public function getSkipPassword(): bool
    {
        return $this->skipPassword ?? false;
    }

    /**
     * Get skipInternalConfig option
     */
    public function getSkipInternalConfig(): bool
    {
        return $this->skipInternalConfig ?? false;
    }

    /**
     * List of hard list params
     */
    public function getConfigHardList(): array
    {
        return $this->configHardList ?? [];
    }
}
