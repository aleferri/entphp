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

namespace entphp\meta;

use basin\concepts\Schema;
use entphp\serde\TableSchema;
use entphp\query\SQLFetchQueryBuilder;

class MetadataStore {

    private $metadata;
    private $context;

    public function __construct(string $context = 'sql') {
        $this->metadata = [];
        $this->context = $context;
    }

    public function visit(string $classname): array {
        if ( isset( $this->metadata[ $classname ] ) ) {
            return $this->metadata[ $classname ];
        }
        $class = new \ReflectionClass( $classname );

        $source = TableSchema::source_of( $class, $this->context );
        $schema = TableSchema::of_class( $class, $this->context );
        $identity_info = TableSchema::identity_of( $class, $this->context );

        // First register this to prevent recursion
        $this->metadata[ $classname ] = [ $source, $schema, $identity_info ];

        $patchset = [];

        foreach ( $schema->foreign_sourced_properties() as $key => $property ) {
            $dep_class = $property[ 'classname' ];
            [ $dep_source, $dep_schema ] = $this->visit( $dep_class );
            $property[ 'items_schema' ] = $dep_schema;
            $patchset[ $key ] = $property;
        }

        foreach ( $patchset as $key => $property ) {
            $schema->set_property_field( $key, 'items_schema', $property[ 'items_schema' ] );
        }

        return $this->metadata[ $classname ];
    }

    public function query_for(string $classname, array $values): SQLFetchQueryBuilder {
        $this->visit( $classname );
        [ $source, $schema, $identity_info ] = $this->metadata[ $classname ];

        $table = $source;
        $query = SQLFetchQueryBuilder::start()
                ->from( $table, 'ent' )
                ->select( 'ent.*' );

        foreach ( $values as $key => $list ) {
            $query = $query->filter_by( 'per_row_' . $key, $key . ' IN (' . implode( ',', $list ) . ')' );
        }

        return $query;
    }

    public function query_for_key(string $classname, string $key, mixed $id): SQLFetchQueryBuilder {
        $this->visit( $classname );
        [ $source, $schema, $identity_info ] = $this->metadata[ $classname ];

        $table = $source;
        $query = SQLFetchQueryBuilder::start()
                ->from( $table, 'ent' )
                ->select( 'ent.*' )
                ->filter_by( 'per_row_' . $key, $key . ' = ?', $id );

        return $query;
    }

    public function start_query(string $classname): SQLFetchQueryBuilder {
        $this->visit( $classname );
        [ $source, $schema, $identity_info ] = $this->metadata[ $classname ];

        $table = $source;
        $query = SQLFetchQueryBuilder::start()
                ->from( $table, 'ent' )
                ->select( 'ent.*' );

        return $query;
    }

    public function has(string $classname): bool {
        return isset( $this->metadata[ $classname ] );
    }

    public function get(string $classname): array {
        return $this->metadata[ $classname ];
    }

    public function foreigns_of(string $classname): array {
        [ $source, $schema, $identity_info ] = $this->metadata[ $classname ];

        return $schema->foreign_sourced_properties();
    }

    public function schema_of(string $classname): Schema {
        [ $source, $schema, $identity_info ] = $this->metadata[ $classname ];

        return $schema;
    }

    public function first_key_of(string $classname): array|null {
        [ $source, $schema, $identity_info ] = $this->metadata[ $classname ];
        if ( count( $identity_info ) === 0 ) {
            return null;
        }

        return $identity_info[ 0 ];
    }
}
