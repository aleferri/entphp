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
 *
 * @author Alessio
 */
interface Deserializer {

    /**
     * Format select of the query
     * @return string
     */
    public function format_select(): string;

    /**
     * Flatten sources for the query
     * @return array
     */
    public function flatten_sources(): array;

    /**
     * Process data into the target format
     * @param array $data
     * @return array
     */
    public function process(array $data): array;

}
