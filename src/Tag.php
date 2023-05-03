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
 * Description of Tag
 *
 * @author Alessio
 */
class Tag {

    private $name;
    private $value;

    /**
     * Crea il tag per una proprietà
     * @param string $name
     * @param mixed $value
     */
    public function __construct(string $name, $value) {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * Nome del tag
     * @return string
     */
    public function name(): string {
        return $this->name;
    }

    /**
     * Valore del tag
     * @return mixed
     */
    public function value() {
        return $this->value;
    }

}
