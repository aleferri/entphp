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
use basin\attributes\MapArray;
use basin\attributes\MapObject;
use basin\attributes\MapSource;
use basin\attributes\MapIdentity;

/**
 * Description of People
 *
 * @author Alessio
 */
#[MapSource(context: 'sql', source: 'people')]
class Person {

    public function __construct(
        #[MapPrimitive(context: 'sql', kind: 'int|null', settings: [])]
        #[MapIdentity(context: 'sql', kind: 'single')]
        private ?int $person_id,
        #[MapPrimitive(context: 'sql', kind: 'string', settings: [])]
        private string $first_name,
        #[MapPrimitive(context: 'sql', kind: 'string', settings: [])]
        private string $last_name,
        #[MapPrimitive(context: 'sql', kind: 'date', settings: [])]
        private \DateTimeImmutable $born_at,
        #[MapPrimitive(context: 'sql', kind: 'string', settings: [ 'default' => '', 'length' => 'medium' ])]
        private string $notes = '',
        #[MapObject(context: 'sql', classname: 'Address', ref: [], settings: [])]
        private ?Address $address = null,
        #[MapArray(context: 'sql', classname: 'Contact', ref: [ 'person_id' => 'person_id' ],
                settings: [ 'default' => [] ])]
        private array $contacts = [],
    ) {

    }

    public function get_person_id(): ?int {
        return $this->person_id;
    }

    public function get_address(): ?Address {
        return $this->address;
    }

    public function with_address(?Address $address): Person {
        $person = clone $this;
        $person->address = $address;
        return $person;
    }

    public function get_contacts(): array {
        return $this->contacts;
    }

    public function add_contact(Contact $contact): void {
        $this->contacts[] = $contact;
    }

    public function remove_contact(Contact $drop): void {
        $contacts = [];

        foreach ( $this->contacts as $contact ) {
            if ( $contact->get_id() !== 0 && $drop->get_id() === 0 ) {
                $contacts[] = $contact;
            }

            if ( $contact->get_id() !== $drop->get_id() ) {

            }
        }
    }

    public function get_first_name(): string {
        return $this->first_name;
    }

    public function with_first_name(string $firstname): Person {
        $person = clone $this;
        $person->first_name = $firstname;
        return $person;
    }

    public function get_last_name(): string {
        return $this->last_name;
    }

    public function with_last_name(string $lastname): Person {
        $person = clone $this;
        $person->last_name = $lastname;
        return $person;
    }

    public function get_born_at(): \DateTimeImmutable {
        return $this->born_at;
    }

    public function get_notes(): string {
        return $this->notes;
    }

    public function with_notes(string $notes): Person {
        $person = clone $this;
        $person->notes = $notes;
        return $person;
    }

}
