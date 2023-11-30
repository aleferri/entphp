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

namespace entphp\datatypes;

use basin\concepts\convert\Deserializer;

/**
 * Description of Properties
 *
 * @author Alessio
 */
class Properties {

    private $map;

    public function __construct(array $map) {
        $this->map = $map;
    }

    public function converter(string $name): Deserializer|callable|null {
        $data = $this->map[ $name ];

        return $data[ 'converter' ] ?? null;
    }

    public function field(string $name): ?string {
        $data = $this->map[ $name ];

        return $data[ 'field' ] ?? null;
    }

    public function fields(string $name): array {
        $data = $this->map[ $name ];

        return $data[ 'fields' ] ?? null;
    }

    public function default(string $name): mixed {
        $data = $this->map[ $name ];

        return $data[ 'default' ] ?? null;
    }

    public function info(string $name): array {
        return $this->map[ $name ];
    }

    public function all(): array {
        return $this->map;
    }

    public function set(string $name, string $key, mixed $value): void {
        $this->map[ $name ][ $key ] = $value;
    }

    public function local_sourced_properties(): array {
        $locals = [];

        foreach ( $this->map as $name => $info ) {
            $location = $info[ 'location' ] ?? 'local';

            if ( $location === 'local' ) {
                $locals[ $name ] = $info;
            }
        }

        return $locals;
    }

    public function foreign_sourced_properties(): array {
        $foreign = [];

        foreach ( $this->map as $name => $info ) {
            $location = $info[ 'location' ] ?? 'local';

            if ( $location === 'foreign' ) {
                $foreign[ $name ] = $info;
            }
        }

        return $foreign;
    }
}
