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
use basin\attributes\MapSource;

/**
 * Description of People
 *
 * @author Alessio
 */
#[MapSource(context: 'sql', source: 'people')]
class Person {

    public function __construct(
            #[MapPrimitive(context: 'sql', kind: 'int|null', settings: [])]
            private int $person_id,
            #[MapPrimitive(context: 'sql', kind: 'string', settings: [])]
            private string $first_name,
            #[MapPrimitive(context: 'sql', kind: 'string', settings: [])]
            private string $last_name,
            #[MapPrimitive(context: 'sql', kind: 'date', settings: [])]
            private \DateTimeImmutable $born_at,
            #[MapPrimitive(context: 'sql', kind: 'string', settings: [])]
            private string $notes,
            #[MapArray(context: 'sql', classname: 'Contact', ref: [ 'person_id' => 'person_id' ], settings: [ 'default' => [] ])]
            private array $contacts,
    ) {

    }

    public function get_person_id(): int {
        return $this->person_id;
    }

    public function get_contacts(): array {
        return $this->contacts;
    }

    public function get_first_name(): string {
        return $this->first_name;
    }

    public function get_last_name(): string {
        return $this->last_name;
    }

    public function get_born_at(): \DateTimeImmutable {
        return $this->born_at;
    }

    public function get_notes(): string {
        return $this->notes;
    }
}
