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

namespace Espo\Modules\ExportImport\Tools;

use DateTime;
use DateTimeZone;
use RuntimeException;
use Espo\Core\Console\IO;
use Espo\Core\Utils\Util;
use Espo\Modules\ExportImport\Tools\Processor\Utils as ToolUtils;

class Params
{
    public const ACTION_EXPORT = 'export';

    public const ACTION_IMPORT = 'import';

    public const ACTION_ERASE = 'erase';

    public const ACTION_COMPARE = 'compare';

    public const APPEND = '__APPEND__';

    public const PATH_ENTITIES = 'Entities';

    public const PATH_FILES = 'Files';

    public const PATH_CUSTOMIZATION = 'Customization';

    public const PATH_CONFIG = 'Config';

    public const IMPORT_TYPE_CREATE = 'create';

    public const IMPORT_TYPE_CREATE_AND_UPDATE = 'createAndUpdate';

    public const IMPORT_TYPE_UPDATE = 'update';

    public const DEFAULT_STORAGE = 'EspoUploadDir';

    public const STORAGE_PATH = 'data/upload';

    public const COMPARE_TYPE_CREATED = 'created';

    public const COMPARE_TYPE_UPDATED = 'updated';

    public const COMPARE_TYPE_DELETED = 'deleted';

    public const COMPARE_TYPE_ALL = 'all';

    public const LOG_LEVEL_INFO = 'info';

    public const LOG_LEVEL_DEBUG = 'debug';

    private string $action;

    private $format = null;

    private bool $skipData;

    private $exportImportDefs;

    private ?array $entityTypeList;

    private ?array $entityTypeSkipList;

    private $path = null;

    private ?string $resultPath;

    private $prettyPrint = false;

    private $manifestFile = null;

    private $importType= null;

    private $updateCurrency= null;

    private $currency= null;

    private bool $confirmed;

    private $quiet;

    private ?bool $userActive = null;

    private $userPassword = null;

    private $io = null;

    private bool $skipCustomization;

    private bool $allCustomization;

    private $exportTime;

    private $updateCreatedAt;

    private bool $skipConfig;

    private $configIgnoreList;

    private bool $skipPassword;

    private bool $skipInternalConfig;

    private ?array $configHardList;

    private ?array $userActiveList;

    private ?array $userSkipList;

    private bool $skipRelatedEntities;

    private ?array $entityImportTypeCreate;

    private ?array $entityImportTypeUpdate;

    private ?array $entityImportTypeCreateAndUpdate;

    private bool $allAttributes;

    private string $compareType;

    /**
     * Skip checking modifiedAt to find modified records
     */
    private bool $skipModifiedAt;

    /**
     * Skip checking stream messages to find modified records
     */
    private bool $skipStream;

    /**
     * Skip checking action history to find modified records
     */
    private bool $skipActionHistory;

    /**
     * Skip checking workflow log to find modified records
     */
    private bool $skipWorkflowLog;

    private ?string $logLevel = null;

    private ?DateTime $fromDate;

    private ?array $skipAttributeList;

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
        $action = $params['action'] ?? null;

        if (!$format) {
            throw new RuntimeException('Option "format" is not defined.');
        }

        if (!$action) {
            throw new RuntimeException('Unknown action.');
        }

        $obj = new self($format);

        $obj->action = $action;
        $obj->path = $params['path'] ?? null;
        $obj->resultPath = $params['resultPath'] ?? $params['default']['resultPath'] ?? null;
        $obj->manifestFile = $params['manifestFile'] ?? null;
        $obj->importType = $params['importType'] ?? self::IMPORT_TYPE_CREATE_AND_UPDATE;
        $obj->exportImportDefs = $obj->normalizeExportImportDefs($params);
        $obj->userPassword = $params['userPassword'] ?? null;
        $obj->currency = $params['currency'] ?? null;
        $obj->skipPassword = $params['skipPassword'] ?? false;
        $obj->skipInternalConfig = $params['skipInternalConfig'] ?? false;
        $obj->userActive = $obj->normalizeUserActive($params);
        $obj->logLevel = $obj->normalizeLogLevel($params);

        if (!in_array(
            $obj->importType,
            [
                self::IMPORT_TYPE_CREATE,
                self::IMPORT_TYPE_CREATE_AND_UPDATE,
                self::IMPORT_TYPE_UPDATE,
            ]
        )) {
            throw new RuntimeException('Incorrect "importType" option.');
        }

        $obj->entityTypeList = ToolUtils::normalizeList(
            $params['entityList'] ?? null,
            $params['default']['entityList'] ?? null,
        );

        $obj->entityTypeSkipList = ToolUtils::normalizeList(
            $params['entitySkipList'] ?? null,
            $params['default']['entitySkipList'] ?? null,
        );

        $obj->configIgnoreList = ToolUtils::normalizeList(
            $params['configIgnoreList'] ?? null,
            $params['default']['configIgnoreList'] ?? null,
        );

        $obj->confirmed = ToolUtils::normalizeBoolFromArray(
            $params, 'y'
        );

        $obj->quiet = ToolUtils::normalizeBoolFromArray(
            $params, 'q'
        );

        $obj->skipData = ToolUtils::normalizeBoolFromArray(
            $params, 'skipData'
        );

        $obj->skipCustomization = ToolUtils::normalizeBoolFromArray(
            $params, 'skipCustomization'
        );

        $obj->allCustomization = ToolUtils::normalizeBoolFromArray(
            $params, 'allCustomization'
        );

        $obj->skipConfig = ToolUtils::normalizeBoolFromArray(
            $params, 'skipConfig'
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

        $obj->exportTime = $obj->normalizeExportTime(
            $params['exportTime'] ?? null
        );

        $obj->configHardList = ToolUtils::normalizeList(
            $params['configHardList'] ?? null,
            $params['default']['configHardList'] ?? null,
        );

        $obj->userActiveList = ToolUtils::normalizeList(
            $params['userActiveList'] ?? null,
            $params['default']['userActiveList'] ?? null,
        );

        $obj->userSkipList = ToolUtils::normalizeList(
            $params['userSkipList'] ?? null,
            $params['default']['userSkipList'] ?? null,
        );

        $obj->skipRelatedEntities = ToolUtils::normalizeBoolFromArray(
            $params, 'skipRelatedEntities'
        );

        $obj->entityImportTypeCreate = ToolUtils::normalizeList(
            $params['entityImportTypeCreate'] ?? null,
            $params['default']['entityImportTypeCreate'] ?? null,
        );

        $obj->entityImportTypeUpdate = ToolUtils::normalizeList(
            $params['entityImportTypeUpdate'] ?? null,
            $params['default']['entityImportTypeUpdate'] ?? null,
        );

        $obj->entityImportTypeCreateAndUpdate = ToolUtils::normalizeList(
            $params['entityImportTypeCreateAndUpdate'] ?? null,
            $params['default']['entityImportTypeCreateAndUpdate'] ?? null,
        );

        $obj->allAttributes = ToolUtils::normalizeBoolFromArray(
            $params, 'allAttributes'
        );

        $compareType = $params['compareType'] ?? null;

        if ($compareType && !in_array(
            $compareType,
            [
                self::COMPARE_TYPE_ALL,
                self::COMPARE_TYPE_CREATED,
                self::COMPARE_TYPE_UPDATED,
                self::COMPARE_TYPE_DELETED,
            ]
        )) {
            throw new RuntimeException('Incorrect "compareType" option.');
        }

        $obj->compareType = $compareType ?? self::COMPARE_TYPE_ALL;

        $obj->skipModifiedAt = ToolUtils::normalizeBoolFromArray(
            $params, 'skipModifiedAt'
        );

        $obj->skipStream = ToolUtils::normalizeBoolFromArray(
            $params, 'skipStream'
        );

        $obj->skipActionHistory = ToolUtils::normalizeBoolFromArray(
            $params, 'skipActionHistory'
        );

        $obj->skipWorkflowLog = ToolUtils::normalizeBoolFromArray(
            $params, 'skipWorkflowLog'
        );

        $obj->fromDate = ToolUtils::normalizeDateTime(
            $params['fromDate'] ?? null
        );

        $obj->skipAttributeList = ToolUtils::normalizeList(
            $params['skipAttributeList'] ?? null
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
        $action = $params['action'] ?? null;
        $exportImportDefs = $params['exportImportDefs'] ?? null;

        if (!$exportImportDefs) {
            throw new RuntimeException('Incorrect "exportImportDefs" data.');
        }

        $hardList = isset($params['entityHardList']) ?
            ToolUtils::normalizeList($params['entityHardList'], []) : [];

        if ($action == self::ACTION_EXPORT) {
            foreach ($hardList as $entityType) {
                $exportImportDefs[$entityType]['exportDisabled'] = false;
            }
        }

        if ($action == self::ACTION_IMPORT) {
            foreach ($hardList as $entityType) {
                $exportImportDefs[$entityType]['importDisabled'] = false;
            }
        }

        return $exportImportDefs;
    }

    private function normalizeUserActive(array $params): ?bool
    {
        $isActivate = $params['activateUsers'] ?? null;
        $isDeactivate = $params['deactivateUsers'] ?? null;

        if ($isActivate && $isDeactivate) {
            throw new RuntimeException(
                'The "--activate-users" and "--deactivate-users" options ' .
                'cannot be used together.'
            );
        }

        if ($isActivate) {
            return true;
        }

        if ($isDeactivate) {
            return false;
        }

        return null;
    }

    private function normalizeLogLevel(array $params): ?string
    {
        $info = $params['info'] ?? null;

        if ($info) {
            return self::LOG_LEVEL_INFO;
        }

        $debug = $params['debug'] ?? null;

        if ($debug) {
            return self::LOG_LEVEL_DEBUG;
        }

        return null;
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

    public function withEntityTypeSkipList($list): self
    {
        $obj = clone $this;

        $obj->entityTypeSkipList = ToolUtils::normalizeList(
            $list
        );

        return $obj;
    }

    public function withAction(string $action): self
    {
        $obj = clone $this;

        $obj->action = $action;

        return $obj;
    }

    public function withPath(?string $path): self
    {
        $obj = clone $this;

        $obj->path = $path;

        return $obj;
    }

    public function withSkipData(bool $skip): self
    {
        $obj = clone $this;

        $obj->skipData = $skip;

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

    public function withCurrency(?string $currency): self
    {
        $obj = clone $this;

        $obj->currency = strtoupper($currency);

        return $obj;
    }

    public function withConfirmed(bool $value): self
    {
        $obj = clone $this;

        $obj->confirmed = $value;

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

    public function withUserActive(?bool $userActive): self
    {
        $obj = clone $this;

        $obj->userActive = $userActive;

        return $obj;
    }

    public function withUserActiveList(array $list): self
    {
        $obj = clone $this;

        $obj->userActiveList = $list;

        return $obj;
    }

    public function withUserSkipList(array $list): self
    {
        $obj = clone $this;

        $obj->userSkipList = $list;

        return $obj;
    }

    public function withUserPassword(string $userPassword): self
    {
        $obj = clone $this;

        $obj->userPassword = $userPassword;

        return $obj;
    }

    public function withSkipCustomization(bool $skip): self
    {
        $obj = clone $this;

        $obj->skipCustomization = $skip;

        return $obj;
    }

    public function withAllCustomization(bool $value): self
    {
        $obj = clone $this;

        $obj->allCustomization = $value;

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

    public function withSkipConfig(bool $skip): self
    {
        $obj = clone $this;

        $obj->skipConfig = $skip;

        return $obj;
    }

    public function withConfigIgnoreList(array $configIgnoreList): self
    {
        $obj = clone $this;

        $obj->configIgnoreList = $configIgnoreList;

        return $obj;
    }

    public function withConfigHardList(array $list): self
    {
        $obj = clone $this;

        $obj->configHardList = $list;

        return $obj;
    }

    public function withSkipPassword(bool $skipPassword): self
    {
        $obj = clone $this;

        $obj->skipPassword = $skipPassword;

        return $obj;
    }

    public function withSkipInternalConfig(bool $isSkip): self
    {
        $obj = clone $this;

        $obj->skipInternalConfig = $isSkip;

        return $obj;
    }

    public function withSkipRelatedEntities(bool $value): self
    {
        $obj = clone $this;

        $obj->skipRelatedEntities = $value;

        return $obj;
    }

    public function withEntityImportTypeCreate(array $list): self
    {
        $obj = clone $this;

        $obj->entityImportTypeCreate = $list;

        return $obj;
    }

    public function withEntityImportTypeUpdate(array $list): self
    {
        $obj = clone $this;

        $obj->entityImportTypeUpdate = $list;

        return $obj;
    }

    public function withEntityImportTypeCreateAndUpdate(array $list): self
    {
        $obj = clone $this;

        $obj->entityImportTypeCreateAndUpdate = $list;

        return $obj;
    }

    public function withAllAttributes(bool $value): self
    {
        $obj = clone $this;

        $obj->allAttributes = $value;

        return $obj;
    }

    /**
     * @throws RuntimeException
     */
    public function withCompareType(string $compareType): self
    {
        if (!in_array(
            $compareType,
            [
                self::COMPARE_TYPE_ALL,
                self::COMPARE_TYPE_CREATED,
                self::COMPARE_TYPE_UPDATED,
                self::COMPARE_TYPE_DELETED,
            ]
        )) {
            throw new RuntimeException('Incorrect "compareType" option.');
        }

        $obj = clone $this;

        $obj->compareType = $compareType;

        return $obj;
    }

    public function withLogLevel(?string $logLevel): self
    {
        $obj = clone $this;

        $obj->logLevel = $logLevel;

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
        return $this->entityTypeList ?? null;
    }

    /**
     * Get a skip list of entity type
     */
    public function getEntityTypeSkipList(): ?array
    {
        return $this->entityTypeSkipList ?? null;
    }

    /**
     * Get an action (export / import / erase)
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * Get a path for an export / import
     */
    public function getPath(): string
    {
        return $this->path;
    }

    public function getResultPath(): ?string
    {
        return $this->resultPath ?? null;
    }

    /**
     * Get skipData option
     */
    public function getSkipData(): bool
    {
        return $this->skipData;
    }

    /**
     * Get a prettyPrint option
     */
    public function getPrettyPrint(): bool
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
     * Get export / import ENTITIES path
     */
    public function getEntitiesPath(): string
    {
        return Util::concatPath($this->path, self::PATH_ENTITIES);
    }

    /**
     * Get export / import FILES path
     */
    public function getFilesPath(): string
    {
        return Util::concatPath($this->path, self::PATH_FILES);
    }

    /**
     * Get a manifest file
     */
    public function getManifestFile(): string
    {
        return Util::concatPath($this->path, $this->manifestFile);
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
     * Is confirmed
     */
    public function isConfirmed(): bool
    {
        return $this->confirmed;
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
    public function getUserActive(): ?bool
    {
        return $this->userActive;
    }

    /**
     * List of active users. It can be ID or userName
     */
    public function getUserActiveList(): array
    {
        return $this->userActiveList ?? [];
    }

    /**
     * List of skipped users. It can be ID or userName
     */
    public function getUserSkipList(): array
    {
        return $this->userSkipList ?? [];
    }

    /**
     * User password
     */
    public function getUserPassword(): ?string
    {
        return $this->userPassword;
    }

    /**
     * Skip export / import customization
     */
    public function getSkipCustomization(): bool
    {
        return $this->skipCustomization;
    }

    /**
     * Export / import all customization
     */
    public function getAllCustomization(): bool
    {
        return $this->allCustomization;
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
     * Skip export / import config
     */
    public function getSkipConfig(): bool
    {
        return $this->skipConfig;
    }

    /**
     * List of ignore config params
     */
    public function getConfigIgnoreList(): array
    {
        return $this->configIgnoreList ?? [];
    }

    /**
     * List of hard list params
     */
    public function getConfigHardList(): array
    {
        return $this->configHardList ?? [];
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
     * Get skipRelatedEntities option
     */
    public function getSkipRelatedEntities(): bool
    {
        return $this->skipRelatedEntities ?? false;
    }

    public function getEntityImportTypeCreate(): ?array
    {
        return $this->entityImportTypeCreate;
    }

    public function getEntityImportTypeUpdate(): ?array
    {
        return $this->entityImportTypeUpdate;
    }

    public function getEntityImportTypeCreateAndUpdate(): ?array
    {
        return $this->entityImportTypeCreateAndUpdate;
    }

    public function getAllAttributes(): bool
    {
        return $this->allAttributes;
    }

    public function getCompareType(): string
    {
        return $this->compareType ?? self::COMPARE_TYPE_ALL;
    }

    public function getSkipModifiedAt(): bool
    {
        return $this->skipModifiedAt;
    }

    public function getSkipStream(): bool
    {
        return $this->skipStream;
    }

    public function getSkipActionHistory(): bool
    {
        return $this->skipActionHistory;
    }

    public function getSkipWorkflowLog(): bool
    {
        return $this->skipWorkflowLog;
    }

    public function getLogLevel(): ?string
    {
        return $this->logLevel;
    }

    public function getFromDate(): ?DateTime
    {
        return $this->fromDate;
    }

    public function getSkipAttributeList(): ?array
    {
        return $this->skipAttributeList;
    }
}
