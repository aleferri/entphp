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
 * Description of TransientIdentity
 *
 * @author Alessio
 */
class TransientIdentity implements Identity {

    private $fields;
    private $transients;

    public function __construct(array $fields, array $transients) {
        $this->fields = $fields;
        $this->transients = $transients;
    }

    public function has_field(string $field) {
        return in_array( $field, $this->fields );
    }

    public function fields(): array {
        return $this->fields;
    }

    public function values(): array {
        return $this->transients;
    }

    public function replace_with_transient(string $field) {
        return '__transient_' . $field;
    }

    public function fill_transients(array $data): array {
        foreach ( $this->fields as $field ) {
            if ( ! isset( $data[ $field ] ) ) {
                $transient_field = '__transient_' . $field;
                $data[ $transient_field ] = $this->transients[ $field ];
            }
        }
        return $data;
    }

    public function fill_as_fk(array $data, string $prefix): array {
        foreach ( $this->fields as $field ) {
            $key = $prefix . '_' . $field . '_fk';
            $transient_key = '__transient_' . $key;
            $data[ $key ] = $this->transients[ $field ];

            if ( ! isset( $data[ '__transient_patches' ] ) ) {
                $data[ '__transient_patches' ] = [];
            }
            $data[ '__transient_patches' ][ $transient_key ] = $key;
        }
        return $data;
    }

}
