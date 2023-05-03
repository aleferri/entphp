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

namespace alessio\entphp;

use alessio\entphp\drivers\SQLDriver;

/**
 * Description of ReaderSQL
 *
 * @author Alessio
 */
class ReaderSQL implements Reader {

    private $links;
    private $columns;
    private $descriptors;
    private $driver;
    private $pdo;

    public function __construct(\PDO $pdo, SQLDriver $driver) {
        $this->pdo = $pdo;
        $this->links = [];
        $this->columns = [];
        $this->descriptors = [];
        $this->driver = $driver;
    }

    public function register_descriptor(EntityDescriptor $descriptor) {
        $this->descriptors[ $descriptor->entity_name() ] = $descriptor;
    }

    public function register_table(string $name, array $columns) {
        $this->columns[ $name ] = $columns;
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

    public function compare_primary_key(int|string|array $value, array $primary_keys) {
        if ( is_int( $value ) || is_string( $value ) ) {
            return [ [ $value ], $primary_keys[ 0 ] . ' = ?' ];
        }

        $comparisons = [];
        foreach ( $primary_keys as $key ) {
            $comparisons[] = "{$key} = ?";
        }

        return [ $value, implode( ' AND ', $comparisons ) ];
    }

    public function create_from(array $from_links, array &$all_values, array &$keys): string {
        $from_strs = '';

        foreach ( $from_links as $link ) {
            if ( isset( $keys[ $link->to ] ) ) {
                continue;
            }
            [ $str, $values ] = $link->link_map();
            array_push( $all_values, $values );
            $from_str = $link->render_from( $from_strs, $str );
            $from_strs .= "{$from_str} )";

            $keys[ $link->to ] = true;
        }

        return $from_strs;
    }

    public function descriptor_for(array|string $select, ?string $root = null): EntityDescriptor {
        if ( is_array( $select ) ) {
            $name = '__' . implode( '_', $select );

            if ( ! isset( $this->descriptors[ $name ] ) ) {
                $descriptor = EntityDescriptorBuilder::of_array( $name )
                    ->from( $root )
                    ->primary_key( $this->driver->find_primary_keys_for( $root ) )
                    ->map_all_same( ...$select )
                    ->into_descriptor();
                $this->descriptors[ $name ] = $descriptor;
            }
        } else {
            $name = $select;

            if ( ! isset( $this->descriptors[ $name ] ) ) {
                $descriptor = EntityDescriptorBuilder::of_class( $select )
                    ->autodiscover()
                    ->primary_keys_discoverer( [ $this->driver, 'find_primary_keys_for' ] )
                    ->into_descriptor();
                $this->descriptors[ $name ] = $descriptor;
            }
        }

        return $this->descriptors[ $name ];
    }

    public function fetch_all(array|string $select, ?string $root = null): array {
        $descriptor = $this->descriptor_for( $select, $root );
        $fetch_kind = ($descriptor->kind() === EntityDescriptor::ARRAY_ENTITY) ? \PDO::FETCH_ASSOC : \PDO::FETCH_CLASS;

        $root_table = $descriptor->root_table();
        $from_str = $root_table . ' ';
        $from_links = $descriptor->trace_all_links();

        $keys = [ $root_table => true ];
        $values = [];

        if ( count( $from_links ) > 0 ) {
            $from_str .= $this->create_from( $from_links, $values, $keys );
        }

        $select_string = implode( ', ', $descriptor->fully_qualified_keys() );

        $st = $this->pdo->prepare( "SELECT {$select_string} FROM {$from_str}" );
        $st->execute( $values );

        if ( $fetch_kind === \PDO::FETCH_ASSOC ) {
            return $st->fetchAll( $fetch_kind );
        }
        return $st->fetchAll( \PDO::FETCH_CLASS, $select );
    }

    public function fetch_by_primary(array|string $select, int|string|array $primary, ?string $root = null): array|object {
        $descriptor = $this->descriptor_for( $select, $root );
        $fetch_kind = ($descriptor->kind() === EntityDescriptor::ARRAY_ENTITY) ? \PDO::FETCH_ASSOC : \PDO::FETCH_CLASS;

        $root_table = $descriptor->root_table();
        $from_str = $root_table . ' ';
        $from_links = $descriptor->trace_all_links();

        $keys = [ $root_table => true ];
        $values = [];

        if ( count( $from_links ) > 0 ) {
            $from_str .= $this->create_from( $from_links, $values, $keys );
        }

        $select_string = implode( ', ', $descriptor->fully_qualified_keys() );

        [ $condition_values, $condition ] = $this->compare_primary_key( $primary, $descriptor->primary_key() );
        array_push( $values, ...$condition_values );

        $st = $this->pdo->prepare( "SELECT {$select_string} FROM {$from_str} WHERE {$condition}" );
        $st->execute( $values );

        if ( $fetch_kind === \PDO::FETCH_ASSOC ) {
            return $st->fetch( $fetch_kind );
        }

        $raw = $st->fetch( \PDO::FETCH_NUM );
        $class = new \ReflectionClass( $select );

        return $class->newInstanceArgs( $raw );
    }

    public function fetch_page(array|string $select, Condition $condition, Page $page, ?string $root = null): array {

    }

}
