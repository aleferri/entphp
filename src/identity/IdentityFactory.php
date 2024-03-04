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

namespace entphp\identity;

use basin\concepts\Persistable;
use basin\concepts\Identity;

/**
 *
 * @author Alessio
 */
interface IdentityFactory {

    /**
     * Get next identity
     * @return Identity
     */
    public function provide_transient(Persistable $persistable): Identity;

    /**
     *
     * @param Persistable $persistable
     * @param Identity $identity
     * @return array
     */
    public function patch_identity(Persistable $persistable, Identity $identity): array;

    /**
     * Patch transient data
     * @param array $data
     * @return array
     */
    public function patch_transients(array $data): array;

    /**
     *
     * @param string $classname
     * @return Identity
     */
    public function empty_identity(string $classname): Identity;

}
