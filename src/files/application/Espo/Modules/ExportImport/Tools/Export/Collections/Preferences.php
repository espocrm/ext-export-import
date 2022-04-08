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

namespace Espo\Modules\ExportImport\Tools\Export\Collections;

use Espo\Core\{
    Di,
};

use Espo\{
    ORM\Query\Select,
    ORM\EntityCollection,
};

use Espo\Modules\ExportImport\Tools\Export\{
    Collection
};

class Preferences implements

    Collection,
    Di\EntityManagerAware
{
    use Di\EntityManagerSetter;

    public function getCollection(Select $query): EntityCollection
    {
        $collection = $this->entityManager
            ->getRDBRepository('User')
            ->where([
                'type!=' => 'system',
            ])
            ->find();

        $repository = $this->entityManager->getRDBRepository('Preferences');

        $entityList = [];

        foreach ($collection as $user) {
            $entityList[] = $repository->get($user->id);
        }

        $obj = new EntityCollection($entityList, 'Preferences');

        $obj->setAsFetched();

        return $obj;
    }
}