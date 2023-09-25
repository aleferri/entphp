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

use basin\concepts\query\FetchQuery;
use basin\concepts\query\Selection;
use basin\concepts\query\Order;

class SQLFetchQuery implements FetchQuery {

    /**
     *
     * @var \basin\concepts\Selection
     */
    private $selection;

    /**
     *
     * @var string
     */
    private $from;

    /**
     *
     * @var array<string, QueryArea>
     */
    private $areas;

    /**
     *
     * @var array<string>
     */
    private $group_by;

    /**
     *
     * @var ?\basin\concepts\Order
     */
    private $order;

    /**
     *
     * @var array{?int, ?int}
     */
    private $range;

    public function __construct(Selection $selection, string $from, array $areas, array $group_by, ?Order $order, array $range) {
        $this->selection = $selection;
        $this->from = $from;
        $this->areas = $areas;
        $this->group_by = $group_by;
        $this->order = $order;
        $this->range = $range;
    }

    public function selection(): Selection {
        return $this->selection;
    }

    public function from(): string {
        return $this->from;
    }

    public function stringify_filters(string $prefix = 'per_row_'): string {
        $filters = [];

        foreach ( $this->areas as $area ) {
            if ( strpos( $area->id(), $prefix ) === 0 ) {
                $filters[] = '(' . $area->expression() . ')';
            }
        }

        return implode( ' AND ', $filters );
    }

    public function filters(?string $id = null): array {
        $filters = [];

        foreach ( $this->areas as $area ) {
            if ( $id !== null && strpos( $area->id(), $id ) === 0 ) {
                $filters[] = '(' . $area->expression() . ')';
            } else if ( $id === null ) {
                $filters[] = '(' . $area->expression() . ')';
            }
        }

        return implode( ' AND ', $filters );
    }

    public function values(?string $id = null): array {
        $all = [];

        foreach ( $this->areas as $area ) {
            if ( $id === null ) {
                array_push( $all, ...$area->values() );
            } else if ( strpos( $area->id(), $id ) === 0 ) {
                array_push( $all, ...$area->values() );
            }
        }

        return $all;
    }

    public function group_by(): array {
        return $this->group_by;
    }

    public function order_by(): ?Order {
        return $this->order;
    }

    public function limit(): ?int {
        return $this->range[ 0 ];
    }

    public function offset(): ?int {
        return $this->range[ 1 ];
    }

    public function to_sql(): string {
        $select_str = implode( ', ', $this->selection->fields() );

        $where = $this->stringify_filters( 'per_row_' );
        if ( $where === '' ) {
            $where = '1';
        }

        if ( count( $this->group_by ) > 0 ) {
            $group_by = 'GROUP BY ' . implode( ', ', $this->group_by );
        } else {
            $group_by = '';
        }

        $having_check = $this->stringify_filters( 'per_group_' );
        if ( $having_check !== '' ) {
            $having = 'HAVING ' . $having_check;
        } else {
            $having = '';
        }

        if ( $this->range[ 0 ] !== null ) {
            $limit = ' LIMIT ' . $this->range[ 0 ];
        } else {
            $limit = '';
        }

        if ( $this->range[ 1 ] !== null ) {
            $offset = ' OFFSET ' . $this->range[ 1 ];
        } else {
            $offset = '';
        }

        return "SELECT {$select_str}"
                . " FROM {$this->from}"
                . " WHERE {$where}"
                . " {$group_by} "
                . " {$having}"
                . " {$limit} {$offset} ";
    }
}
