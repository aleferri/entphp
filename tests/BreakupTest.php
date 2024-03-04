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

use PHPUnit\Framework\TestCase;
use entphp\serde\ObjectSerializer;

require_once 'Contact.php';
require_once 'Address.php';
require_once 'Person.php';

/**
 * Description of BreakupTest
 *
 * @author Alessio
 */
class BreakupTest extends TestCase {

    private function get_pdo_sqlite() {
        $pdo = new PDO(
            'sqlite:' . __DIR__ . '/testdb.sqlite', null, null,
            [
            \PDO::ATTR_PERSISTENT        => true,
            \PDO::ATTR_EMULATE_PREPARES  => false,
            \PDO::ATTR_STRINGIFY_FETCHES => false
            ]
        );

        return $pdo;
    }

    private function execute_query($pdo, \entphp\query\SQLFetchQuery $query) {
        $sql = $query->to_sql();

        $st = $pdo->prepare( $sql );

        $i = 1;
        foreach ( $query->values() as $value ) {
            $st->bindValue( $i, $value );

            $i ++;
        }

        $st->execute();
        $records = $st->fetchAll();

        return $records;
    }

    public function testBreakupPersisted() {
        $pdo = $this->get_pdo_sqlite();

        $query = \entphp\query\SQLFetchQueryBuilder::start()
            ->select( 'p.*' )
            ->from( 'people', 'p' )
            ->filter_by( 'per_row_main', 'person_id = 1' )
            ->into_query();

        $planner = new \entphp\query\SQLFetchPlanner( $pdo );
        $people = $planner->fetch_all( Person::class, $query );

        $this->assertEquals( 1, count( $people ) );

        foreach ( $people as $person ) {
            $this->assertEquals( Person::class, get_class( $person ) );
            $this->assertEquals( 4, count( $person->get_contacts() ) );

            foreach ( $person->get_contacts() as $contact ) {
                $this->assertEquals( Contact::class, get_class( $contact ) );
            }
        }

        $id_factory = new entphp\identity\IdentityFactorySequential( 'sql' );

        $serializer = ObjectSerializer::of_class( Person::class, 'sql', $id_factory );
        $breaked = $serializer->breakup_all( $people );

        $this->assertTrue( isset( $breaked[ 'people' ] ) );
        $this->assertTrue( isset( $breaked[ 'contacts' ] ) );
    }

    public function testBreakupTransients() {
        $person = new Person( null, 'A', 'B', new \DateTimeImmutable(), '', new Address() );
        $person->add_contact( new Contact( null, 'home' ) );
        $person->add_contact( new Contact( null, 'office' ) );
        $person->add_contact( new Contact( null, 'personal' ) );

        $second = new Person( null, 'B', 'C', new \DateTimeImmutable() );
        $second->add_contact( new Contact( null, 'home' ) );
        $second->add_contact( new Contact( null, 'spouse' ) );

        $people = [ $person, $second ];

        $id_factory = new entphp\identity\IdentityFactorySequential( 'sql' );

        $serializer = ObjectSerializer::of_class( Person::class, 'sql', $id_factory );
        $breaked = $serializer->breakup_all( $people );

        $this->assertTrue( isset( $breaked[ 'people' ] ) );
        $this->assertTrue( isset( $breaked[ 'contacts' ] ) );
        $this->assertEquals( 2, count( $breaked[ 'people' ] ) );
        $this->assertEquals( 5, count( $breaked[ 'contacts' ] ) );
    }

    public function testBreakupTransientsEmulate() {
        $person = new Person( null, 'A', 'B', new \DateTimeImmutable() );
        $person->add_contact( new Contact( null, 'home' ) );
        $person->add_contact( new Contact( null, 'office' ) );
        $person->add_contact( new Contact( null, 'personal' ) );

        $second = new Person( null, 'A', 'B', new \DateTimeImmutable() );
        $second->add_contact( new Contact( null, 'home' ) );
        $second->add_contact( new Contact( null, 'spouse' ) );

        $people = [ $person, $second ];

        $db_test = new entphp\drivers\TestDriver();
        $id_factory = new entphp\identity\IdentityFactorySequential( 'sql' );

        $serializer = ObjectSerializer::of_class( Person::class, 'sql', $id_factory );
        $breaked = $serializer->breakup_all( $people );

        foreach ( $breaked as $source => $records ) {
            foreach ( $records as &$record ) {
                if ( isset( $record[ '__identity' ] ) && $record[ '__identity' ] instanceof \entphp\identity\TransientIdentity ) {
                    $id = $db_test->next_key( $source );
                }
            }
            unset( $record );
        }
    }

}
