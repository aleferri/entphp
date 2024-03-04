<?php

/*
 * Copyright 2024 Alessio.
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
 * Description of DatabaseTest
 *
 * @author Alessio
 */
class TestDriver {

    private $primary_keys_by_table;

    public function __construct() {
        $this->primary_keys_by_table = [];
    }

    public function next_key(string $table): int {
        if ( ! isset( $this->primary_keys_by_table[ $table ] ) ) {
            $this->primary_keys_by_table[ $table ] = 0;
        }

        $this->primary_keys_by_table[ $table ] += 1;

        return $this->primary_keys_by_table[ $table ];
    }

}
