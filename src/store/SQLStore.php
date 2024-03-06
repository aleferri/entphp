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

namespace entphp\store;

use entphp\identity\IdentityTracker;
use entphp\identity\TransientIdentity;
use entphp\identity\PersistedIdentity;
use basin\concepts\Identity;

/**
 * Description of SQLStore
 *
 * @author Alessio
 */
class SQLStore {

    private $pdo;
    private $id_tracker;

    public function __construct(\PDO $pdo, IdentityTracker $id_tracker) {
        $this->pdo = $pdo;
        $this->id_tracker = $id_tracker;
    }

    private function make_insert_query(string $table, array $fields): string {
        $fields_quoted = array_map( function ($a) {
            return "`{$a}`";
        }, $fields );

        $fields_plaholders = array_map( function ($a) {
            return '?';
        }, $fields );

        $fields_formatted = implode( ', ', $fields_quoted );
        $fields_placeholder = implode( ', ', $fields_plaholders );

        $query = "INSERT INTO {$table} ({$fields_formatted}) VALUES ({$fields_placeholder})";

        return $query;
    }

    private function prepare_row(Identity $identity, array $row): array {
        unset( $row[ '__identity' ] );
        foreach ( $identity->fields() as $field ) {
            $key = '__transient_' . $field;
            unset( $row[ $key ] );
            unset( $row[ $field ] );
        }

        return $row;
    }

    private function execute_inserts(array $inserts, string $dest_table): array {
        if ( count( $inserts ) === 0 ) {
            return [];
        }

        $template = $this->prepare_row( $inserts[ 0 ][ '__identity' ], $inserts[ 0 ] );

        $fields = array_keys( $template );

        $query = $this->make_insert_query( $dest_table, $fields );

        $st = $this->pdo->prepare( $query );

        $results = [];
        foreach ( $inserts as $row ) {
            $identity = $row[ '__identity' ];
            $clean_row = $this->prepare_row( $row[ '__identity' ], $row );
            $st->execute( array_values( $clean_row ) );
            $id = $this->pdo->lastInsertId();
            foreach ( $identity->fields() as $field ) {
                $this->id_tracker->patch_id( $row[ $field ], $id );
                $clean_row[ $field ] = $id;
            }

            $results[] = $clean_row;
        }

        return $results;
    }

    private function schedule_round(array $rows_by_dest): array {
        $leftovers = [];
        $persisted = [];

        foreach ( $rows_by_dest as $source => $records ) {
            $later = [];

            $fit_for_update = [];
            $fit_for_insert = [];

            foreach ( $records as $record ) {
                if ( ! isset( $record[ '__identity' ] ) ) {
                    // The record has no identity, how do we store it? We assume it was readonly
                    continue;
                }

                if ( isset( $record[ '__transient_patches' ] ) ) {
                    $later[] = $record; // Record not yet ready
                    continue;
                }

                $identity = $record[ '__identity' ];
                if ( $identity instanceof TransientIdentity ) {
                    $fit_for_insert[] = $record;
                } else if ( $identity instanceof PersistedIdentity ) {
                    $fit_for_update[] = $record;
                }

                // Otherwise, don't know how to store it (or it is null)
            }

            $inserted_rows = $this->execute_inserts( $fit_for_insert, $source );

            if ( count( $later ) > 0 ) {
                $leftovers[ $source ] = $later;
            }
        }

        return $leftovers;
    }

    public function store(array $rows_by_dest): array {
        $this->schedule_round( $rows_by_dest );

        return [];
    }

}
