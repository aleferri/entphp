<?php

/*
 * Copyright 2022 Alessio.
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
 * Description of DeserializerArray
 *
 * @author Alessio
 */
class DeserializerArray implements Deserializer {

    private $list;
    private $root;

    public function __construct(array $list, string $root) {
        $this->list = $list;
        $this->root = $root;
    }

    public function format_select(): string {
        return implode ( ', ', $this->list );
    }

    public function flatten_sources(): array {
        $sources = [];

        foreach ( $this->list as $prop ) {
            [ $ns, $name ] = explode ( '.', $prop );
            $sources[ $ns ] = $name;
        }

        return [ $this->root, array_keys ( $sources ) ];
    }

    public function process(array $data): array {
        return $data;
    }

}
