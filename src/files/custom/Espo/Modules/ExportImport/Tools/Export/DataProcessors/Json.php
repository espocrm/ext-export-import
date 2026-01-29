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

namespace Espo\Modules\ExportImport\Tools\Export\DataProcessors;

use Espo\Core\ORM\Entity;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\Entities\Preferences;
use Espo\Core\Utils\Json as JsonUtil;

use Espo\Modules\ExportImport\Tools\Export\Params;
use Espo\Modules\ExportImport\Tools\Processor\Data;
use Espo\Modules\ExportImport\Tools\Export\Processor;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

use GuzzleHttp\Psr7\Stream;
use Psr\Http\Message\StreamInterface;

class Json implements Processor
{
    public function __construct(
        private Config $config,
        private Preferences $preferences,
        private Metadata $metadata
    ) {}

    public function loadAdditionalFields(Entity $entity, $fieldList)
    {
        foreach ($fieldList as $field) {
            $fieldType = $this->metadata->get([
                'entityDefs', $entity->getEntityType(), 'fields', $field, 'type'
            ]);

            switch ($fieldType) {
                case 'linkMultiple':
                case 'attachmentMultiple':
                    if ($entity->hasLinkMultipleField($field)) {
                        $entity->loadLinkMultipleField($field);
                    }
                    break;
            }
        }
    }

    public function process(Params $params, Data $data): StreamInterface
    {
        $fp = fopen('php://temp', 'w');

        $data->rewind();

        if ($data->isEmpty()) {
            return new Stream($fp);
        }

        $prettyPrint = $params->getPrettyPrint();

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
