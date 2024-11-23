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

use DateTime;
use RuntimeException;
use Espo\Core\Utils\Util;
use Espo\Modules\ExportImport\Tools\Manifest;
use Espo\Modules\ExportImport\Tools\Params as ToolParams;
use Espo\Modules\ExportImport\Tools\Processor\ProcessHook;
use Espo\Modules\ExportImport\Tools\Processor\Params as IParams;

class Params implements IParams
{
    private const PATH_CHANGED_ACTUAL = 'changed/actual';

    private const PATH_CHANGED_PREV = 'changed/prev';

    private const PATH_SKIPPED_ACTUAL = 'skipped/actual';

    private const PATH_SKIPPED_PREV = 'skipped/prev';

    private string $entityType;

    private string $path;

    private string $resultPath;

    private string $format;

    private ?array $exportImportDefs;

    private Manifest $manifest;

    private ?ProcessHook $processHookClass;

    private string $entitiesPath;

    private string $filesPath;

    private bool $isCustomEntity = false;

    private ?array $userSkipList;

    private array $idMap = [];

    private array $skipAttributeList;

    private ?DateTime $fromDate;

    private string $compareType;

    private bool $skipModifiedAt;

    private bool $skipStream;

    private bool $skipActionHistory;

    private bool $skipWorkflowLog;

    private ?string $logLevel;

    private bool $prettyPrint;

    private bool $allAttributes;

    public function __construct(string $entityType)
    {
        $this->entityType = $entityType;
    }

    public static function create(string $entityType): self
    {
        return new self($entityType);
    }

    public static function fromRaw(array $params): self
    {
        $format = $params['format'] ?? null;
        $entityType = $params['entityType'] ?? null;
        $path = $params['path'] ?? null;
        $resultPath = $params['resultPath'] ?? null;

        if (!$format) {
            throw new RuntimeException('Option "format" is not defined.');
        }

        if (!$entityType) {
            throw new RuntimeException('Option "entityType" is not defined.');
        }

        if ($path === $resultPath) {
            throw new RuntimeException('Option "resultPath" should be different from "path".');
        }

        $obj = new self($entityType);

        $obj->format = $format;
        $obj->path = $path;
        $obj->resultPath = $resultPath;
        $obj->exportImportDefs = $params['exportImportDefs'] ?? null;
        $obj->manifest = $params['manifest'] ?? null;
        $obj->processHookClass = $params['processHookClass'] ?? null;
        $obj->entitiesPath = $params['entitiesPath'] ?? null;
        $obj->filesPath = $params['filesPath'] ?? null;
        $obj->userSkipList = $params['userSkipList'] ?? null;
        $obj->isCustomEntity = $params['isCustomEntity'] ?? false;
        $obj->compareType = $params['compareType'] ?? null;
        $obj->idMap = $params['idMap'] ?? null;
        $obj->skipAttributeList = $params['skipAttributeList'] ?? [];
        $obj->fromDate = $params['fromDate'] ?? null;
        $obj->skipModifiedAt = $params['skipModifiedAt'] ?? false;
        $obj->skipStream = $params['skipStream'] ?? false;
        $obj->skipActionHistory = $params['skipActionHistory'] ?? false;
        $obj->skipWorkflowLog = $params['skipWorkflowLog'] ?? false;
        $obj->logLevel = $params['logLevel'] ?? null;
        $obj->prettyPrint = $params['prettyPrint'] ?? false;
        $obj->allAttributes = $params['allAttributes'] ?? false;

        if ($obj->skipModifiedAt) {
            $obj->skipAttributeList[] = 'modifiedAt';
        }

        return $obj;
    }

    public function withFormat(string $format): self
    {
        $obj = clone $this;

        $obj->format = $format;

        return $obj;
    }

    public function withPath(string $path): self
    {
        $obj = clone $this;

        $obj->path = $path;

        return $obj;
    }

    public function withResultPath(string $path): self
    {
        $obj = clone $this;

        $obj->resultPath = $path;

        return $obj;
    }

    public function withEntitiesPath(string $path): self
    {
        $obj = clone $this;

        $obj->entitiesPath = $path;

        return $obj;
    }

    public function withFilesPath(string $path): self
    {
        $obj = clone $this;

        $obj->filesPath = $path;

        return $obj;
    }

    public function withManifest(Manifest $manifest): self
    {
        $obj = clone $this;

        $obj->manifest = $manifest;

        return $obj;
    }

    public function withProcessHookClass(ProcessHook $processHookClass): self
    {
        $obj = clone $this;

        $obj->processHookClass = $processHookClass;

        return $obj;
    }

    public function withExportImportDefs(array $exportImportDefs): self
    {
        $obj = clone $this;

        $obj->exportImportDefs = $exportImportDefs;

        return $obj;
    }

    public function withUserSkipList(array $userSkipList): self
    {
        $obj = clone $this;

        $obj->userSkipList = $userSkipList;

        return $obj;
    }

    public function withIdMap(array $idMap): self
    {
        $obj = clone $this;

        $obj->idMap = $idMap;

        return $obj;
    }

    public function withSkipAttributeList(array $skipAttributeList): self
    {
        $obj = clone $this;

        $obj->skipAttributeList = $skipAttributeList;

        return $obj;
    }

    public function withSkipModifiedAt(bool $skipModifiedAt): self
    {
        $obj = clone $this;

        $obj->skipModifiedAt = $skipModifiedAt;

        return $obj;
    }

    public function withSkipStream(bool $skipStream): self
    {
        $obj = clone $this;

        $obj->skipStream = $skipStream;

        return $obj;
    }

    public function withSkipActionHistory(bool $skipActionHistory): self
    {
        $obj = clone $this;

        $obj->skipActionHistory = $skipActionHistory;

        return $obj;
    }

    public function withSkipWorkflowLog(bool $skipWorkflowLog): self
    {
        $obj = clone $this;

        $obj->skipWorkflowLog = $skipWorkflowLog;

        return $obj;
    }

    public function withLogLevel(?string $logLevel): self
    {
        $obj = clone $this;

        $obj->logLevel = $logLevel;

        return $obj;
    }

    public function withPrettyPrint(bool $prettyPrint): self
    {
        $obj = clone $this;

        $obj->prettyPrint = $prettyPrint;

        return $obj;
    }

    public function withAllAttributes(bool $allAttributes): self
    {
        $obj = clone $this;

        $obj->allAttributes = $allAttributes;

        return $obj;
    }

    public function withIsCustomEntity(bool $isCustomEntity): self
    {
        $obj = clone $this;

        $obj->isCustomEntity = $isCustomEntity;

        return $obj;
    }

    public function withCompareType(string $compareType): self
    {
        $obj = clone $this;

        $obj->compareType = $compareType;

        return $obj;
    }

    public function withFromDate(DateTime $fromDate): self
    {
        $obj = clone $this;

        $obj->fromDate = $fromDate;

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
     * Get result path.
     */
    public function getResultPath(): ?string
    {
        return $this->resultPath;
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
     * Get a path for changed actual data
     */
    public function getChangedActualPath(): string
    {
        return Util::concatPath(
            $this->resultPath,
            self::PATH_CHANGED_ACTUAL
        );
    }

    /**
     * Get a path for changed previous data
     */
    public function getChangedPrevPath(): string
    {
        return Util::concatPath(
            $this->resultPath,
            self::PATH_CHANGED_PREV
        );
    }

    /**
     * Get a path for skipped previous data
     */
    public function getSkippedPrevPath(): string
    {
        return Util::concatPath(
            $this->resultPath,
            self::PATH_SKIPPED_PREV
        );
    }

    /**
     * Get a path for skipped actual data
     */
    public function getSkippedActualPath(): string
    {
        return Util::concatPath(
            $this->resultPath,
            self::PATH_SKIPPED_ACTUAL
        );
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

    /**
     * Get a skip attribute list
     */
    public function getSkipAttributeList(): array
    {
        return $this->skipAttributeList;
    }

    /**
     * Check if an attribute is skipped
     */
    public function isAttributeSkipped(string $attributeName): bool
    {
        return in_array($attributeName, $this->skipAttributeList);
    }

    /**
     * Get from date
     */
    public function getFromDate(): ?DateTime
    {
        return $this->fromDate;
    }

    /**
     * Get compare type
     */
    public function getCompareType(): string
    {
        return $this->compareType;
    }

    /**
     * Check if a compare type is "created"
     */
    public function isCreatedType(): bool
    {
        if (in_array($this->compareType, [
            ToolParams::COMPARE_TYPE_ALL,
            ToolParams::COMPARE_TYPE_CREATED
        ])) {
            return true;
        }

        return false;
    }

    /**
     * Check if a compare type is "updated"
     */
    public function isUpdatedType(): bool
    {
        if (in_array($this->compareType, [
            ToolParams::COMPARE_TYPE_ALL,
            ToolParams::COMPARE_TYPE_UPDATED
        ])) {
            return true;
        }

        return false;
    }

    /**
     * Check if a compare type is "deleted"
     */
    public function isDeletedType(): bool
    {
        if (in_array($this->compareType, [
            ToolParams::COMPARE_TYPE_ALL,
            ToolParams::COMPARE_TYPE_DELETED
        ])) {
            return true;
        }

        return false;
    }

    /**
     * Is skip the modifiedAt attribute
     */
    public function getSkipModifiedAt(): bool
    {
        return $this->skipModifiedAt ?? false;
    }

    /**
     * Is skip the modification message in stream
     */
    public function getSkipStream(): bool
    {
        return $this->skipStream ?? false;
    }

    /**
     * Is skip the action history
     */
    public function getSkipActionHistory(): bool
    {
        return $this->skipActionHistory ?? false;
    }

    /**
     * Is skip the workflow log
     */
    public function getSkipWorkflowLog(): bool
    {
        return $this->skipWorkflowLog ?? false;
    }

    public function getLogLevel(): ?string
    {
        return $this->logLevel;
    }

    public function isInfoLevel(): bool
    {
        return $this->logLevel == ToolParams::LOG_LEVEL_INFO;
    }

    public function isDebugLevel(): bool
    {
        return $this->logLevel == ToolParams::LOG_LEVEL_DEBUG;
    }

    public function getPrettyPrint(): bool
    {
        return $this->prettyPrint ?? false;
    }

    public function getAllAttributes(): bool
    {
        return $this->allAttributes;
    }
}
