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

use basin\concepts\convert\SchemaDeserializer;
use basin\concepts\Schema;

/**
 * Description of TypeBuilder
 *
 * @author Alessio
 */
class ObjectDeserializer implements SchemaDeserializer {

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

    private function raw_of(array $info, array $data): mixed {
        $field = $info[ 'field' ] ?? null;

        if ( $field !== null ) {
            return $data[ $field ];
        }

        if ( isset( $this->defaults[ $field ] ) ) {
            return $this->defaults[ $field ];
        }

        throw new \RuntimeException( 'cannot find value' );
    }

    private function parse_of_kind(array $info, array $data, mixed $raw): mixed {
        $kind = $info[ 'kind' ] ?? null;

        if ( $kind === null ) {
            return $raw;
        }

        if ( $kind === 'string' ) {
            return \entphp\datatypes\identity( $raw );
        }

        if ( $kind === 'int' ) {
            return \entphp\datatypes\to_int_strict( $raw );
        }

        if ( $kind === 'int|null' ) {
            return \entphp\datatypes\to_int( $raw );
        }

        if ( $kind === 'date' ) {
            return \entphp\datatypes\to_date_strict( $raw );
        }

        if ( $kind === 'date|null' ) {
            return \entphp\datatypes\to_date( $raw );
        }

        throw new \RuntimeException( 'unexpected kind' );
    }

    private function value_of(array $info, array $data): mixed {
        $converter = $info[ 'converter' ] ?? null;
        $raw = $this->raw_of( $info, $data );

        if ( $converter === null && isset( $info[ 'kind' ] ) ) {
            return $this->parse_of_kind( $info, $data, $raw );
        }

        if ( is_callable( $converter ) ) {
            return $converter( $raw, $info[ 'arity' ] );
        }

        if ( $converter === null ) {
            $converter = new ObjectDeserializer( $info[ 'classname' ],
                                                 $info[ 'item_schema' ] );
        }

        if ( $info[ 'arity' ] === 'n' ) {
            return $converter->instance_all( $raw );
        } else {
            return $converter->instance( $raw );
        }
    }

    public function instance(array $data): object|array {
        $instance = $this->class->newInstanceWithoutConstructor();

        foreach ( $this->schema->properties() as $name => $info ) {
            $property = $this->class->getProperty( $name );
            $property->setAccessible( true );

            $value = $this->value_of( $info, $data );
            $property->setValue( $instance, $value );
        }

        return $instance;
    }

    public function instance_all(array $records): array {
        $instances = [];
        foreach ( $records as $record ) {
            $instances[] = $this->instance( $record );
        }

        return $instances;
    }

    public function class(): \ReflectionClass {
        return $this->class;
    }

    public function schema(): Schema {
        return $this->schema;
    }

}
