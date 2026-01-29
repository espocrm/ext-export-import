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

namespace Espo\Modules\ExportImport\Tools\Import\Placeholder;

use LogicException;
use Espo\Core\Utils\Metadata;
use Espo\Core\InjectableFactory;
use Espo\Modules\ExportImport\Tools\Import\Placeholder\Actions\Action as PlaceholderAction;

class Factory
{
    private $injectableFactory;

    private $metadata;

    protected $actions = [];

    public function __construct(InjectableFactory $injectableFactory, Metadata $metadata)
    {
        $this->injectableFactory = $injectableFactory;
        $this->metadata = $metadata;
    }

    public function create(string $action): PlaceholderAction
    {
        $className = $this->metadata->get(
            ['app', 'exportImport', 'placeholderActionClassNameMap', $action]
        );

        if (!$className) {
            throw new LogicException("No implementation placeholder action '{$action}'.");
        }

        return $this->injectableFactory->create($className);
    }

    public function get(string $action): PlaceholderAction
    {
        if (!isset($this->actions[$action])) {
            $this->actions[$action] = $this->create($action);
        }

        return $this->actions[$action];
    }
}
