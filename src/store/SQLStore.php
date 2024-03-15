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

    private function make_update_query(string $table, array $fields, array $identity_fields): string {
        $fields_quoted = array_map( function ($a) {
            return "`{$a}` = ?";
        }, $fields );

        $fields_formatted = implode( ', ', $fields_quoted );

        $identity_quoted = array_map( function ($a) {
            return "`{$a}` = ?";
        }, $identity_fields );

        $identity_formatted = implode( ' AND ', $identity_quoted );

        $query = "UPDATE {$table} SET {$fields_formatted} WHERE {$identity_formatted}";

        return $query;
    }

    private function canonical_params(array $row, array $template): array {
        $data = [];

        foreach ( $template as $key => $value ) {
            $data[] = $row[ $key ];
        }

        return $data;
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
                $transient_field = '__transient_' . $field;
                $this->id_tracker->patch_id( $row[ $transient_field ], $id );
                $clean_row[ $field ] = $id;
            }

            $results[] = $clean_row;
        }

        return $results;
    }

    private function execute_updates(array $updates, string $dest_table): array {
        if ( count( $updates ) === 0 ) {
            return [];
        }

        $identity = $updates[ 0 ][ '__identity' ];
        $template = $this->prepare_row( $updates[ 0 ][ '__identity' ], $updates[ 0 ] );

        $fields = array_keys( $template );

        $query = $this->make_update_query( $dest_table, $fields, $identity->fields() );

        $st = $this->pdo->prepare( $query );

        $results = [];
        foreach ( $updates as $row ) {
            $identity = $row[ '__identity' ];
            $refs = $identity->of( $row );

            $params = array_merge( $this->canonical_params( $row, $template ), $refs );

            $st->execute( $params );

            $results[] = $row;
        }

        return $results;
    }

    private function schedule_round(array $rows_by_dest): array {
        $leftovers = [];
        $persisted = [];

        foreach ( $rows_by_dest as $source => $records ) {
            $try_fix = [];

            $fit_for_update = [];
            $fit_for_insert = [];

            foreach ( $records as $record ) {
                if ( ! isset( $record[ '__identity' ] ) ) {
                    // The record has no identity, how do we store it? We assume it was readonly
                    continue;
                }

                if ( isset( $record[ '__transient_patches' ] ) ) {
                    $try_fix[] = $record; // Record not yet ready
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
            $updated_rows = $this->execute_updates( $fit_for_update, $source );

            $schedule_later = [];
            foreach ( $try_fix as $record ) {
                $schedule_later[] = $this->id_tracker->patch_transients( $record );
            }

            if ( count( $schedule_later ) > 0 ) {
                $leftovers[ $source ] = $schedule_later;
            }
        }

        return $leftovers;
    }

    public function store(array $rows_by_dest): array {
        $max_depth = 3;

        while ( count( $rows_by_dest ) > 0 && $max_depth > 0 ) {
            $rows_by_dest = $this->schedule_round( $rows_by_dest );
            $max_depth --;
        }

        $this->id_tracker->flush();

        return $rows_by_dest;
    }

}
