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

function to_int(mixed $raw): int|null {
    if ( is_int( $raw ) ) {
        return $raw;
    }

    if ( is_float( $raw ) ) {
        return intval( $raw );
    }

    if ( is_string( $raw ) && is_numeric( $raw ) ) {
        return intval( $raw );
    }

    if ( is_object( $raw ) && method_exists( $raw, 'to_int' ) ) {
        return $raw->to_int();
    }

    return null;
}

function to_int_strict(mixed $raw): int {
    $value = to_int( $raw );

    if ( $value === null ) {
        throw new \RuntimeException( $raw . ' is not a valid integer' );
    }

    return $value;
}
