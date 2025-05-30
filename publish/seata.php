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
return [
    'application_id' => uniqid('', true),
    'tx_service_group' => 'my_test_tx_group',
    // DEFAULT_MODE = GlobalTransactionScanner:AT_MOD + GlobalTransactionScanner::MT_MOD
    'mode' => 1 + 2,
    'access_key' => '',
    'secret_key' => '',
    'commit_retry_count' => 5,
    'rollback_retry_count' => 5,
    'service' => [
        'disable_global_transaction' => false,
        'vgroup_mapping' => [
            'my_test_tx_group' => 'default',
        ],
        'default' => [
            'grouplist' => '127.0.0.1:8091',
        ],
    ],
    'store' => [
        // store mode: file、db
        'mod' => 'db',
        'file' => [
        ],
        'db' => [
        ],
    ],
    'server' => [
    ],
];
