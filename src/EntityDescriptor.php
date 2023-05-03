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

namespace alessio\entphp;

/**
 *
 * @author Alessio
 */
class EntityDescriptor {

    public const CLASS_ENTITY = 0;
    public const ARRAY_ENTITY = 1;

    private $name;
    private $kind;
    private $root;
    private $primary;
    private $links;
    private $read_map;
    private $write_map;

    public function __construct(string $name, int $kind, string $root, string|array $primary, array $links, array $read_map, array $write_map) {
        $this->name = $name;
        $this->kind = $kind;
        $this->root = $root;
        $this->primary = $primary;
        $this->links = $links;
        $this->read_map = $read_map;
        $this->write_map = $write_map;
    }

    public function kind(): int {
        return $this->kind;
    }

    /**
     * Name of the entity
     * @return string
     */
    public function entity_name(): string {
        return $this->name;
    }

    /**
     * Root table
     * @return string
     */
    public function root_table(): string {
        return $this->root;
    }

    /**
     * Tables referenced by the entity
     * @return array
     */
    public function references_tables(): array {
        $tables = [ $this->root ];
        foreach ( $this->links as $link ) {
            $tables[] = $link->to;
        }

        return $tables;
    }

    /**
     * Primary key
     * @return array
     */
    public function primary_key(): array {
        if ( is_array ( $this->primary ) ) {
            return $this->primary;
        }
        return [ $this->primary ];
    }

    public function fully_qualified_keys(): array {
        return array_keys ( $this->write_map );
    }

    public function properties(): array {
        return array_keys ( $this->read_map );
    }

    /**
     * Links of table
     * @param string $left
     * @param int $kind
     * @return array
     */
    public function links_of(string $left, int $kind = Link::LINK_FLAT): array {
        $collect = [];

        foreach ( $this->links as $link ) {
            if ( $link->from === $left && ( $kind & $link->linkage ) !== 0 ) {
                $collect[] = $link;
            }
        }

        return $collect;
    }

    /**
     * Trace all links from the source to the destination table
     * @param string $left
     * @param string $right
     * @return array
     */
    public function trace_link_from_to(string $left, string $right): array {
        $list = $this->links_of ( $left );

        foreach ( $list as $link ) {
            if ( $link->to === $right ) {
                return [ $link ];
            }

            $follow = $this->trace_from_to ( $link->to, $right );
            if ( count ( $follow ) > 0 ) {
                return [ $link, ...$follow ];
            }
        }

        return [];
    }

    /**
     * Trace all links from root table to the specified table
     * @param string $right
     * @return array
     */
    public function trace_link_to(string $right): array {
        return $this->trace_link_from_to ( $this->root, $right );
    }

    public function trace_all_links(): array {
        $tables = $this->references_tables ();
        $from_links = [];

        foreach ( $tables as $table ) {
            if ( $table !== $this->root ) {
                $trace = $this->trace_link_to ( $table );
                array_push ( $from_links, ...$trace );
            }
        }

        return $from_links;
    }

}
