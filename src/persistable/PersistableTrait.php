<?php

/*
 * Copyright 2024 Alessio.
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

namespace entphp\persistable;

use basin\concepts\Identity;
use basin\concepts\Persistable;

/**
 * Description of PersistableTrait
 *
 * @author Alessio
 */
trait PersistableTrait {

    private $__identity = null;
    private $__transient_identity = null;

    public function __transient_identity(?Identity $identity): ?Identity {
        $old = $this->__transient_identity;
        if ( $identity !== null ) {
            $this->__transient_identity = $identity;
        }

        return $old;
    }

    public function __identity(?Identity $identity): ?Identity {
        $old = $this->__identity;
        if ( $identity !== null ) {
            $this->__identity = $identity;

            $values = $this->__identity->values();
            foreach ( $this->__identity->fields() as $field ) {
                $this->$field = $values[ $field ];
            }
        }

        return $old;
    }

    /**
     * Equals
     * @param Persistable $compare
     * @return bool
     */
    public function equals(Persistable $compare): bool {
        if ( get_class( $this ) !== get_class( $compare ) ) {
            return false;
        }

        if ( $this->__identity !== null ) {
            return $this->__identity == $compare->__identity( null );
        }

        if ( $this->__transient_identity !== null ) {
            return $this->__transient_identity == $compare->__transient_identity( null );
        }

        return $this === $compare;
    }

}
