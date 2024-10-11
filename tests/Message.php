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

use basin\attributes\MapPrimitive;
use basin\attributes\MapObject;
use basin\attributes\MapSource;
use basin\attributes\MapIdentity;

/**
 * Description of Message
 *
 * @author Alessio
 */
#[MapSource(context: 'sql', source: 'messages')]
class Message {

    public function __construct(
        #[MapPrimitive(context: 'sql', kind: 'int|null', settings: [])]
        #[MapIdentity(context: 'sql', kind: 'single')]
        private ?int $message_id,
        #[MapPrimitive(context: 'sql', kind: 'string', settings: [])]
        public string $message_text,
        #[MapPrimitive(context: 'sql', kind: 'date', settings: [])]
        public \DateTimeImmutable $sent_at,
        #[MapObject(context: 'sql', classname: 'Person', ref: [], settings: [])]
        public ?Person $from_person,
        #[MapObject(context: 'sql', classname: 'Address', ref: [], settings: [])]
        public ?Address $from_location,
        #[MapObject(context: 'sql', classname: 'Person', ref: [], settings: [])]
        public ?Person $to_person,
        #[MapObject(context: 'sql', classname: 'Address', ref: [], settings: [])]
        public ?Address $to_location,
    ) {

    }

}
