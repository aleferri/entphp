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

use PHPUnit\Framework\TestCase;

require 'Contact.php';
require 'Person.php';

use entphp\datatypes\FlatBuilder;

/**
 * Description of FetchQueryTest
 *
 * @author Alessio
 */
class FetchQueryTest extends TestCase {

    private function get_pdo_sqlite() {
        $pdo = new PDO(
                'sqlite:' . __DIR__ . '/testdb.sqlite',
                null,
                null,
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

            $i++;
        }

        $st->execute();
        $records = $st->fetchAll();

        return $records;
    }

    public function testReadTablePrimitiveManual() {
        $pdo = $this->get_pdo_sqlite();

        $query = \entphp\query\SQLFetchQueryBuilder::start()
                ->select( "cts.*" )
                ->from( 'contacts', 'cts' )
                ->filter_by( 'per_row_main', 'primary_email LIKE ?', '%outlook.com' )
                ->into_query();

        $records = $this->execute_query( $pdo, $query );

        $this->assertEquals( 3, count( $records ) );

        $flat_builder = FlatBuilder::of_class( Contact::class, 'sql' );

        $contacts = $flat_builder->instance_all( $records );

        $this->assertEquals( 3, count( $contacts ) );

        foreach ( $contacts as $contact ) {
            $this->assertEquals( Contact::class, get_class( $contact ) );
        }
    }

    public function testReadTablePrimitiveDerived() {
        $pdo = $this->get_pdo_sqlite();

        $query = \entphp\query\SQLFetchQueryBuilder::start()
                ->select( "cts.*" )
                ->from( 'contacts', 'cts' )
                ->filter_by( 'per_row_main', 'primary_email LIKE ?', '%outlook.com' )
                ->into_query();

        $records = $this->execute_query( $pdo, $query );

        $this->assertEquals( 3, count( $records ) );

        $flat_builder = FlatBuilder::of_class( Contact::class, 'sql' );

        $contacts = $flat_builder->instance_all( $records );

        $this->assertEquals( 3, count( $contacts ) );

        foreach ( $contacts as $contact ) {
            $this->assertEquals( Contact::class, get_class( $contact ) );
        }
    }

    public function testFetchDerivedValues() {
        $pdo = $this->get_pdo_sqlite();

        $fetch_node = \entphp\query\SQLFetchNode::of_class( Contact::class );

        $query = $fetch_node->query_for( [ 'person_id' => [ 1, 2, 3 ] ] )->into_query();

        $records = $this->execute_query( $pdo, $query );

        $this->assertEquals( 7, count( $records ) );

        $contacts = $fetch_node->builder()->instance_all( $records );

        $this->assertEquals( 7, count( $contacts ) );

        foreach ( $contacts as $contact ) {
            $this->assertEquals( Contact::class, get_class( $contact ) );
        }
    }

    public function testReadTableArrayDerived() {
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
        }
    }
}
