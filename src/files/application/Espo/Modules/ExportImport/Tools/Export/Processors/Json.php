<?php
/************************************************************************
 * This file is part of Export Import extension for EspoCRM.
 *
 * Export Import extension for EspoCRM.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Modules\ExportImport\Tools\Export\Processors;

use Espo\Core\ORM\Entity;

use Espo\Core\{
    Utils\Config,
    Utils\Metadata,
    Utils\Json as JsonUtil,
};

use Espo\Modules\ExportImport\Tools\Export\{
    Processor,
    Processor\Params,
    Processor\Data,
};

use Espo\Entities\Preferences;

use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Psr7\Stream;

use const JSON_UNESCAPED_UNICODE;
use const JSON_UNESCAPED_SLASHES;
use const JSON_PRETTY_PRINT;

class Json implements Processor
{
    protected $config;
    protected $preferences;
    protected $metadata;

    public function __construct(Config $config, Preferences $preferences, Metadata $metadata)
    {
        $this->config = $config;
        $this->preferences = $preferences;
        $this->metadata = $metadata;
    }

    public function addAdditionalAttributes($entityType, &$attributeList, $fieldList)
    {
        foreach ($fieldList as $fieldName) {
            $fieldType = $this->metadata->get(['entityDefs', $entityType, 'fields', $fieldName, 'type']);

            switch ($fieldType) {
                case 'email':
                case 'phone':
                    $additionalFieldName = $fieldName . 'Data';

                    if (!in_array($additionalFieldName, $attributeList)) {
                        $attributeList[] = $additionalFieldName;
                    }
                    break;
            }
        }
    }

    public function loadAdditionalFields(Entity $entity, $fieldList)
    {
        foreach ($fieldList as $field) {
            $fieldType = $this->metadata->get(['entityDefs', $entity->getEntityType(), 'fields', $field, 'type']);

            switch ($fieldType) {
                case 'linkMultiple':
                case 'attachmentMultiple':
                    if (!$entity->has($field . 'Ids')) {
                        $entity->loadLinkMultipleField($field);
                    }
                    break;
            }
        }
    }

    public function process(Params $params, Data $data): StreamInterface
    {
        $fp = fopen('php://temp', 'w');

        if ($data->isEmpty()) {
            return new Stream($fp);
        }

        $prettyPrint = $this->metadata->get(['app', 'exportImport', 'prettyPrint']) ?? false;

        fwrite($fp, "[\n");

        while (($row = $data->readRow()) !== null) {
            $preparedRow = $this->prepareRow($row, $prettyPrint);

            fwrite($fp, $preparedRow);

            $delimiter = $data->isEnd() ? "\n" : ",\n";

            fwrite($fp, $delimiter);
        }

        fwrite($fp, "]\n");

        rewind($fp);

        return new Stream($fp);
    }

    protected function prepareRow(array $row, bool $prettyPrint): string
    {
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

        if ($prettyPrint) {
            $flags |= JSON_PRETTY_PRINT;
        }

        return JsonUtil::encode($row, $flags);
    }
}