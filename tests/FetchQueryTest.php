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

require "Contact.php";

/**
 * Description of FetchQueryTest
 *
 * @author Alessio
 */
class FetchQueryTest extends TestCase {

    private function get_flat_builder_manual() {
        $flat_builder = new entphp\datatypes\FlatBuilder(
                Contact::class,
                [
                'id'                => [ 'field' => 'id', 'parse' => 'entphp\\datatypes\\to_int' ],
                'name'              => 'name',
                'primary_phone'     => 'primary_phone',
                'primary_email'     => 'primary_email',
                'secondary_email'   => 'secondary_email',
                'first_name'        => 'first_name',
                'last_name'         => 'last_name',
                'address_formatted' => 'address_formatted'
                ]
        );

        return $flat_builder;
    }

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

        $flat_builder = $this->get_flat_builder_manual();

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

        $flat_builder = entphp\datatypes\FlatBuilder::of_class( Contact::class, 'sql' );

        $contacts = $flat_builder->instance_all( $records );

        $this->assertEquals( 3, count( $contacts ) );

        foreach ( $contacts as $contact ) {
            $this->assertEquals( Contact::class, get_class( $contact ) );
        }
    }
}
