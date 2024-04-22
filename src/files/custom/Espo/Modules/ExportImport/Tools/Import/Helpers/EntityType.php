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

namespace Espo\Modules\ExportImport\Tools\Import\Helpers;

use Espo\ORM\Defs;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\File\Manager as FileManager;

use Espo\Modules\ExportImport\Tools\Params;
use Espo\Modules\ExportImport\Tools\Core\Entity as EntityTool;
use Espo\Modules\ExportImport\Tools\Processor\Utils as ToolUtils;

class EntityType
{
    public const OPTION_IMPORT_DISABLED = 'importDisabled';

    public function __construct(
        private Defs $defs,
        private Metadata $metadata,
        private EntityTool $entityTool,
        private FileManager $fileManager
    ) {}

    public function getNormalizedList(Params $params): array
    {
        if ($params->getEntityTypeList()) {
            $list = $params->getEntityTypeList();
        }

        if (!isset($list)) {
            $list = $this->loadList($params);
        }

        $list = array_merge($list, $this->getRelatedList($params));

        $list = array_unique($list);

        $list = $this->filterList($params, $list);

        $list = ToolUtils::sortEntityTypeListByType($this->metadata, $list);

        return array_values($list);
    }

    private function getRelatedList(Params $params): array
    {
        if ($params->getSkipRelatedEntities()) {
            return [];
        }

        if (!$params->getEntityTypeList()) {
            return [];
        }

        $entityTypeList = $params->getEntityTypeList();

        return $this->entityTool->getRelatedEntitiesTypeList(
            $entityTypeList
        );
    }

    private function loadList(Params $params): array
    {
        $entityFileList = $this->fileManager->getFileList(
            $params->getEntitiesPath(),
            false,
            '\.json$'
        );

        $availableEntityTypeList = $this->defs->getEntityTypeList();

        $entityTypeList = [];

        foreach ($entityFileList as $entityType) {
            $normalizedEntityType = preg_replace('/\.json$/i', '', $entityType);

            if (!in_array($normalizedEntityType, $availableEntityTypeList)) {

                continue;
            }

            $entityTypeList[] = $normalizedEntityType;
        }

        return $entityTypeList;
    }

    private function filterList(Params $params, array $list): array
    {
        $filteredList = [];

        $defs = $params->getExportImportDefs();
        $skipList = $params->getEntityTypeSkipList() ?? [];

        foreach ($list as $entityType) {
            $isImportDisabled = $defs[$entityType][self::OPTION_IMPORT_DISABLED] ?? false;

            if ($isImportDisabled) {
                continue;
            }

            if (in_array($entityType, $skipList)) {
                continue;
            }

            if ($this->entityTool->isCategoryTreeAdditionalTable($entityType)) {
                continue;
            }

            $filteredList[] = $entityType;
        }

        return $filteredList;
    }
}
