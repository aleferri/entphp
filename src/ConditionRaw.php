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
 * Description of ConditionRaw
 *
 * @author Alessio
 */
class ConditionRaw implements Condition {

    public static function of(string $cond, array $args): self {
        return new self ( $cond, $args );
    }

    private $cond;
    private $args;

    public function __construct(string $cond, array $args) {
        $this->cond = $cond;
        $this->args = $args;
    }

    public function args(): array {
        return $this->args;
    }

    public function to_string(): string {
        return $this->cond;
    }

}
