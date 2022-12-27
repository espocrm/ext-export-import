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

namespace Espo\Modules\ExportImport\Tools\Import;

use Espo\Modules\ExportImport\Tools\{
    Processor\Result as IResult,
};

use const PHP_EOL;

class Result implements IResult
{
    private $entityType;

    private $successCount = 0;

    private $failCount = 0;

    public function __construct(string $entityType)
    {
        $this->entityType = $entityType;
    }

    public static function create(string $entityType): self
    {
        return new self($entityType);
    }

    public function withSuccessCount(int $count): self
    {
        $obj = clone $this;

        $obj->successCount = $count;

        return $obj;
    }

    public function withFailCount(int $count): self
    {
        $obj = clone $this;

        $obj->failCount = $count;

        return $obj;
    }

    public function getMessage(): ?string
    {
        $message = "  Total: " . $this->successCount;

        if ($this->failCount) {
            $message .= ", failed: " . $this->failCount;
        }

        return $message;
    }

    public function getGlobalMessage(): ?string
    {
        return null;
    }

    /**
     * Get a target entity type.
     */
    public function getEntityType(): string
    {
        return $this->entityType;
    }

    /**
     * Get record count
     */
    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    /**
     * Get record count
     */
    public function getFailCount(): int
    {
        return $this->failCount;
    }
}
