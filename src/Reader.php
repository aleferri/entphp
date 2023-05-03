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

namespace alessio\entphp;

/**
 *
 * @author Alessio
 */
interface Reader {

    /**
     * Fetch by primary key
     * @param array|string $select selection: class name or array of fields
     * @param int|string|array $primary primary key/keys
     * @param string|null $root starting table, required for array
     * @return array|object result, class instance if class name, array if an array is passed as select
     */
    public function fetch_by_primary(array|string $select, int|string|array $primary, ?string $root = null): array|object;

    public function fetch_all(array|string $select, ?string $root = null): array;

    public function fetch_page(array|string $select, Condition $condition, Page $page, ?string $root = null): array;

}
