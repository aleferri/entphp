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

namespace entphp\query\fetch;

/**
 * Description of Selection
 *
 * @author Alessio
 */
class Selection implements \basin\concepts\query\Selection {

    private $fields;
    private $alias_to;

    public function __construct(array $fields = [], array $alias_to = []) {
        $this->fields = $fields;
        $this->alias_to = $alias_to;
    }

    public function add(string $field, ?string $alias = null) {
        $this->fields[] = $field;
        $this->alias_to[] = $alias;
    }

    public function contains(string $name, bool $is_alias = false): bool {
        foreach ( $this->fields as $field ) {
            if ( $field === $name ) {
                return true;
            }
        }

        if ( $is_alias ) {
            foreach ( $this->alias_to as $alias ) {
                if ( $alias === $name ) {
                    return true;
                }
            }
        }

        return false;
    }

    public function fields(): array {
        return $this->fields;
    }

    private function is_sql_function(string $str): bool {
        return str_starts_with( $str, 'SUM(' ) || str_starts_with( $str, 'COUNT(' ) || str_starts_with( $str, 'AVG(' );
    }

    public function is_computed(string $name): bool {
        for ( $i = 0; $i < count( $this->fields ); $i++ ) {
            return ($this->alias_to[ $i ] === $name && $this->is_sql_function( $this->fields[ $i ] ) );
        }
        return false;
    }
}
