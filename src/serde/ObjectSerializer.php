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

use basin\concepts\convert\Serializer;
use basin\concepts\Schema;
use entphp\identity\IdentityTracker;
use entphp\identity\TransientIdentity;
use entphp\meta\MetadataStore;

/**
 * Description of ObjectSerializer
 *
 * @author Alessio
 */
class ObjectSerializer implements Serializer {

    public static function of_class(string $classname, string $context, IdentityTracker $id_factory) {
        $metadata = new MetadataStore( $context );
        $metadata->visit( $classname );

        return new self( $classname, $metadata, $id_factory );
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
     * @var type
     */
    private $id_factory;

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

    public function __construct(string $classname, MetadataStore $metadata, IdentityTracker $id_factory, array $defaults = []) {
        $this->classname = $classname;
        $this->metadata = $metadata;
        $this->id_factory = $id_factory;
        $this->defaults = $defaults;
        $this->class = new \ReflectionClass( $classname );
    }

    private function init_row(array $defaults, array $link, ?object $object): array {
        $row = [];

        foreach ( $defaults as $key => $value ) {
            $row[ $key ] = $value;
        }

        foreach ( $link as $handle => $value ) {
            $row[ $handle ] = $value;
        }

        $identity = $this->id_factory->identity_of( $object );

        // Try create a transient identity
        if ( $identity === null ) {
            $identity = $this->id_factory->track_transient( $object );
        }

        if ( $identity instanceof TransientIdentity ) {
            $row = $identity->fill_transients( $row );
        }

        $row[ '__identity' ] = $identity;

        return $row;
    }

    private function export_local_properties(array $row, $object, array $properties, \ReflectionClass $class): array {
        foreach ( $properties as $name => $info ) {
            $property = $class->getProperty( $name );
            $property->setAccessible( true );

            $value = $property->getValue( $object );
            if ( $value instanceof \DateTimeImmutable && $info[ 'kind' ] === 'date' ) {
                $value = $value->format( 'Y-m-d' );
            }
            $row[ $name ] = $value;
        }

        return $row;
    }

    private function throw_missing_field(string $field, string $classname, string $identity) {
        throw new \RuntimeException( 'missing field ' . $field . ' of class ' . $classname . ' with ' . $identity );
    }

    private function link_from(array $row, array $link_spec, string $classname = ''): array {
        $link_data = [];

        foreach ( $link_spec as $key => $handle ) {
            if ( isset( $row[ $key ] ) ) {
                $link_data[ $handle ] = $row[ $key ];
            } else {
                if ( !isset( $row[ '__identity' ] ) ) {
                    $this->throw_missing_field( $key, $classname, print_r( $row, true ) );
                }
                $identity = $row[ '__identity' ];

                if ( !$identity->has_field( $key ) || !$identity instanceof TransientIdentity ) {
                    $this->throw_missing_field( $key, $classname, print_r( $identity, true ) );
                }

                $transient_key = $identity->replace_with_transient( $key );

                if ( !isset( $row[ $transient_key ] ) ) {
                    $this->throw_missing_field( $transient_key, $classname, print_r( $identity, true ) );
                }

                $link_data[ $handle ] = $row[ $transient_key ];

                if ( !isset( $link_data[ '__transient_patches' ] ) ) {
                    $link_data[ '__transient_patches' ] = [];
                }
                $link_data[ '__transient_patches' ][ $transient_key ] = $key;
            }
        }

        return $link_data;
    }

    private function link_to(?object $value, string $classname): \basin\concepts\Identity {
        if ( $value === null ) {
            return $this->id_factory->track_transient( null, $classname );
        }

        $identity = $this->id_factory->identity_of( $value );
        if ( $identity !== null ) {
            return $identity;
        }

        throw new \RuntimeException( 'cannot get an identity for the relation' );
    }

    private function merge_data(array $data, string $source, array $record): array {
        if ( !isset( $data[ $source ] ) ) {
            $data[ $source ] = [ $record ];
        } else {
            $data[ $source ] = array_merge( $data[ $source ], [ $record ] );
        }
        return $data;
    }

    private function recursive_breakup_array(array $data, Schema $schema, \ReflectionClass $class, array $items, array $link = []): array {
        foreach ( $items as $item ) {
            $data = $this->recursive_breakup( $data, $schema, $class, $item, $link );
        }

        return $data;
    }

    private function recursive_breakup(array $data, Schema $schema, \ReflectionClass $class, $object, array $link = []): array {
        if ( $object === null ) {
            return $data;
        }

        $source = $schema->root_source();

        $init = $this->init_row( [], $link, $object );

        $row = $this->export_local_properties( $init, $object, $schema->local_sourced_properties(), $class );

        foreach ( $schema->foreign_sourced_properties() as $name => $info ) {
            $property = $class->getProperty( $name );
            $property->setAccessible( true );

            $value = $property->getValue( $object );

            $link_spec = $info[ 'link' ];

            $child_schema = $this->metadata->schema_of( $info[ 'classname' ] );
            $child_class = new \ReflectionClass( $info[ 'classname' ] );
            if ( $info[ 'arity' ] === 'n' ) {
                $link = $this->link_from( $row, $link_spec, $class->name );
                $data = $this->recursive_breakup_array( $data, $child_schema, $child_class, $value, $link );
            } else {
                $data = $this->recursive_breakup( $data, $child_schema, $child_class, $value, [] );
                $link_identity = $this->link_to( $value, $info[ 'classname' ] );

                $row = $link_identity->fill_as_fk( $row, $name );
            }
        }

        return $this->merge_data( $data, $source, $row );
    }

    public function breakup(object $object): array {
        return $this->recursive_breakup( [], $this->schema(), $this->class, $object );
    }

    public function breakup_all(array $objects): array {
        $data = [];

        foreach ( $objects as $object ) {
            $data = $this->recursive_breakup( $data, $this->schema(), $this->class, $object );
        }

        return $data;
    }

    public function class(): \ReflectionClass {
        return $this->class;
    }

    public function schema(): Schema {
        return $this->metadata->schema_of( $this->classname );
    }
}
