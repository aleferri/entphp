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

namespace entphp\identity;

use basin\concepts\Identity;

/**
 * Description of PersistedIdentity
 *
 * @author Alessio
 */
class PersistedIdentity implements Identity {

    private $fields;
    private $values;

    public function __construct(array $fields, array $values) {
        $this->fields = $fields;
        $this->values = $values;
    }

    public function has_field(string $field) {
        return in_array( $field, $this->fields );
    }

    public function fields(): array {
        return $this->fields;
    }

    public function of(array $row): array {
        $refs = [];

        foreach ( $this->fields as $field ) {
            $refs[] = $row[ $field ];
        }

        return $refs;
    }

    public function fill_as_fk(array $data, string $prefix): array {
        foreach ( $this->fields as $field ) {
            $key = $prefix . '_' . $field . '_fk';
            $data[ $key ] = $this->values[ $field ];
        }
        return $data;
    }

}
