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

use basin\attributes\MapPrimitive;
use basin\attributes\MapIdentity;
use basin\attributes\MapSource;

/**
 * Description of Address
 *
 * @author Alessio
 */
#[MapSource(context: 'sql', source: 'addresses')]
class Address {

    public function __construct(
        #[MapPrimitive(context: 'sql', kind: 'int|null', settings: [])]
        #[MapIdentity(context: 'sql', kind: 'single')]
        private ?int $id = null,
        #[MapPrimitive(context: 'sql', kind: 'string', settings: [])]
        private string $title = '',
        #[MapPrimitive(context: 'sql', kind: 'string', settings: [])]
        private string $country = '',
        #[MapPrimitive(context: 'sql', kind: 'string', settings: [])]
        private string $state = '',
        #[MapPrimitive(context: 'sql', kind: 'string', settings: [])]
        private string $subdivision = '',
        #[MapPrimitive(context: 'sql', kind: 'string', settings: [])]
        private string $town = '',
        #[MapPrimitive(context: 'sql', kind: 'string', settings: [])]
        private string $place = '',
        #[MapPrimitive(context: 'sql', kind: 'string', settings: [])]
        private string $postcode = '',
        #[MapPrimitive(context: 'sql', kind: 'string', settings: [])]
        private string $address_kind = '',
        #[MapPrimitive(context: 'sql', kind: 'string', settings: [])]
        private string $address_line_0 = '',
        #[MapPrimitive(context: 'sql', kind: 'string', settings: [])]
        private string $address_line_1 = '',
        #[MapPrimitive(context: 'sql', kind: 'string', settings: [ 'len' => 'longtext' ])]
        private string $notes = ''
    ) {

    }

    public function get_id(): ?int {
        return $this->id;
    }

    public function get_title(): string {
        return $this->title;
    }

    public function get_country(): string {
        return $this->country;
    }

    public function get_state(): string {
        return $this->state;
    }

    public function get_subdivision(): string {
        return $this->subdivision;
    }

    public function get_town(): string {
        return $this->town;
    }

    public function get_place(): string {
        return $this->place;
    }

    public function get_postcode(): string {
        return $this->postcode;
    }

    public function get_address_kind(): string {
        return $this->address_kind;
    }

    public function get_address_line_0(): string {
        return $this->address_line_0;
    }

    public function get_address_line_1(): string {
        return $this->address_line_1;
    }

    public function get_notes(): string {
        return $this->notes;
    }

    public function as_formatted(string $format): string {
        $props = get_object_vars( $this );

        foreach ( $props as $name => $value ) {
            $format = str_replace( '{' . $name . '}', $value, $format );
        }

        return $format;
    }

    public function as_formatted_short(): string {
        return $this->as_formatted( '{town} ({postcode}) in {address_kind} {address_line_0} {address_line_1}' );
    }

}
