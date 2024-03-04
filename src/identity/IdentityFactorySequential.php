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

namespace entphp\identity;

use basin\concepts\Persistable;
use basin\concepts\Identity;
use basin\attributes\MapIdentity;

/**
 * Description of IdentityFactorySequential
 *
 * @author Alessio
 */
class IdentityFactorySequential implements IdentityFactory {

    private $context;
    private $sequence;
    private $transaction;
    private $cache;

    public function __construct(string $context, int $sequence = 0, array $transaction = []) {
        $this->context = $context;
        $this->sequence = $sequence;
        $this->transaction = $transaction;
        $this->cache = [];
    }

    private function cache_identity_info(string $classname) {
        if ( isset( $this->cache[ $classname ] ) ) {
            return $this->cache[ $classname ];
        }

        $class = new \ReflectionClass( $classname );

        $identity_info = [];

        foreach ( $class->getProperties() as $property ) {
            $identity_fields = $property->getAttributes( MapIdentity::class );

            foreach ( $identity_fields as $field ) {
                $arguments = $field->getArguments();

                if ( $arguments[ 'context' ] !== $this->context ) {
                    continue;
                }

                $settings = $arguments[ 'settings' ] ?? [];
                $identity_info[] = [ 'settings' => $settings, 'field' => $property ];
            }
        }

        $this->cache[ $classname ] = $identity_info;
        return $identity_info;
    }

    private function next() {
        $this->sequence += 1;
        return $this->sequence;
    }

    public function patch(int $transient, int $id) {
        $this->transaction[ $transient ] = $id;
    }

    public function patch_identity(Persistable $persistable, Identity $identity): array {

    }

    private function patch_transient_identity(array $data) {
        if ( ! isset( $data[ '__identity' ] ) ) {
            return $data;
        }

        $identity = $data[ '__identity' ];

        if ( $identity === null ) {
            throw new RuntimeException( 'identity is null' );
        }

        return $data;
    }

    private function patch_transient_links(array $data) {
        if ( ! isset( $data[ '__transient_patches' ] ) ) {
            return $data;
        }

        $patches = $data[ '__transient_patches' ];

        foreach ( $patches as $field ) {
            $transient_id = $data[ $field ];
            $id = $this->transaction[ $transient_id ];

            $data[ $field ] = $id;
        }

        return $data;
    }

    public function patch_transients(array $data): array {
        return $this->patch_transient_links(
                $this->patch_transient_identity( $data )
        );
    }

    public function provide_transient(Persistable $persistable): Identity {
        $classname = \get_class( $persistable );

        $identity_info = $this->cache_identity_info( $classname );

        $fields = [];
        $transients = [];

        foreach ( $identity_info as $field_info ) {
            $field = $field_info[ 'field' ]->getName();
            $fields[] = $field;
            $transients[ $field ] = $this->next();
        }

        $identity = new TransientIdentity( $fields, $transients );
        $persistable->__transient_identity( $identity );

        return $identity;
    }

    public function empty_identity(string $classname): Identity {
        $identity_info = $this->cache_identity_info( $classname );

        $fields = [];
        $values = [];

        foreach ( $identity_info as $field_info ) {
            $field = $field_info[ 'field' ]->getName();
            $fields[] = $field;
            $values[ $field ] = null;
        }

        $identity = new EmptyIdentity( $fields, $values );

        return $identity;
    }

}
