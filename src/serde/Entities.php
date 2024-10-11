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

namespace entphp\serde;

use basin\concepts\query\FetchQueryBuilder;
use basin\concepts\query\FetchQuery;
use basin\concepts\query\Filters;
use basin\concepts\Repository;
use entphp\drivers\SQLDriver;
use entphp\meta\MetadataStore;
use entphp\query\SQLFetchQueryBuilder;
use entphp\query\SQLFetchPlanner;
use entphp\identity\IdentityTrackerSequential;

/**
 * Description of Entities
 *
 * @author Alessio
 */
class Entities implements Repository {

    private $planner;
    private $metadata;
    private $driver;
    private $id_tracker;

    public function __construct(\PDO $pdo, SQLDriver $driver) {
        $this->metadata = new MetadataStore();
        $this->driver = $driver;
        $this->planner = new SQLFetchPlanner( $pdo, $this->metadata );
        $this->id_tracker = new IdentityTrackerSequential( 'sql' );
    }

    private function compile_join_plan(array $plan, array $classnames): array {
        $leftovers = [];

        foreach ( $classnames as $classname ) {
            $join_path = false;

            foreach ( $plan as $candidate ) {
                if ( $this->metadata->has_relation_1( $candidate[ 0 ], $classname ) ) {
                    $plan[] = [ $classname, $candidate[ 0 ] ];
                    $join_path = true;
                    break;
                }
            }

            if ( ! $join_path ) {
                $leftovers[] = $classname;
            }
        }

        return [ $plan, $classnames ];
    }

    private function compile_projection(array $fields): SQLFetchQueryBuilder {
        $aliases = [];
        $selection = [];
        $i = 0;

        foreach ( $fields as $field ) {
            [ $classname, $name ] = explode( '.', $field );

            if ( ! isset( $aliases[ $classname ] ) ) {
                $aliases[ $classname ] = 'ent' . $i;
                $i ++;
            }

            $selection[] = $aliases[ $classname ] . '.' . $field;
        }

        $classnames = array_keys( $aliases );
        $root = array_shift( $classnames );

        $builder = SQLFetchQueryBuilder::start()
            ->select( ...$selection )
            ->from( $root, $aliases[ $root ] );

        $joins = [ [ $root ] ];

        $leftovers = $classnames;
        $count = count( $leftovers );
        $old_count = $count + 1;

        while ( $count > 0 && $count < $old_count ) {
            [ $joins, $leftovers ] = $this->compile_join_plan( $joins, $leftovers );
            $old_count = $count;
            $count = count( $leftovers );
        }

        if ( $count > 0 ) {
            throw new \RuntimeException( 'projection ' . implode( $fields, ',' ) . ' not possible because not all classes are linked' );
        }

        foreach ( $plan as $join ) {
            if ( count( $join ) === 1 ) {
                continue;
            }

            $map = $this->metadata->relation_1_map( $join[ 1 ], $join[ 0 ] ); // intentional, candidate is at position 1 and the next to join is at position 0
            $builder = $builder->left_join( $join[ 0 ], $map );
        }

        return $builder;
    }

    public function fetch(string|array $fields, mixed $id): object|array|null {
        if ( $id === null ) {
            $id = 1;
        }

        if ( is_string( $fields ) ) {
            $classname = $fields;
            $info = $this->metadata->first_key_of( $classname );
            $key = $info[ 'field' ];
            $builder = $this->metadata->query_for_key( $classname, $key, $id );
        } else {
            $builder = $this
                ->compile_projection( $fields )
                ->filter_by( 'per_row_id', 'id', $id );
        }

        $records = $this->planner->fetch_all( $classname, $builder->into_query() );
        return $records[ 0 ] ?? null;
    }

    public function find_all(string|array $fields, Filters $filters, ?\basin\concepts\query\Order $order_by): array {
        if ( is_string( $fields ) ) {
            $classname = $fields;
            $builder = $this->metadata->start_query( $classname );
        } else {
            throw new \RuntimeException( 'Not supported yet' );
        }

        $order_fields = $order_by->fields(); // TODO lower

        $filters->apply_to( $builder ); // TODO lower
        $builder->order_by( ...$order_fields ); // TODO lower

        return $this->planner->fetch_all( $classname, $builder->into_query() );
    }

    public function find_page(string|array $fields, Filters $filters, \basin\concepts\query\Page $page): array {
        if ( is_string( $fields ) ) {
            $classname = $fields;
            $builder = $this->metadata->start_query( $classname );
        } else {
            throw new \RuntimeException( 'Not supported yet' );
        }

        $filters->apply_to( $builder ); // TODO lower
        $page->apply_to( $builder ); // TODO lower

        return $this->planner->fetch_all( $classname, $builder->into_query() );
    }

    public function find_next_batch(string|array $fields, Filters $filters, \basin\concepts\query\Cursor $cursor): array {
        if ( is_string( $fields ) ) {
            $classname = $fields;
            $builder = $this->metadata->start_query( $classname );
        } else {
            throw new \RuntimeException( 'Not supported yet' );
        }

        $filters->apply_to( $builder ); // TODO lower
        $cursor->apply_to( $builder ); // TODO lower

        return $this->planner->fetch_all( $classname, $builder->into_query() );
    }

    public function find_query(FetchQuery $query): array {
        return $this->planner->find( $query );
    }

    public function store(object|array $data): object|array {

    }

    public function store_all(array $data): array {
        foreach ( $data as $object ) {
            $classname = get_class( $object );

            $serializer = $this->cache_serializer( $classname );

            $raw = $serializer->breakup( $object );
        }
    }

    public function drop(object|array $data, int $policy = 1): bool {

    }

    public function create_table(string $classname): mixed {
        $this->metadata->visit( $classname );
        [ $source, $schema, $identity_info ] = $this->metadata->get( $classname );

        $identity_fields = $this->metadata->report_identity( $classname );

        $fields = [];

        foreach ( $schema->properties() as $info ) {
            $location = $info[ 'location' ] ?? 'local';
            $name = $info[ 'field' ];

            if ( $location === 'local' ) {
                $field_decl = $name . " " . $this->driver->map_type( $info[ 'kind' ] );

                if ( isset( $identity_fields[ $name ] ) ) {
                    $field_decl .= ' PRIMARY KEY NOT NULL';

                    if ( isset( $identity_fields[ $name ][ 'settings' ][ 'autoincrement' ] ) ) {
                        $field_decl .= ' AUTOINCREMENT';
                    }
                }

                $fields[] = $field_decl;
            } else {
                if ( count( $info[ 'link' ] ) > 0 ) {
                    continue;
                }

                $target_fields = $this->metadata->report_identity( $info[ 'classname' ] );

                foreach ( $target_fields as $target_field ) {
                    $fields[] = $name . '_' . $target_field[ 'field' ] . '_fk ' . $this->driver->map_type( $target_field[ 'kind' ] );
                }
            }
        }

        $sql_create = "\nCREATE TABLE IF NOT EXISTS {$source} (\n";
        $sql_create .= implode( ",\n", $fields );
        $sql_create .= ");\n";

        $result = $this->driver->exec( $sql_create );

        if ( $result === 1 ) {
            return $sql_create;
        }

        return false;
    }

}
