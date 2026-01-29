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

namespace Espo\Modules\ExportImport\Tools\Export;

use Espo\Modules\ExportImport\Tools\Processor\Result as IResult;

class Result implements IResult
{
    private $entityType;

    private $storagePath;

    private $successCount = 0;

    private ?array $warningList = null;

    public function __construct(string $entityType)
    {
        $this->entityType = $entityType;
    }

    public static function create(string $entityType): self
    {
        return new self($entityType);
    }

    public function withStoragePath(string $storagePath): self
    {
        $obj = clone $this;

        $obj->storagePath = $storagePath;

        return $obj;
    }

    public function withSuccessCount(int $count): self
    {
        $obj = clone $this;

        $obj->successCount = $count;

        return $obj;
    }

    public function withWarningList(?array $textList): self
    {
        $obj = clone $this;

        $obj->warningList = $textList;

        return $obj;
    }

    public function getMessage(): ?string
    {
        return "  Total: " . $this->successCount;
    }

    public function getGlobalMessage(): ?string
    {
        return "Files saved at \"" . $this->storagePath ."\".";
    }

    public function getWarningList(): ?array
    {
        return !empty($this-> warningList) ? $this-> warningList : null;
    }

    /**
     * Get a target entity type.
     */
    public function getEntityType(): string
    {
        return $this->entityType;
    }

    /**
     * Get a storage path
     */
    public function getStoragePath(): string
    {
        return $this->storagePath;
    }

    /**
     * Get record count
     */
    public function getSuccessCount(): int
    {
        return $this->successCount;
    }
}
