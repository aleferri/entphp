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

/**
 * Description of TransactionTracker
 *
 * @author Alessio
 */
interface TransactionTracker {

    /**
     * Track the object and derive it's current identity or provide a transient one to fix up later
     * @param object|null $object
     * @param string $classname
     * @return Identity
     */
    public function track_object(?object $object, string $classname = ''): Identity;

    /**
     * Track the patches required when the objects is saved
     * @param array $record
     * @return array
     */
    public function track_patches(array $record): array;

    /**
     * Flush the transaction and sync the persisted identity with saved objects
     * @return void
     */
    public function flush(): void;

}
