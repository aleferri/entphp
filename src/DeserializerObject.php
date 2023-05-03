<?php

/*
 * Copyright 2022 Alessio.
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

namespace archimetrica\entphp;

/**
 * Description of Deserializer
 *
 * @author Alessio
 */
class DeserializerObject implements Deserializer {

    private static function fetch_and_compose(string $callback, array $keys, array $content): mixed {
        $args = [];
        foreach ( $keys as $key ) {
            $args[] = $content[ $key ];
        }

        return call_user_func ( $callback, ...$args );
    }

    private $classname;
    private $class;
    private $properties;
    private $root;

    public function __construct(string $classname, \ReflectionClass $class, array $properties, string $root) {
        $this->classname = $classname;
        $this->class = $class;
        $this->properties = $properties;
        $this->root = $root;
    }

    public function list_properties() {
        return $this->properties;
    }

    public function format_select(): string {
        $props = [];
        foreach ( $this->properties as $prop ) {
            $elem = $prop[ 'attribute' ]->settings;
            if ( $elem[ 'kind' ] === 'composite' ) {
                foreach ( $elem[ 'parts' ] as $part ) {
                    $props[] = $part;
                }
            } else if ( $elem[ 'kind' ] === 'collection' ) {

            } else if ( $elem[ 'kind' ] === 'expression' ) {
                $props[] = $elem[ 'expr' ] . ' AS ' . $prop[ 'key' ];
            } else {
                $props[] = $elem[ 'key' ];
            }
        }

        return implode ( ', ', $props );
    }

    public function flatten_sources(): array {
        $sources = [];
        foreach ( $this->properties as $prop ) {
            $elem = $prop[ 'attribute' ]->settings;
            if ( $elem[ 'kind' ] === 'composite' ) {
                foreach ( $elem[ 'parts' ] as $part ) {
                    [ $ns, _ ] = explode ( '.', $part );
                    $sources[ $ns ] = 0;
                }
            } else if ( $elem[ 'kind' ] === 'collection' ) {

            } else if ( $elem[ 'kind' ] === 'expression' ) {
                foreach ( $elem[ 'sources' ] as $source ) {
                    $sources[ $source ] = 0;
                }
            } else {
                [ $ns, _ ] = explode ( '.', $elem );
                $sources[ $ns ] = 0;
            }
        }

        return [ $this->root, array_keys ( $sources ) ];
    }

    public function process(array $data): array {
        $objects = [];

        foreach ( $data as $record ) {
            $args = [];

            foreach ( $this->properties as $prop ) {
                $elem = $prop[ 'attribute' ]->settings;
                if ( $elem[ 'kind' ] === 'composite' ) {
                    $args[] = self::fetch_and_compose ( $elem[ 'compose' ], $elem[ 'parts' ], $record );
                } else if ( $elem[ 'kind' ] === 'collection' ) {
                    $args[] = [];
                } else {
                    $args[] = $record[ $prop[ 'key' ] ];
                }
            }

            $object = $this->class->newInstance ( ...$args );
            $objects[] = $object;
        }

        return $objects;
    }

}
