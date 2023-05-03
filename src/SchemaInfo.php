<?php

/*
 * Copyright 2021 Alessio.
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

namespace archimetrica\entphp;

/**
 * Description of SchemaInfo
 *
 * @author Alessio
 */
class SchemaInfo {

    private $progressive;
    private $primary_keys;
    private $indexes;

    public function __construct(?string $progressive = null) {
        $this->progressive = $progressive;
        $this->primary_keys = [];
        $this->indexes = [];
    }

    public function progressive_name(): ?string {
        return $this->progressive;
    }

    public function primary_keys(): array {
        return $this->primary_keys;
    }

    public function indexes(): array {
        return $this->indexes;
    }

    public function indexed_by(string $name): void {
        $this->indexes[] = $name;
    }

    public function identified_by(string ...$keys): void {
        $this->primary_keys = $keys;
    }

}
