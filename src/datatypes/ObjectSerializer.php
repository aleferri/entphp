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

namespace entphp\datatypes;

use basin\concepts\convert\Serializer;
use basin\concepts\Schema;

/**
 * Description of ObjectSerializer
 *
 * @author Alessio
 */
class ObjectSerializer implements Serializer {

    public static function of_class(string $classname, string $context, array $defaults = []) {
        $class = new \ReflectionClass( $classname );
        $schema = TableSchema::of_class( $class, $context );

        return new self( $classname, $schema, $defaults );
    }

    /**
     *
     * @var classname
     */
    private $classname;

    /**
     *
     * @var Schema
     */
    private $schema;

    /**
     *
     * @var array
     */
    private $defaults;

    /**
     *
     * @var ReflectionClass|null
     */
    private $class;

    public function __construct(string $classname, Schema $schema, array $defaults = []) {
        $this->classname = $classname;
        $this->schema = $schema;
        $this->defaults = $defaults;
        $this->class = new \ReflectionClass( $classname );
    }

    private function recursive_breakup_array(array $data, Schema $schema, \ReflectionClass $class, array $items, array $link = []): array {
        foreach ( $items as $item ) {
            $data = $this->recursive_breakup( $data, $schema, $class, $item, $link );
        }

        return $data;
    }

    private function recursive_breakup(array $data, Schema $schema, \ReflectionClass $class, $object, array $link = []): array {
        $source = $schema->root_source();

        $row = [];

        foreach ( $link as $handle => $value ) {
            $row[ $handle ] = $value;
        }

        foreach ( $schema->local_sourced_properties() as $name => $info ) {
            $property = $class->getProperty( $name );
            $property->setAccessible( true );

            $value = $property->getValue( $object );
            $row[ $name ] = $value;
        }

        foreach ( $schema->foreign_sourced_properties() as $name => $info ) {
            $property = $class->getProperty( $name );
            $property->setAccessible( true );

            $value = $property->getValue( $object );

            $link = $info[ 'link' ];
            $link_data = [];

            foreach ( $link as $key => $handle ) {
                $link_data[ $handle ] = $row[ $key ];
            }

            $child_schema = $info[ 'item_schema' ];
            $child_class = new \ReflectionClass( $info[ 'classname' ] );
            if ( $info[ 'arity' ] === 'n' ) {
                $data = $this->recursive_breakup_array( $data, $child_schema, $child_class, $value, $link_data );
            } else {
                $data = $this->recursive_breakup( $data, $child_schema, $child_class, $value, $link_data );
            }
        }

        if ( ! isset( $data[ $source ] ) ) {
            $data[ $source ] = [ $row ];
        } else {
            $data[ $source ] = array_merge( $data[ $source ], $row );
        }
        return $data;
    }

    public function breakup(object $object): array {
        return $this->recursive_breakup( [], $this->schema, $this->class, $object );
    }

    public function breakup_all(array $objects): array {
        $data = [];
        foreach ( $objects as $object ) {
            $map = $this->breakup( $object );

            foreach ( $map as $source => $rows ) {
                if ( ! isset( $data[ $source ] ) ) {
                    $data[ $source ] = $rows;
                } else {
                    $data[ $source ] = array_merge( $data[ $source ], $rows );
                }
            }
        }

        return $data;
    }

    public function class(): \ReflectionClass {
        return $this->class;
    }

    public function schema(): Schema {
        return $this->schema;
    }

}
