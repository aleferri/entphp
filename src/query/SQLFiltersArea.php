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

namespace entphp\query;

/**
 * Description of QueryArea
 *
 * @author Alessio
 */
class SQLFiltersArea {

    private $id;
    private $values;
    private $expression;

    public function __construct(string $id, string $expression, array $values) {
        $this->id = $id;
        $this->expression = $expression;
        $this->values = $values;
    }

    public function id(): string {
        return $this->id;
    }

    public function fold_right(string $rel, string $left, mixed ...$values): self {
        $this->expression = "{$left} {$rel} ( {$this->expression} )";
        $this->values = [ ...$values, ...$this->values ];

        return $this;
    }

    public function fold_left(string $rel, string $right, mixed ...$values): self {
        $this->expression = "( {$this->expression} ) {$rel} {$right}";
        $this->values = [ ...$this->values, ...$values ];

        return $this;
    }

    public function fold(): self {
        $this->expression = "( {$this->expression} )";

        return $this;
    }

    public function chain_left(string $rel, string $expr, mixed ...$values): self {
        $this->expression = "{$this->expression} {$rel} {$expr}";
        $this->values = [ ...$this->values, ...$values ];

        return $this;
    }

    public function chain_right(string $rel, string $expr, mixed ...$values): self {
        $this->expression = "{$expr} {$rel} {$this->expression}";
        $this->values = [ ...$this->values, ...$values ];

        return $this;
    }

    public function append(mixed $value): self {
        $this->values[] = $value;

        return $this;
    }

    public function values(): array {
        return $this->values;
    }

    public function expression(): string {
        return $this->expression;
    }
}
