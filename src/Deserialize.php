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
 * Description of Deserialize
 *
 * @author Alessio
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Deserialize {

    public $context;
    public $name;
    public $link;
    public $settings;

    public function __construct(string $context, string $name, ?Link $link, array $settings) {
        $this->context = $context;
        $this->name = $name;
        $this->link = $link;
        $this->settings = $settings;
    }

}
