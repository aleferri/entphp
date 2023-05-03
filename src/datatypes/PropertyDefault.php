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

namespace archimetrica\entphp\datatypes;

use archimetrica\entphp\Property;
use archimetrica\entphp\Tag;

/**
 * Description of PropertyTrait
 *
 * @author Alessio
 */
trait PropertyDefault {

    /**
     *
     * @var list<entphp\Tag>
     */
    private $tags;

    protected function init_tags() {
        $this->tags = [];
    }

    public function query_tag(string $name): ?Tag {
        foreach ( $this->tags as $tag ) {
            if ( $tag->name () == $name ) {
                return $tag;
            }
        }
        return null;
    }

    public function tag(Tag $tag): Property {
        $this->tags[] = $tag;
        return $this;
    }

    public function tags(): array {
        return $this->tags;
    }

}
