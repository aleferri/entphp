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

/**
 * Description of TypeBuilder
 *
 * @author Alessio
 */
class FlatBuilder implements TypeBuilder {

    public static function of_class(string $classname, string $context, array $defaults = []) {
        $class = new \ReflectionClass( $classname );
        $properties = [];

        foreach ( $class->getProperties() as $property ) {
            $primitives = $property->getAttributes( MapPrimitive::class );
            foreach ( $primitives as $primitive ) {
                $arguments = $primitive->getArguments();

                if ( $arguments[ 'context' ] === $context ) {
                    $settings = $arguments[ 'settings' ];
                    $name = $property->getName();
                    $field = $settings[ 'field' ] ?? $property->getName();
                    $custom_parser = $settings[ 'custom_parser' ] ?? null;
                    if ( $custom_parser !== null ) {
                        $properties[ $name ] = [ 'field' => $field, 'parse' => $custom_parser ];
                    } else {
                        $properties[ $name ] = $field;
                    }

                    if ( isset( $settings[ 'default' ] ) ) {
                        $defaults[ $name ] = $settings[ 'default' ];
                    }
                }
            }
        }

        return new self( $classname, $properties, $defaults );
    }

    /**
     *
     * @var classname
     */
    private $classname;

    /**
     *
     * @var array<string, TypeBuilder|callable|string>
     */
    private $properties;

    /**
     *
     * @var ReflectionClass|null
     */
    private $class;

    /**
     *
     * @var array<string, mixed>
     */
    private $defaults;

    public function __construct(string $classname, array $properties, array $defaults = []) {
        $this->classname = $classname;
        $this->properties = $properties;
        $this->class = new \ReflectionClass( $classname );
        $this->defaults = $defaults;
    }

    private function value_of(string $name, string|array|callable|TypeBuilder $builder, array $data): mixed {
        $property = $this->class->getProperty( $name );
        $property->setAccessible( true );

        $default = $this->defaults[ $name ] ?? null;

        if ( is_string( $builder ) ) {
            return $data[ $builder ] ?? $default;
        } else if ( is_array( $builder ) ) {
            $raw = $data[ $builder[ 'field' ] ] ?? $default;
            return $builder[ 'parse' ]( $raw );
        } else if ( is_callable( $builder ) ) {
            $value = $builder( $data, $this->defaults );
            if ( $value === null ) {
                return $default;
            } else {
                return $value;
            }
        } else {
            return $builder->instance( $data );
        }
    }

    public function instance(array $data): object|array {
        $instance = $this->class->newInstanceWithoutConstructor();

        foreach ( $this->properties as $name => $builder ) {
            $property = $this->class->getProperty( $name );
            $property->setAccessible( true );

            $value = $this->value_of( $name, $builder, $data );
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
}
