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

namespace Espo\Modules\ExportImport\Tools;

use Exception;
use Espo\Core\Utils\Log;
use Espo\Core\Utils\Metadata;
use Espo\Core\Exceptions\Error;
use Espo\Core\InjectableFactory;
use Espo\Modules\ExportImport\Tools\Tool;
use Espo\Modules\ExportImport\Tools\Params;
use Espo\Modules\ExportImport\Tools\Manifest;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Modules\ExportImport\Tools\Processor\ProcessHook;
use Espo\Modules\ExportImport\Tools\Manifest\ManifestWriter;
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
        private Metadata $metadata,
        private EntityTool $entityTool,
        private EntityTypeHelper $entityTypeHelper,
        private InjectableFactory $injectableFactory,
        private IdMappingTool $idMappingTool,
        private FileManager $fileManager
    ) {}

    public function run(Params $params) : void
    {
        $format = $params->getFormat() ?? null;
        $path = $params->getPath() ?? null;
        $resultPath = $params->getResultPath() ?? null;

        if (!$format) {
            throw new Error('Option "format" is not defined.');
        }

        if (!$path) {
            throw new Error('Path is not defined.');
        }

        if (!$resultPath) {
            throw new Error('Result path is not defined.');
        }

        if (!file_exists($path)) {
            throw new Error("Path \"{$path}\" does not exist.");
        }

        $this->clearResultDir($resultPath);

        $manifest = $this->injectableFactory->createWith(Manifest::class, [
            'params' => $params,
        ]);

        $this->processConfig($params, $manifest);

        $this->processCustomization($params, $manifest);

        $this->processData($params, $manifest);

        $this->createManifest($params);

        ProcessorUtils::writeList($params, $this->warningList, "Warnings:");

        ProcessorUtils::writeNewLine($params);

        ProcessorUtils::writeLine(
            $params,
            "For more information, check the result stored " .
            "in \"" . $params->getResultPath() ."\"."
        );
    }

    private function getEntityTypeList(Params $params): array
    {
        return $this->entityTypeHelper->getNormalizedList($params);
    }

    private function clearResultDir(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        if (!is_dir($path)) {
            throw new Error("Result path \"{$path}\" is not a directory.");
        }

        $this->fileManager->removeInDir($path);
    }

    private function processData(Params $params, Manifest $manifest): void
    {
        if ($params->getSkipData()) {
            return;
        }

        ProcessorUtils::writeNewLine($params);

        $entityTypeList = $this->getEntityTypeList($params);

        $idMap = $this->idMappingTool->getIdMap($params);

        foreach ($entityTypeList as $entityType) {
            ProcessorUtils::writeLine($params, "{$entityType}...");

            try {
                $result = $this->processEntity($entityType, $params, $manifest, $idMap);
            }
            catch (Exception $e) {
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
        $compare = $this->injectableFactory->create(CompareTool::class);

        $result = $compare->run(CompareParams::fromRaw([
            'entityType' => $entityType,
            'format' => $params->getFormat(),
            'path' => $params->getPath(),
            'resultPath' => $params->getResultPath(),
            'exportImportDefs' => $params->getExportImportDefs(),
            'manifest' => $manifest,
            'processHookClass' => $this->getProcessHookClass($entityType),
            'entitiesPath' => $params->getEntitiesPath(),
            'filesPath' => $params->getFilesPath(),
            'userSkipList' => $params->getUserSkipList(),
            'isCustomEntity' => $this->entityTool->isCustom($entityType),
            'compareType' => $params->getCompareType(),
            'idMap' => $idMap,
            'fromDate' => $params->getFromDate(),
            'skipModifiedAt' => $params->getSkipModifiedAt(),
            'skipStream' => $params->getSkipStream(),
            'skipActionHistory' => $params->getSkipActionHistory(),
            'skipWorkflowLog' => $params->getSkipWorkflowLog(),
            'logLevel' => $params->getLogLevel(),
            'allAttributes' => $params->getAllAttributes(),
            'prettyPrint' => $params->getPrettyPrint(),
            'skipAttributeList' => ProcessorUtils::getListForEntity(
                $entityType,
                $params->getSkipAttributeList()
            ),
        ]));

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

    private function createManifest(Params $params): void
    {
        $compareParams = CompareParams::create('Dummy')
            ->withResultPath($params->getResultPath());

        $this->createManifestForPath($params, $compareParams->getChangedPrevPath());
        $this->createManifestForPath($params, $compareParams->getChangedActualPath());

        if ($params->getLogLevel() != Params::LOG_LEVEL_INFO) {
            return;
        }

        $this->createManifestForPath($params, $compareParams->getSkippedPrevPath());
        $this->createManifestForPath($params, $compareParams->getSkippedActualPath());
    }

    private function createManifestForPath(Params $params, string $path): void
    {
        $manifestFile = $this->metadata->get(['app', 'exportImport', 'manifestFile']);

        $this->injectableFactory
            ->createWith(ManifestWriter::class, [
                'params' => $params,
            ])
            ->setManifestFile($path . '/' . $manifestFile)
            ->save();
    }
}
