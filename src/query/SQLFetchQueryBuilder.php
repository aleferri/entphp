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

use basin\concepts\query\FetchQueryBuilder;
use basin\concepts\query\OrderField;
use basin\concepts\query\Direction;
use entphp\query\fetch\Selection;

/**
 * Description of FetchQueryBuilderSQL
 *
 * @author Alessio
 */
class SQLFetchQueryBuilder implements FetchQueryBuilder {

    public static function start(): self {
        return new self();
    }

    /**
     *
     * @var array<string, QueryArea>
     */
    private $areas;

    /**
     *
     * @var \entphp\query\fetch\Selection
     */
    private $selection;

    /**
     *
     * @var string
     */
    private $from;

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

    /**
     *
     * @var array<string, string>
     */
    private $aliases;

    public function __construct() {
        $this->areas = [];
        $this->selection = new Selection();
        $this->from = '';
        $this->group_by = [];
        $this->order = null;
        $this->range = [ null, null ];
        $this->aliases = [];
    }

    /**
     * Select some fields
     * @param string $fields
     * @return self
     */
    public function select(string ...$fields): self {
        foreach ( $fields as $field ) {
            if ( !$this->selection->contains( $field, true ) ) {
                $this->selection->add( $field );
            }
        }

        return $this;
    }

    /**
     * From some fields
     * @param string $origin
     * @param string|null $alias
     * @return self
     */
    public function from(string $origin, ?string $alias = null): self {
        $this->from = $origin;
        if ( $alias !== null ) {
            $this->from .= ' as ' . $alias;
            $this->aliases[ $origin ] = $alias;
        }

        return $this;
    }

    /**
     * left join with map
     * @param string $right
     * @param array $map
     * @return self
     */
    public function left_join(string $right, array $map): self {
        $this->from = '( ' . $this->from . ' LEFT JOIN ' . $right . ' ON ( ';

        $cc = [];
        foreach ( $map as $left => $right ) {
            if ( is_int( $left ) && is_int( $right ) ) {
                throw new \RuntimeException( 'int to int comparison, at least one key is required' );
            }

            $cc[] = $right . ' = ' . $left;
        }

        $this->from .= implode( ' AND ', $cc ) . ' ) )';
    }

    /**
     * Right join with map
     * @param string $right
     * @param array $map
     * @return self
     */
    public function right_join(string $right, array $map): self {
        $this->from = '( ' . $this->from . ' RIGHT JOIN ' . $right . ' ON ( ';

        $cc = [];
        foreach ( $map as $left => $right ) {
            if ( is_int( $left ) && is_int( $right ) ) {
                throw new \RuntimeException( 'int to int comparison, at least one key is required' );
            }

            $cc[] = $right . ' = ' . $left;
        }

        $this->from .= implode( ' AND ', $cc ) . ' ) )';

        return $this;
    }

    /**
     * Inner join with map
     * @param string $right
     * @param array $map
     * @return self
     */
    public function inner_join(string $right, array $map): self {
        $this->from = '( ' . $this->from . ' INNER JOIN ' . $right . ' ON ( ';

        $cc = [];
        foreach ( $map as $left => $right ) {
            if ( is_int( $left ) && is_int( $right ) ) {
                throw new \RuntimeException( 'int to int comparison, at least one key is required' );
            }

            $cc[] = $right . ' = ' . $left;
        }

        $this->from .= implode( ' AND ', $cc ) . ' ) )';

        return $this;
    }

    /**
     * Prepend another table, in other joins the result is ($CONSOLIDATED JOIN $table ON $map), but in this case
     * the result is ($table JOIN ($CONSOLIDATED) ON $map)
     * @param string $left
     * @param array $map
     * @return self
     */
    public function prepend_left_join(string $left, array $map): self {
        $this->from = '( ' . $left . ' LEFT JOIN (' . $this->from . ') ON ( ';

        $cc = [];
        foreach ( $map as $left => $right ) {
            if ( is_int( $left ) && is_int( $right ) ) {
                throw new \RuntimeException( 'int to int comparison, at least one key is required' );
            }

            $cc[] = $right . ' = ' . $left;
        }

        $this->from .= implode( ' AND ', $cc ) . ' ) )';

        return $this;
    }

    /**
     * Filter by the specified condition
     * @param string $id string id of the condition
     * @param string $condition
     * @param mixed $values
     * @return self
     */
    public function filter_by(string $id, string $condition, mixed ...$values): self {
        $this->areas[ $id ] = new SQLFiltersArea( $id, $condition, $values );

        return $this;
    }

    /**
     * Add filter to the left of the combined condition
     * @param string $id
     * @param string $rel
     * @param string $condition
     * @param mixed $values
     * @return self
     */
    public function add_filter_by_left(string $id, string $rel, string $condition, mixed ...$values): self {
        $this->areas[ $id ]->chain_left( $rel, $condition, $values );

        return $this;
    }

    /**
     * Add filter to the right of the combined condition
     * @param string $id
     * @param string $rel
     * @param string $condition
     * @param mixed $values
     * @return self
     */
    public function add_filter_by_right(string $id, string $rel, string $condition, mixed ...$values): self {
        $this->areas[ $id ]->chain_right( $rel, $condition, $values );

        return $this;
    }

    /**
     * Fold a filter, so enclose the filter in a set of parent
     * @param string $id
     * @return self
     */
    public function fold_filter(string $id): self {
        $this->areas[ $id ]->fold();

        return $this;
    }

    /**
     * Reset the "group by" and add to it the speficied fields
     * @param string $fields
     * @return self
     */
    public function group_by(string ...$fields): self {
        $this->group_by = $fields;

        return $this;
    }

    /**
     * Add some fields in the "group by" clause
     * @param string $fields
     * @return self
     */
    public function also_group_by(string ...$fields): self {
        foreach ( $fields as $field ) {
            $this->group_by[] = $field;
        }

        return $this;
    }

    /**
     * Reset the "order by" and add the specified fields
     * @param string $fields
     * @return self
     */
    public function order_by(string ...$fields): self {
        $this->order = new fetch\OrderBy();
        foreach ( $fields as $field ) {
            $parts = explode( ' ', $field );

            if ( count( $parts ) > 1 ) {
                $f = $parts[ 0 ];
                $d = $parts[ 1 ];
            } else {
                $f = $parts[ 0 ];
                $d = 'ASC';
            }

            $this->order->append( new OrderField( $f, Direction::of( $d ) ) );
        }

        return $this;
    }

    /**
     * Add some fields in the "order by" clause
     * @param string $fields
     * @return self
     */
    public function also_order_by(string ...$fields): self {
        foreach ( $fields as $field ) {
            $parts = explode( ' ', $field );

            if ( count( $parts ) > 1 ) {
                $f = $parts[ 0 ];
                $d = $parts[ 1 ];
            } else {
                $f = $parts[ 0 ];
                $d = 'ASC';
            }

            $this->order->append( new OrderField( $f, Direction::of( $d ) ) );
        }

        return $this;
    }

    /**
     * Skip the first $number results
     * @param int $number
     * @return self
     */
    public function skip_firsts(int $number): self {
        $this->range[ 0 ] = $number;

        return $this;
    }

    /**
     * Take at most $number results
     * @param int $number
     * @return self
     */
    public function take_at_most(int $number): self {
        $this->range[ 1 ] = $number;

        return $this;
    }

    /**
     * Create a query from builder data
     * @return FetchQuery
     */
    public function into_query(): SQLFetchQuery {
        return new SQLFetchQuery( $this->selection, $this->from, $this->areas, $this->group_by, $this->order, $this->range );
    }
}
