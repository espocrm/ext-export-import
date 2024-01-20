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

namespace Espo\Modules\ExportImport\Tools\Import\Processor\Attributes;

use Espo\ORM\EntityManager;
use Espo\Core\Utils\Config;

use Espo\Modules\ExportImport\Tools\Import\Params;
use Espo\Modules\ExportImport\Tools\Import\ProcessorAttribute;

class FloatObj implements ProcessorAttribute
{
    private const TYPE_CURRENCY = 'currency';

    public function __construct(
        private Config $config,
        private EntityManager $entityManager
    ) {}

    public function process(Params $params, array &$row, string $attributeName): void
    {
        $type = $this->entityManager
            ->getDefs()
            ->getEntity($params->getEntityType())
            ->getAttribute($attributeName)
            ->getParam('fieldType');

        if ($type == self::TYPE_CURRENCY) {
            $this->processCurrency($params, $row, $attributeName);
        }
    }

    public function processCurrency(
        Params $params,
        array &$row,
        string $attributeName
    ): void {
        $isUpdateCurrency = $params->getUpdateCurrency();

        if (!$isUpdateCurrency) {
            return;
        }

        $currency = $params->getCurrency()
            ?? $this->config->get('defaultCurrency');

        $row[$attributeName . 'Currency'] = $currency;
    }
}
