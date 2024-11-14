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

use Exception;
use Espo\ORM\Defs;
use Espo\Core\Utils\Log;
use Espo\Core\DataManager;
use Espo\Core\Utils\Metadata;
use Espo\Core\Exceptions\Error;
use Espo\Core\InjectableFactory;
use Espo\Entities\User as UserEntity;
use Espo\Modules\ExportImport\Tools\Params;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Entities\Preferences as PreferencesEntity;
use Espo\Modules\ExportImport\Tools\Core\User as UserTool;
use Espo\Modules\ExportImport\Tools\Processor\ProcessHook;
use Espo\Modules\ExportImport\Tools\Core\Entity as EntityTool;
use Espo\Modules\ExportImport\Tools\Compare\Compare as CompareTool;
use Espo\Modules\ExportImport\Tools\Compare\Result as EntityResult;
use Espo\Modules\ExportImport\Tools\Compare\Params as CompareParams;
use Espo\Modules\ExportImport\Tools\IdMapping\Tool as IdMappingTool;
use Espo\Modules\ExportImport\Tools\Processor\Utils as ProcessorUtils;
use Espo\Modules\ExportImport\Tools\Import\Helpers\EntityType as EntityTypeHelper;

class Compare implements Tool
{
    private array $warningList = [];

    public function __construct(
        private Log $log,
        private Defs $defs,
        private UserTool $userTool,
        private Metadata $metadata,
        private EntityTool $entityTool,
        private FileManager $fileManager,
        private DataManager $dataManager,
        private EntityTypeHelper $entityTypeHelper,
        private InjectableFactory $injectableFactory,
        private IdMappingTool $idMappingTool
    ) {}

    public function run(Params $params) : void
    {
        $format = $params->getFormat() ?? null;
        $path = $params->getPath() ?? null;

        if (!$format) {
            throw new Error('Option "format" is not defined.');
        }

        if (!$path) {
            throw new Error('Path is not defined.');
        }

        if (!file_exists($path)) {
            throw new Error("Path \"{$path}\" does not exist.");
        }

        $manifest = $this->injectableFactory->createWith(Manifest::class, [
            'params' => $params,
        ]);

        $this->processConfig($params, $manifest);

        $this->processCustomization($params, $manifest);

        $this->processData($params, $manifest);

        ProcessorUtils::writeList($params, $this->warningList, "Warnings:");

        ProcessorUtils::writeNewLine($params);
    }

    private function getEntityTypeList(Params $params): array
    {
        return $this->entityTypeHelper->getNormalizedList($params);
    }

    private function processData(Params $params, Manifest $manifest): void
    {
        if ($params->getSkipData()) {
            return;
        }

        ProcessorUtils::writeNewLine($params);

        $entityTypeList = $this->getEntityTypeList($params);

        $idMap = $this->idMappingTool->getIdMap($params, [
            UserEntity::ENTITY_TYPE,
            PreferencesEntity::ENTITY_TYPE,
        ]);

        foreach ($entityTypeList as $entityType) {
            ProcessorUtils::writeLine($params, "{$entityType}...");

            try {
                $result = $this->processEntity($entityType, $params, $manifest, $idMap);
            } catch (Exception $e) {
                ProcessorUtils::writeLine(
                    $params, "  Error: " . $e->getMessage()
                );

                $this->log->warning(
                    'ExportImport [' . $entityType . ']:' . $e->getMessage()
                );

                continue;
            }

            if ($result->getWarningList()) {
                $this->warningList = array_merge(
                    $this->warningList,
                    $result->getWarningList()
                );
            }
        }
    }

    private function processEntity(
        string $entityType,
        Params $params,
        Manifest $manifest,
        array $idMap
    ): EntityResult {
        $processHookClass = $this->getProcessHookClass($entityType);

        $eraseParams = CompareParams::create($entityType)
            ->withFormat($params->getFormat())
            ->withPath($params->getPath())
            ->withEntitiesPath($params->getEntitiesPath())
            ->withFilesPath($params->getFilesPath())
            ->withExportImportDefs($params->getExportImportDefs())
            ->withManifest($manifest)
            ->withProcessHookClass($processHookClass)
            ->withUserSkipList($params->getUserSkipList())
            ->withIsCustomEntity($this->entityTool->isCustom($entityType))
            ->withIdMap($idMap);

        $import = $this->injectableFactory->create(CompareTool::class);
        $import->setParams($eraseParams);

        $result = $import->run();

        ProcessorUtils::writeLine($params, $result->getMessage());

        return $result;
    }

    private function processCustomization(Params $params, Manifest $manifest): void
    {
        if ($params->getSkipCustomization()) {
            return;
        }

       // TODO: implement
    }

    private function processConfig(Params $params, Manifest $manifest): void
    {
        if ($params->getSkipConfig()) {
            return;
        }

        // TODO: implement
    }

    private function getProcessHookClass(string $entityType): ?ProcessHook
    {
        $processHookClassName = $this->metadata->get([
            'app', 'exportImport', 'compareProcessHookClassNameMap', $entityType
        ]);

        if (!$processHookClassName || !class_exists($processHookClassName)) {
            return null;
        }

        return $this->injectableFactory->create($processHookClassName);
    }
}
