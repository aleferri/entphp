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

namespace entphp\serde;

use basin\concepts\convert\SchemaDeserializer;
use basin\concepts\Schema;
use entphp\meta\MetadataStore;

/**
 * Description of TypeBuilder
 *
 * @author Alessio
 */
class ObjectDeserializer implements SchemaDeserializer {

    public static function of_class(string $classname, string $context, array $defaults = []) {
        $metadata = new MetadataStore( $context );
        $metadata->visit( $classname );

        return new self( $classname, $metadata, $defaults );
    }

    /**
     *
     * @var classname
     */
    private $classname;

    /**
     *
     * @var MetadataStore
     */
    private $metadata;

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

    /**
     *
     * @var array<string: callable>
     */
    private $converters;

    public function __construct(string $classname, MetadataStore $metadata, array $defaults = []) {
        $this->classname = $classname;
        $this->metadata = $metadata;
        $this->defaults = $defaults;
        $this->class = new \ReflectionClass( $classname );
        $this->converters = [
                'string'       => '\entphp\datatypes\identity',
                'int'          => '\entphp\datatypes\to_int_strict',
                'int|null'     => '\entphp\datatypes\to_int',
                'float'        => '\entphp\datatypes\to_float_strict',
                'float|null'   => '\entphp\datatypes\to_float',
                'decimal'      => '\entphp\datatypes\to_decimal',
                'decimal|null' => '\entphp\datatypes\to_decimal_strict',
                'date'         => '\entphp\datatypes\to_date_strict',
                'date|null'    => '\entphp\datatypes\to_date',
                'time'         => '\entphp\datatypes\to_time_strict',
                'time|null'    => '\entphp\datatypes\to_time',
        ];
    }

    public function provide(string $key, callable $converter): void {
        $this->converters[ $key ] = $converter;
    }

    public function provide_all(string $converters): void {
        foreach ( $converters as $key => $converter ) {
            $this->converters[ $key ] = $converter;
        }
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

        if ( isset( $this->converters[ $kind ] ) ) {
            return $this->converters[ $kind ]( $raw, $info, $data );
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
            $converter = new ObjectDeserializer( $info[ 'classname' ], $this->metadata );
        }

        if ( $info[ 'arity' ] === 'n' ) {
            return $converter->instance_all( $raw );
        } else {
            if ( $raw !== null ) {
                return $converter->instance( $raw );
            } else {
                return null;
            }
        }
    }

    public function instance(array $data): object|array {
        $instance = $this->class->newInstanceWithoutConstructor();
        $schema = $this->metadata->schema_of( $this->classname );

        foreach ( $schema->properties() as $name => $info ) {
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
        return $this->metadata->schema_of( $this->classname );
    }
}
