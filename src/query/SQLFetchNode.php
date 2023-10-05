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

namespace entphp\query;

use basin\attributes\MapSource;
use basin\concepts\convert\Deserializer;
use entphp\datatypes\ObjectDeserializer;

/**
 * Description of SQLFetchNode
 *
 * @author Alessio
 */
class SQLFetchNode {

    public static function of_class(string $classname, ?Deserializer $builder = null): self {
        $class = new \ReflectionClass( $classname );
        $attributes = $class->getAttributes( MapSource::class );

        foreach ( $attributes as $attribute ) {
            $source = $attribute;
        }

        if ( count( $attributes ) === 0 ) {
            throw new \RuntimeException( 'missing source for class ' . $classname );
        }

        $type_builder = $builder ?? ObjectDeserializer::of_class( $classname, 'sql' );

        return new self( $classname, $source, $type_builder );
    }

    private $classname;
    private $source;
    private $builder;

    public function __construct(string $classname, \ReflectionAttribute $source, Deserializer $builder) {
        $this->classname = $classname;
        $this->source = $source;
        $this->builder = $builder;
    }

    public function query_for(array $values): SQLFetchQueryBuilder {
        $arguments = $this->source->getArguments();

        $table = $arguments[ 'source' ];
        $query = SQLFetchQueryBuilder::start()
                ->from( $table, 'ent' )
                ->select( 'ent.*' );

        foreach ( $values as $key => $list ) {
            $query = $query->filter_by( 'per_row_' . $key, $key . ' IN (' . implode( ',', $list ) . ')' );
        }

        return $query;
    }

    public function builder(): Deserializer {
        return $this->builder;
    }
}
