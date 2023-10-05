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

use basin\concepts\Schema;

/**
 * Description of TableSchema
 *
 * @author Alessio
 */
class TableSchema implements Schema {

    public static function of_class(\ReflectionClass $class, string $context): TableSchema {
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

        return new self( '', new Properties( $properties ) );
    }

    private $source;
    private $properties;
    private $readonly;

    public function __construct(string $root, Properties $properties, bool $readonly = false) {
        $this->source = $root;
        $this->properties = $properties;
        $this->readonly = $readonly;
    }

    public function root_source(): string {
        return $this->source;
    }

    public function sources(): array {
        $far_sourced = $this->properties->far_sourced_properties();

        $sources = [];

        foreach ( $far_sourced as $name => $property ) {
            if ( !isset( $property[ 'classname' ] ) ) {
                continue;
            }

            $classname = $property[ 'classname' ];
            if ( !isset( $sources[ $classname ] ) ) {
                $sources[ $classname ] = [];
            }

            $sources[ $classname ][] = $name;
        }

        return $sources;
    }

    public function properties(): array {
        return $this->properties->all();
    }

    public function local_sourced_properties(): array {
        return $this->properties->local_sourced_properties();
    }

    //put your code here
    public function far_sourced_properties(): array {
        return $this->properties->far_sourced_properties();
    }

    public function is_cacheable(): bool {
        return true;
    }

    public function is_cascade_readable(string $source): bool {

    }

    public function is_cascade_writable(string $source): bool {

    }

    public function is_data_cacheable(): bool {
        return true;
    }

    public function is_data_cascade_cacheable(string $source): bool {

    }

    public function is_readable(): bool {
        return true;
    }

    public function is_writeable(): bool {
        return !$this->readonly;
    }
}
