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
 *
 * @author Alessio
 */
interface Property {

    /**
     * Nome proprietà
     * @return string
     */
    public function name(): string;

    /**
     * List of valid datatypes
     * @return array
     */
    public function datatype(): array;

    /**
     * Lista dei tag
     * @return array
     */
    public function tags(): array;

    /**
     * Ritorna il tag nominato
     * @param string $name
     * @return Tag
     */
    public function query_tag(string $name): ?Tag;

    /**
     * Tagga la proprietà
     * @param Tag $tag tag da aggiungere
     * @return la proprientà modificata
     */
    public function tag(Tag $tag): Property;

}
