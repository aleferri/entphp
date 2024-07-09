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
    private $id_tracker;

    public function __construct(\PDO $pdo) {
        $this->metadata = new MetadataStore();
        $this->planner = new SQLFetchPlanner( $pdo, $this->metadata );
        $this->id_tracker = new IdentityTrackerSequential( 'sql' );
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
            throw new \RuntimeException( 'Not supported yet' );
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
}
