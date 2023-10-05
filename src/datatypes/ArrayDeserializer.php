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
use basin\attributes\MapPrimitive;
use basin\attributes\MapArray;
use basin\attributes\MapSource;

/**
 * Description of ArrayBuilder
 *
 * @author Alessio
 */
class ArrayDeserializer implements SchemaDeserializer {

    public static function of_class(string $classname, string $context): self {
        $instance_builder = ObjectDeserializer::of_class( $classname, $context );

        $attributes = $instance_builder->class()->getAttributes( MapSource::class );

        foreach ( $attributes as $attribute ) {
            $source = $attribute;
        }

        if ( count( $attributes ) === 0 ) {
            throw new \RuntimeException( 'missing source for class ' . $classname );
        }

        $arguments = $source->getArguments();

        return new self( $instance_builder, $arguments[ 'source' ] );
    }

    private $instance_builder;
    private $source;

    public function __construct(SchemaDeserializer $instance_builder, string $source) {
        $this->instance_builder = $instance_builder;
        $this->source = $source;
    }

    public function instance(array $data): object|array {
        return $this->instance_builder->instance( $data );
    }

    public function instance_all(array $records): array {
        return $this->instance_builder->instance_all( $records );
    }

    public function arity(): int {
        return 2;
    }

    public function late_bind_columns() {
        return $this->instance_builder->schema()->far_sourced_properties();
    }

    public function source(): string {
        return $this->source;
    }

    public function schema(): Schema {
        return $this->instance_builder->schema();
    }
}
