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

/**
 * Description of Contact
 *
 * @author Alessio
 */
class Contact {

    public function __construct(
            #[MapPrimitive(context: 'sql', kind: 'int|null', settings: [ 'custom_parser' => 'entphp\\datatypes\\to_int' ])]
            private ?int $id,
            #[MapPrimitive(context: 'sql', kind: 'string', settings: [])]
            private string $name,
            #[MapPrimitive(context: 'sql', kind: 'string', settings: [])]
            private string $primary_phone,
            #[MapPrimitive(context: 'sql', kind: 'string', settings: [])]
            private string $primary_email,
            #[MapPrimitive(context: 'sql', kind: 'string', settings: [])]
            private string $secondary_email,
            #[MapPrimitive(context: 'sql', kind: 'string', settings: [])]
            private string $first_name,
            #[MapPrimitive(context: 'sql', kind: 'string', settings: [])]
            private string $last_name,
            #[MapPrimitive(context: 'sql', kind: 'string', settings: [])]
            private string $address_formatted
    ) {

    }

    public function get_id(): ?int {
        return $this->id;
    }

    public function get_name(): string {
        return $this->name;
    }

    public function get_primary_phone(): string {
        return $this->primary_phone;
    }

    public function get_primary_email(): string {
        return $this->primary_email;
    }

    public function get_secondary_email(): string {
        return $this->secondary_email;
    }

    public function get_first_name(): string {
        return $this->first_name;
    }

    public function get_last_name(): string {
        return $this->last_name;
    }

    public function get_address_formatted(): string {
        return $this->address_formatted;
    }
}
