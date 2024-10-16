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

namespace entphp\query;

use basin\concepts\convert\Deserializer;
use plog\Logger;
use plog\LoggerAware;
use plog\NullLogger;
use entphp\serde\ObjectDeserializer;
use entphp\meta\MetadataStore;

/**
 * Description of SQLFetchPlanner
 *
 * @author Alessio
 */
class SQLFetchPlanner implements LoggerAware {

    private $definitions;
    private $net;
    private $executor;
    private $logger;
    private $metadata;

    public function __construct(\PDO $pdo, ?MetadataStore $metadata = null) {
        $this->definitions = [];
        $this->net = [];
        $this->executor = $pdo;
        $this->logger = new NullLogger();
        $this->metadata = $metadata;
        if ( $this->metadata === null ) {
            $this->metadata = new MetadataStore();
        }
    }

    private function execute_query($pdo, SQLFetchQuery $query) {
        $sql = $query->to_sql();

        $st = $pdo->prepare( $sql );

        $i = 1;
        foreach ( $query->values() as $value ) {
            $st->bindValue( $i, $value );

            $i++;
        }

        $st->execute();
        $records = $st->fetchAll();

        return $records;
    }

    public function find(FetchQuery $query): array {
        $records = $this->execute_query( $this->executor, $query );

        return $records;
    }

    public function fetch_all(string $classname, SQLFetchQuery $query, ?Deserializer $deserializer = null): array {
        if ( !$this->metadata->has( $classname ) ) {
            $this->metadata->visit( $classname );
            $this->net[ $classname ] = $this->metadata->foreigns_of( $classname );
        }

        $records = $this->execute_query( $this->executor, $query );

        foreach ( $this->net[ $classname ] as $dep_column ) {
            $records = $this->fill_column( $dep_column, $records );
        }

        if ( $deserializer === null ) {
            $deserializer = new ObjectDeserializer( $classname, $this->metadata );
        }

        return $deserializer->instance_all( $records );
    }

    public function fill_array(array $records, string $to, array $values, array $indexed_by, array $indexes): array {
        foreach ( $values as $data ) {
            $row_keys = [];
            foreach ( $indexed_by as $key ) {
                $row_keys[] = $data[ $key ];
            }
            $row_key = implode( ',', $row_keys );

            $index = $indexes[ $row_key ];

            $records[ $index ][ $to ][] = $data;
        }

        return $records;
    }

    public function fill_object(array $records, string $to, array $values, array $indexed_by, array $indexes): array {
        foreach ( $values as $data ) {
            $row_keys = [];
            foreach ( $indexed_by as $key ) {
                $row_keys[] = $data[ $key ];
            }
            $row_key = implode( ',', $row_keys );
            $index = $indexes[ $row_key ];
            $records[ $index ][ $to ] = $data;
        }

        return $records;
    }

    public function build_index_for_key(string $key, array $rel, array $records, mixed $default): array {
        $values = [];
        $indexes = [];
        $indexed_by = [];

        $symbolic_rel = [];

        foreach ( $rel as $left => $right ) {
            if ( is_string( $left ) && is_string( $right ) ) {
                $symbolic_rel[ $left ] = $right;
                $values[ $right ] = [];
                $indexed_by[] = $left;
            }
        }

        $i = 0;
        foreach ( $records as $record ) {
            $row_keys = [];
            foreach ( $symbolic_rel as $left => $right ) {
                $link_left = $record[ $left ];
                $values[ $right ][] = $link_left;
                $row_keys[] = $link_left;
            }
            $row_key = implode( ',', $row_keys );

            $records[ $i ][ $key ] = $default;

            $indexes[ $row_key ] = $i;
            $i++;
        }

        return [ $records, $values, $indexes, $indexed_by ];
    }

    public function fill_column(array $derived, array $records) {
        $classname = $derived[ 'classname' ];
        $source_name = $classname;

        if ( !$this->metadata->has( $source_name ) ) {
            $this->metadata->visit( $source_name );
            $this->net[ $classname ] = $this->metadata->foreigns_of( $classname );
        }

        $field = $derived[ 'field' ];

        [ $records, $values, $rows_indexes, $row_indexed_by ] = $this->build_index_for_key(
                $field, $derived[ 'link' ], $records, $derived[ 'default' ] ?? null
        );

        $query = $this->metadata->query_for( $source_name, $values )->into_query();

        $data = $this->execute_query( $this->executor, $query );

        $list = $this->net[ $source_name ] ?? [];

        foreach ( $list as $derived ) {
            $data = $this->fill_column( $derived, $data );
        }

        if ( $derived[ 'arity' ] === 1 ) {
            $records = $this->fill_object( $records, $field, $data, $row_indexed_by, $rows_indexes );
        } else if ( $derived[ 'arity' ] === 'n' ) {
            $records = $this->fill_array( $records, $field, $data, $row_indexed_by, $rows_indexes );
        }

        return $records;
    }

    public function logger(): ?Logger {
        return $this->logger;
    }

    public function set_logger(Logger $logger) {
        $this->logger = $logger;
    }
}
