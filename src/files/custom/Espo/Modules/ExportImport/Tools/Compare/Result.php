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
use Espo\Modules\ExportImport\Tools\Processor\Result as IResult;

class Result implements IResult
{
    private string $entityType;

    private int $totalCount = 0;

    private int $createdCount = 0;

    private int $modifiedCount = 0;

    private int $bothModifiedCount = 0;

    private int $deletedCount = 0;

    private int $skipCount = 0;

    private ?DateTime $fromDate;

    private ?array $warningList = null;

    public function __construct(string $entityType)
    {
        $this->entityType = $entityType;
    }

    public static function create(string $entityType): self
    {
        return new self($entityType);
    }

    public function withCreatedCount(int $count): self
    {
        $obj = clone $this;

        $obj->createdCount = $count;

        return $obj;
    }

    public function withModifiedCount(int $count): self
    {
        $obj = clone $this;

        $obj->modifiedCount = $count;

        return $obj;
    }

    public function withBothModifiedCount(int $count): self
    {
        $obj = clone $this;

        $obj->bothModifiedCount = $count;

        return $obj;
    }

    public function withDeletedCount(int $count): self
    {
        $obj = clone $this;

        $obj->deletedCount = $count;

        return $obj;
    }

    public function withTotalCount(int $count): self
    {
        $obj = clone $this;

        $obj->totalCount = $count;

        return $obj;
    }

    public function withSkipCount(int $count): self
    {
        $obj = clone $this;

        $obj->skipCount = $count;

        return $obj;
    }

    public function withWarningList(?array $textList): self
    {
        $obj = clone $this;

        $obj->warningList = $textList;

        return $obj;
    }

    public function withFromDate(?DateTime $fromDate): self
    {
        $obj = clone $this;

        $obj->fromDate = $fromDate;

        return $obj;
    }

    public function getMessage(): ?string
    {
        $message = "  Total: " . $this->totalCount;

        if ($this->createdCount) {
            $message .= ", created: " . $this->createdCount;
        }

        if ($this->modifiedCount) {
            $message .= ", modified: " . $this->modifiedCount;
        }

        if ($this->bothModifiedCount) {
            $message .= ", skipped (both modified): " . $this->bothModifiedCount;
        }

        if ($this->skipCount) {
            $message .= ", unmodified: " . $this->skipCount;
        }

        if ($this->deletedCount) {
            $message .= ", deleted: " . $this->deletedCount;
        }

        if ($this->fromDate) {
            $message .= "\n  Data compared since: " . $this->fromDate->format('Y-m-d H:i:s');
        }

        return $message;
    }

    /**
     * Get a target entity type.
     */
    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function getCreatedCount(): int
    {
        return $this->createdCount;
    }

    /**
     * Get modified record count
     */
    public function getModifiedCount(): int
    {
        return $this->modifiedCount;
    }

    /**
     * Get both modified record count
     */
    public function getBothModifiedCount(): int
    {
        return $this->bothModifiedCount;
    }

    /**
     * Get deleted record count
     */
    public function getDeletedCount(): int
    {
        return $this->deletedCount;
    }

    /**
     * Get total record count
     */
    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    /**
     * Get skip count
     */
    public function getSkipCount(): int
    {
        return $this->skipCount;
    }

    public function getWarningList(): ?array
    {
        return !empty($this-> warningList) ? $this-> warningList : null;
    }

    public function getFromDate(): ?DateTime
    {
        return $this->fromDate;
    }
}
