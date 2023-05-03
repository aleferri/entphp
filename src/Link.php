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

/**
 * Description of Link
 *
 * @author Alessio
 */
class Link {

    public const LINK_FLAT = 1;
    public const LINK_NESTED = 2;
    public const POLICY_NULLABLE = 1;
    public const POLICY_REQUIRED = 2;
    public const ON_FIELDS = 0;
    public const ON_VALUES = 1;

    public $from;
    public $to;
    public $linkage;
    public $on;

    public function __construct(string $from, string $to, int $linkage, array $on) {
        $this->from = $from;
        $this->to = $to;
        $this->linkage = $linkage;
        $this->on = $on;
    }

    public function render_from(string $left, string $map, int $policy = self::POLICY_REQUIRED): string {
        $join = 'INNER JOIN';
        if ( $policy === self::POLICY_NULLABLE ) {
            $join = 'LEFT JOIN';
        }

        return "{$left} {$join} {$this->to} ON ( {$map} )";
    }

    public function link_map(): array {
        $fields = [];
        $values = [];

        foreach ( $this->on as $pair ) {
            $left = $pair[ 0 ];
            $right = $pair[ 1 ];

            $a = $left[ 'atom' ];
            $b = $right[ 'atom' ];
            $is_a_key = $left[ 'kind' ] === self::ON_FIELDS;
            $is_b_key = $right[ 'kind' ] === self::ON_FIELDS;

            if ( $is_a_key && ! $is_b_key ) {
                $fields[] = "{$a} = ?";
                $values[] = $b;
            } else if ( $is_b_key && ! $is_a_key ) {
                $fields[] = "? = {$b}";
                $values[] = $a;
            } else {
                $fields[] = "{$a} = {$b}";
            }
        }

        $query = implode ( ' AND ', $fields );

        return [ $query, $values ];
    }

    public function create_mapping(array $keys_from, array $keys_to): array {
        $fields = [];
        $values = [];

        foreach ( $this->on as $key => $to ) {
            $is_a_key = isset ( $keys_from[ $key ] );
            $is_b_key = isset ( $keys_to[ $to ] );

            $is_a_raw = $is_a_key || is_numeric ( $key );
            $is_b_raw = $is_b_key || is_numeric ( $to );

            if ( $is_a_raw && ! $is_b_raw ) {
                $fields[] = "{$key} = ?";
                $values[] = $to;
            } else if ( $is_b_raw && ! $is_a_raw ) {
                $fields[] = "? = {$to}";
                $values[] = $key;
            } else {
                $fields[] = "{$key} = {$to}";
            }
        }

        $query = implode ( ' AND ', $fields );

        return [ $query, $values ];
    }

}
