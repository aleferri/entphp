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
 * Description of EntityDescriptorBuilder
 *
 * @author Alessio
 */
class EntityDescriptorBuilder {

    public static function of_class(string $classname) {
        return new self ( $classname, EntityDescriptor::CLASS_ENTITY );
    }

    public static function of_array(string $entity) {
        return new self ( $entity, EntityDescriptor::ARRAY_ENTITY );
    }

    private $name;
    private $kind;
    private $root;
    private $primary;
    private $links;
    private $read_map;
    private $write_map;
    private $linked;

    public function __construct(string $name, int $kind) {
        $this->name = $name;
        $this->kind = $kind;
        $this->root = '_';
        $this->primary = [];
        $this->links = [];
        $this->read_map = [];
        $this->write_map = [];
        $this->linked = '_';
    }

    public function from(string $root): self {
        $this->root = $root;
        $this->linked = $root;

        return $this;
    }

    public function link(Link $link): self {
        $this->links[] = $link;
        $this->linked = $link->to;

        return $this;
    }

    private function recover_values(object $attribute, object $property) {
        $name = $attribute->name;
        if ( $name === '' ) {
            $name = $property->getName ();
        }

        $origin = $attribute->origin;
        if ( $origin === '' ) {
            $origin = $this->root;
        }

        return [ $name, $origin ];
    }

    public function autodiscover(): self {
        $rf_class = new \ReflectionClass ( $this->name );
        $roots = $rf_class->getAttributes ( EntityRoot::class );
        $origins = $rf_class->getAttributes ( EntitySource::class );

        if ( count ( $roots ) === 0 ) {
            throw new \RuntimeException ( "Root table is required" );
        }
        $root = $roots[ 0 ]->newInstance ();

        $this->from ( $root->source );
        foreach ( $origins as $origin ) {
            $this->link ( $origin->newInstance ()->link );
        }

        $properties = $rf_class->getProperties ();
        foreach ( $properties as $property ) {
            $attributes = $property->getAttributes ( PropertyInfo::class );
            foreach ( $attributes as $attribute ) {
                $instance = $attribute->newInstance ();
                if ( $instance->context === 'sql' ) {
                    [ $name, $origin ] = $this->recover_values ( $instance, $property );
                    $this->map ( $property->getName (), $name, $origin );
                }
            }
        }

        return $this;
    }

    public function map(string $property, string $key, ?string $table = null): self {
        $link = $table ?? $this->linked;
        $fqn = $link . '.' . $key;
        $this->read_map[ $property ] = $fqn;
        $this->write_map[ $fqn ] = $property;

        return $this;
    }

    public function map_all_same(string ...$list): self {
        foreach ( $list as $key ) {
            $this->map ( $key, $key );
        }

        return $this;
    }

    public function primary_keys(string|array $keys) {
        $this->primary = $keys;

        return $this;
    }

    public function primary_keys_discoverer(callable $fn): self {
        $this->primary = $fn ( $this->root );

        return $this;
    }

    public function into_descriptor(): EntityDescriptor {
        return new EntityDescriptor ( $this->name, $this->kind, $this->root, $this->primary, $this->links, $this->read_map, $this->write_map );
    }

}
