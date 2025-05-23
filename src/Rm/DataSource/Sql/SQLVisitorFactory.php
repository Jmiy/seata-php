<?php

declare(strict_types=1);
/**
 * Copyright 2019-2022 Seata.io Group.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 */
namespace Hyperf\Seata\Rm\DataSource\Sql;

use Hyperf\Seata\SqlParser\Core\SQLRecognizerFactory;
use Hyperf\Context\ApplicationContext;

class SQLVisitorFactory
{
    protected SQLRecognizerFactory $SQL_RECOGNIZER_FACTORY;

    public static function get(string $sql, string $dbType = 'mysql')
    {
        $container = ApplicationContext::getContainer();
        return $container->get(SQLRecognizerFactory::class)->create($sql, $dbType);
    }
}
