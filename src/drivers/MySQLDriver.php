<?php

/*
 * Copyright 2023 Alessio.
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
 */

namespace entphp\drivers;

/**
 * Description of MySQLDriver
 *
 * @author Alessio
 */
class MySQLDriver {

    public const PRIMITIVE_MAPPINGS = [
        'string'   => 'VARCHAR(255)',
        'int'      => 'INT',
        'float'    => 'FLOAT',
        'date'     => 'DATE',
        'datetime' => 'DATETIME',
        'time'     => 'TIME'
    ];

    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function find_primary_keys_for(string $table): array {
        $result = $this->pdo->query( "SHOW KEYS FROM {$table} WHERE Key_name = 'PRIMARY'" );

        $keys = [];
        foreach ( $result as $record ) {
            $keys[] = $record[ 'Column_name' ];
        }

        return $keys;
    }

    public function exec(string $query): mixed {
        return $this->pdo->exec( $query );
    }

    public function map_type(string $name): string {
        return self::PRIMITIVE_MAPPINGS[ $name ];
    }

}
