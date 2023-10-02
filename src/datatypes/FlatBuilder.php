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

use basin\concepts\convert\TypeBuilder;
use basin\attributes\MapPrimitive;
use basin\attributes\MapArray;

/**
 * Description of TypeBuilder
 *
 * @author Alessio
 */
class FlatBuilder implements TypeBuilder {

    public static function select_parser_for_primitive(array $arguments) {
        $kind = $arguments[ 'kind' ];

        if ( $kind === 'string' ) {
            return '\entphp\datatypes\identity';
        }

        if ( $kind === 'int' ) {
            return '\entphp\datatypes\to_int_strict';
        }

        if ( $kind === 'int|null' ) {
            return '\entphp\datatypes\to_int';
        }

        if ( $kind === 'date' ) {
            return '\entphp\datatypes\to_date_strict';
        }

        if ( $kind === 'date|null' ) {
            return '\entphp\datatypes\to_date';
        }

        throw new \RuntimeException( 'unexpected kind' );
    }

    public static function of_primitive(\ReflectionProperty $property, \ReflectionAttribute $primitive, array $defaults = []) {
        $arguments = $primitive->getArguments();

        $settings = $arguments[ 'settings' ];
        $name = $property->getName();
        $field = $settings[ 'field' ] ?? $property->getName();
        $custom_parser = $settings[ 'custom_converter' ] ?? null;
        if ( $custom_parser !== null ) {
            $content = [ 'field' => $field, 'converter' => $custom_parser ];
        } else {
            $content = [ 'field' => $field, 'converter' => self::select_parser_for_primitive( $arguments ) ];
        }

        if ( isset( $defaults[ $name ] ) || isset( $settings[ 'default' ] ) ) {
            $default = $defaults[ $name ] ?? $settings[ 'default' ];
            $content[ 'default' ] = $default;
        }

        return [ $name, $content ];
    }

    public static function of_arrays(\ReflectionProperty $property, \ReflectionAttribute $array) {
        $arguments = $array->getArguments();

        $context = $arguments[ 'context' ];
        $classname = $arguments[ 'classname' ];
        $settings = $arguments[ 'settings' ];
        $name = $property->getName();
        $custom_parser = $settings[ 'custom_converter' ] ?? null;

        if ( $custom_parser !== null ) {
            $content = [ 'field' => $name, 'converter' => $custom_parser ];
        } else {
            $content = [ 'field' => $name, 'converter' => ArrayBuilder::of_class( $classname, $context ) ];
        }

        $content[ 'classname' ] = $classname;
        $content[ 'ref' ] = $arguments[ 'ref' ];
        $content[ 'late_bind' ] = true;
        if ( isset( $settings[ 'default' ] ) ) {
            $content[ 'default' ] = $settings[ 'default' ];
        }

        return [ $name, $content ];
    }

    public static function of_class(string $classname, string $context, array $defaults = []) {
        $class = new \ReflectionClass( $classname );
        $properties = [];

        foreach ( $class->getProperties() as $property ) {
            $primitives = $property->getAttributes( MapPrimitive::class );

            foreach ( $primitives as $primitive ) {
                $arguments = $primitive->getArguments();

                if ( $arguments[ 'context' ] !== $context ) {
                    continue;
                }

                $settings = $arguments[ 'settings' ];

                [ $name, $content ] = self::of_primitive( $property, $primitive, $defaults );
                $properties[ $name ] = $content;
            }

            $arrays = $property->getAttributes( MapArray::class );

            foreach ( $arrays as $array ) {
                $arguments = $array->getArguments();

                if ( $arguments[ 'context' ] !== $context ) {
                    continue;
                }

                $settings = $arguments[ 'settings' ];

                [ $name, $content ] = self::of_arrays( $property, $array );
                $properties[ $name ] = $content;
            }
        }

        return new self( $classname, new Properties( $properties ) );
    }

    /**
     *
     * @var classname
     */
    private $classname;

    /**
     *
     * @var Properties
     */
    private $properties;

    /**
     *
     * @var ReflectionClass|null
     */
    private $class;

    public function __construct(string $classname, Properties $properties) {
        $this->classname = $classname;
        $this->properties = $properties;
        $this->class = new \ReflectionClass( $classname );
    }

    private function raw_of(array $info, array $data): mixed {
        $field = $info[ 'field' ] ?? null;

        if ( $field !== null ) {
            return $data[ $field ];
        }

        throw new \RuntimeException( 'cannot find value' );
    }

    private function value_of(array $info, array $data): mixed {
        $converter = $info[ 'converter' ] ?? null;
        $raw = $this->raw_of( $info, $data );

        if ( $converter === null ) {
            return $raw;
        }

        if ( is_callable( $converter ) ) {
            return $converter( $raw );
        }

        if ( $converter->arity() > 1 ) {
            return $converter->instance_all( $raw );
        } else {
            return $converter->instance( $raw );
        }
    }

    public function instance(array $data): object|array {
        $instance = $this->class->newInstanceWithoutConstructor();

        foreach ( $this->properties->all() as $name => $info ) {
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

    public function arity(): int {
        return 1;
    }

    public function late_bind_columns(): array {
        return $this->properties->late_bind();
    }

    public function class(): \ReflectionClass {
        return $this->class;
    }
}
