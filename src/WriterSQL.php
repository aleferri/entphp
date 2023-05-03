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

namespace alessio\entphp;

/**
 * Description of WriterSQL
 *
 * @author Alessio
 */
class WriterSQL implements Writer {

    private $pdo;
    private $links;
    private $descriptors;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
        $this->links = [];
        $this->descriptors = [];
    }

    public function register_link(Link $link) {
        $this->links[ $link->from ] = $link;
    }

    public function links_of(string $left, int $kind = Link::LINK_FLAT): array {
        $collect = [];

        foreach ( $this->links as $link ) {
            if ( $link->from === $left && ( $kind & $link->linkage ) !== 0 ) {
                $collect[] = $link;
            }
        }

        return $collect;
    }

    public function primary_key_of_table(string $table): array {
        $result = $this->pdo->query ( "SHOW KEYS FROM {$table} WHERE Key_name = 'PRIMARY'" );
        $keys = [];
        foreach ( $result as $record ) {
            $keys[] = $record[ 'Column_name' ];
        }

        return $keys;
    }

    public function register_descriptor(EntityDescriptor $descriptor) {
        $this->descriptors[ $descriptor->entity_name () ] = $descriptor;
    }

    public function compare_primary_key(int|string|array $value, array $primary_keys) {
        if ( is_int ( $value ) || is_string ( $value ) ) {
            return [ [ $value ], $primary_keys[ 0 ] . ' = ?' ];
        }

        $comparisons = [];
        foreach ( $primary_keys as $key ) {
            $comparisons[] = "{$key} = ?";
        }

        return [ $value, implode ( ' AND ', $comparisons ) ];
    }

    public function descriptor_for(array|string $select, ?string $root = null): EntityDescriptor {
        if ( is_array ( $select ) ) {
            $name = '__' . implode ( '_', $select );

            if ( ! isset ( $this->descriptors[ $name ] ) ) {
                $descriptor = EntityDescriptorBuilder::of_array ( $name )
                    ->from ( $root )
                    ->primary ( $this->primary_key_of_table ( $root ) )
                    ->map_all_same ( ...$select )
                    ->into_descriptor ();
                $this->descriptors[ $name ] = $descriptor;
            }
        } else {
            $name = $select;

            if ( ! isset ( $this->descriptors[ $name ] ) ) {
                $descriptor = EntityDescriptorBuilder::of_class ( $select )
                    ->autodiscover ()
                    ->primary ( $this->primary_key_of_table ( $descriptor->root_table () ) )
                    ->into_descriptor ();
                $this->descriptors[ $name ] = $descriptor;
            }
        }

        return $this->descriptors[ $name ];
    }

    public function store(array|object $value, ?string $root = null): array|object {

    }

    public function store_all(array $value, ?string $root = null): array {

    }

}
