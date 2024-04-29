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

namespace Espo\Modules\ExportImport\Tools\IdMapping;

use Espo\Core\Utils\Json as JsonUtil;

use Espo\Modules\ExportImport\Tools\Processor\Params;
use Espo\Modules\ExportImport\Tools\Core\Relation as RelationTool;
use Espo\Modules\ExportImport\Tools\Import\Params as ImportParams;

use Exception;

class IdReplacer
{
    public function __construct(
        private RelationTool $relationTool,
    ) {}

    public function processString(
        Params $params,
        array &$row,
        string $attributeName
    ): void {
        $id = $row[$attributeName] ?? null;

        if (!$id) {
            return;
        }

        if (!is_string($id)) {
            return;
        }

        $newId = $this->getNewReplaceId($params, $attributeName, $id);

        if (!$newId) {
            return;
        }

        $row[$attributeName] = $newId;
    }

    public function processJsonObject(
        Params $params,
        array &$row,
        string $attributeName
    ): void {
        $data = $row[$attributeName] ?? null;

        if (!$data) {
            return;
        }

        $isDecode = false;

        if (!is_string($data)) {
            $isDecode = true;

            try {
                $data = JsonUtil::encode($data);
            }
            catch (Exception $e) {
                return;
            }
        }

        $isChanged = $this->replaceString($params, $data);

        if (!$isChanged) {
            return;
        }

        if (!$this->isJsonValid($data)) {
            return;
        }

        if ($isDecode) {
            $data = JsonUtil::decode($data);
        }

        $row[$attributeName] = $data;
    }

    public function processText(
        Params $params,
        array &$row,
        string $attributeName
    ): void {
        $data = $row[$attributeName] ?? null;

        if (!$data || !is_string($data)) {
            return;
        }

        $isChanged = $this->replaceString($params, $data);

        if (!$isChanged) {
            return;
        }

        $row[$attributeName] = $data;
    }

    public function getReplacedString(
        Params $params,
        ?string $data
    ): string {
        if (!$data || !is_string($data)) {
            return $data;
        }

        $this->replaceString($params, $data);

        return $data;
    }

    private function replaceString(
        Params $params,
        string &$value
    ): bool {
        $isSave = false;

        $changedValue = $this->replaceAllOccurrences(
            $params, $value, ["'", '"', '\\\\"'], $isSave
        );

        if (!$isSave) {
            return false;
        }

        $value = $changedValue;

        return true;
    }

    private function getNewReplaceId(
        Params $params,
        string $attributeName,
        string $actualId
    ): ?string {
        if (!method_exists($params, 'getIdMap')) {
            return null;
        }

        /** @var ImportParams $params */
        $idMap = $params->getIdMap();

        foreach ($idMap as $entityType => $entityIdMap) {
            $newId = $entityIdMap[$actualId] ?? null;

            if (!$newId) {
                continue;
            }

            $isRelatedToEntity = $this->relationTool
                ->isAttributeRelatedTo(
                    $params->getEntityType(),
                    $attributeName,
                    $entityType
                );

            if (!$isRelatedToEntity) {
                continue;
            }

            return $newId;
        }

        return null;
    }

    /**
     * Find and replace all occurrences
     */
    private function replaceAllOccurrences(
        Params $params,
        string $value,
        array $delimiterList = [''],
        bool &$isChanged = false
    ): string {
        if (!method_exists($params, 'getIdMap')) {
            return $value;
        }

        /** @var ImportParams $params */
        $idMap = $params->getIdMap();

        foreach ($idMap as $entityType => $entityIdMap) {
            foreach ($entityIdMap as $fromId => $toId) {
                foreach ($delimiterList as $delimiter) {
                    $count = 0;

                    $value = preg_replace(
                        '#' . $delimiter . $fromId . $delimiter . '#',
                        $delimiter . $toId . $delimiter,
                        $value,
                        -1,
                        $count
                    );

                    if ($count > 0) {
                        $isChanged = true;
                    }
                }
            }
        }

        return $value;
    }

    private function isJsonValid(string $data): bool
    {
        try {
            JsonUtil::decode($data);
        }
        catch (Exception $e) {
            return false;
        }

        return true;
    }
}
