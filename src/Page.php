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
 * Description of Page
 *
 * @author Alessio
 */
class Page {

    public static function limit_only(int $limit): self {
        return new self ( [], $limit );
    }

    private $from;
    private $limit;

    public function __construct(int|array $from, int $limit) {
        $this->from = $from;
        $this->limit = $limit;
    }

    public function from(): int|array {
        return $this->from;
    }

    public function limit(): int {
        return $this->limit;
    }

}
