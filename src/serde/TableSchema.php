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

use basin\concepts\Schema;
use basin\attributes\MapPrimitive;
use basin\attributes\MapArray;
use basin\attributes\MapObject;
use basin\attributes\MapSource;
use entphp\datatypes\None;

/**
 * Description of TableSchema
 *
 * @author Alessio
 */
class TableSchema implements Schema {

    public static function source_of(\ReflectionClass $class, string $context): string {
        $attributes = $class->getAttributes( MapSource::class );

        $source = null;

        foreach ( $attributes as $attribute ) {
            $args = $attribute->getArguments();

            if ( $args[ 'context' ] !== $context ) {
                continue;
            }

            $source = $args[ 'source' ];
        }

        if ( $source === null ) {
            throw new \RuntimeException( 'missing source for class ' . $class->getName() );
        }

        return $source;
    }

    public static function of_primitive(\ReflectionProperty $property, \ReflectionAttribute $primitive) {
        $arguments = $primitive->getArguments();

        $settings = $arguments[ 'settings' ];
        $name = $property->getName();
        $field = $settings[ 'field' ] ?? $property->getName();
        $kind = $arguments[ 'kind' ];
        $custom_converter = $settings[ 'custom_converter' ] ?? null;

        $content = [
                'arity'     => 1,
                'field'     => $field,
                'kind'      => $kind,
                'converter' => $custom_converter,
                'default'   => $settings[ 'default' ] ?? None::instance(),
        ];

        return [ $name, $content ];
    }

    public static function of_array(\ReflectionProperty $property, \ReflectionAttribute $array, string $context) {
        $arguments = $array->getArguments();

        $classname = $arguments[ 'classname' ];
        $settings = $arguments[ 'settings' ];
        $name = $property->getName();
        $custom_converter = $settings[ 'custom_converter' ] ?? null;

        $content = [
                'arity'       => 'n',
                'field'       => $name,
                'classname'   => $classname,
                'converter'   => $custom_converter,
                'location'    => 'foreign',
                'link'        => $arguments[ 'ref' ],
                'default'     => $settings[ 'default' ] ?? [],
                'item_schema' => [ $classname, $context ],
        ];

        return [ $name, $content ];
    }

    public static function of_object(\ReflectionProperty $property, \ReflectionAttribute $object, string $context) {
        $arguments = $object->getArguments();

        $classname = $arguments[ 'classname' ];
        $settings = $arguments[ 'settings' ];
        $name = $property->getName();
        $custom_converter = $settings[ 'custom_converter' ] ?? null;

        $content = [
                'arity'       => '?',
                'field'       => $name,
                'classname'   => $classname,
                'converter'   => $custom_converter,
                'location'    => 'foreign',
                'link'        => [],
                'default'     => $settings[ 'default' ] ?? null,
                'item_schema' => [ $classname, $context ],
        ];

        return [ $name, $content ];
    }

    public static function of_class(\ReflectionClass $class, string $context): TableSchema {
        $source = self::source_of( $class, $context );

        $properties = [];

        foreach ( $class->getProperties() as $property ) {
            $primitives = $property->getAttributes( MapPrimitive::class );

            foreach ( $primitives as $primitive ) {
                $arguments = $primitive->getArguments();

                if ( $arguments[ 'context' ] !== $context ) {
                    continue;
                }

                $settings = $arguments[ 'settings' ];

                [ $name, $content ] = self::of_primitive( $property, $primitive );
                $properties[ $name ] = $content;
            }

            $arrays = $property->getAttributes( MapArray::class );

            foreach ( $arrays as $array ) {
                $arguments = $array->getArguments();

                if ( $arguments[ 'context' ] !== $context ) {
                    continue;
                }

                $settings = $arguments[ 'settings' ];

                [ $name, $content ] = self::of_array( $property, $array, $context );
                $properties[ $name ] = $content;
            }

            $objects = $property->getAttributes( MapObject::class );

            foreach ( $objects as $object ) {
                $arguments = $object->getArguments();

                if ( $arguments[ 'context' ] !== $context ) {
                    continue;
                }

                $settings = $arguments[ 'settings' ];

                [ $name, $content ] = self::of_object( $property, $object, $context );
                $properties[ $name ] = $content;
            }
        }

        return new self( $source, new Properties( $properties ) );
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
        $far_sourced = $this->properties->foreign_sourced_properties();

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

    public function set_property_field(string $key, string $field, mixed $value) {
        $this->properties->set( $key, $field, $value );
    }

    public function properties(): array {
        return $this->properties->all();
    }

    public function local_sourced_properties(): array {
        return $this->properties->local_sourced_properties();
    }

    public function foreign_sourced_properties(): array {
        return $this->properties->foreign_sourced_properties();
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
