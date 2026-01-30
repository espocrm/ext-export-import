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

namespace Espo\Modules\ExportImport\Tools\Config;

use Espo\Core\Utils\Config;
use Espo\Core\InjectableFactory;
use Espo\Modules\ExportImport\Tools\Config\Params;

class Util
{
    public function __construct(
        private Config $config,
        private InjectableFactory $injectableFactory
    ) {}

    public function applyIgnoreList(Params $params, array $data): array
    {
        $ignoreList = $this->getAllIgnoreList($params);

        $data = $this->removeStateParams($params, $data);

        return array_diff_key($data, array_flip($ignoreList));
    }

    public function getAllIgnoreList(Params $params): array
    {
        $ignoreList = $params->getConfigIgnoreList();

        if ($params->getSkipInternalConfig()) {
            $ignoreList = array_merge($ignoreList, $this->config->get('systemItems'));
            $ignoreList = array_merge($ignoreList, $this->config->get('adminItems'));
            $ignoreList = array_merge($ignoreList, $this->config->get('superAdminItems'));
        }

        if ($params->getSkipPassword()) {
            $ignoreList = array_merge($ignoreList, Params::PASSWORD_PARAM_LIST);
        }

        $ignoreList = $this->applyHardList($params, $ignoreList);

        return $ignoreList;
    }

    private function applyHardList($params, array $ignoreList): array
    {
        $hardList = $params->getConfigHardList();

        if (empty($hardList)) {
            return $ignoreList;
        }

        foreach ($ignoreList as $key => $value) {
            if (!in_array($value, $hardList)) {
                continue;
            }

            unset($ignoreList[$key]);
        }

        return array_values($ignoreList);
    }

    private function getInternalConfigHelper(): ?object
    {
        $className = '\\Espo\\Core\\Utils\\Config\\InternalConfigHelper';

        if (!class_exists($className)) {
            return null;
        }

        $helper = $this->injectableFactory->create($className);

        if (!method_exists($helper, 'isParamForStateConfig')) {
            return null;
        }

        return $helper;
    }

    /**
     * Remove stat (data/stat.php) params from the export list
     * TODO: refactor from espo min version >= 9.3
     */
    private function removeStateParams(Params $params, array $data): array
    {
        $helper = $this->getInternalConfigHelper();

        if (!$helper) {
            return $data;
        }

        foreach ($data as $key => $value) {
            if ($helper->isParamForStateConfig($key)) {
                unset($data[$key]);
            }
        }

        return $data;
    }
}
