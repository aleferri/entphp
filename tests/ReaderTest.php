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

require "Point.php";

/**
 * Description of ReaderTest
 *
 * @author Alessio
 */
class ReaderTest extends TestCase {

    public function testEmpty() {
        $this->assertEquals( true, true );
    }

    /*
      public function testReadTableClass() {
      $pdo = new PDO (
      'sqlite::memory:',
      null,
      null,
      [
      \PDO::ATTR_PERSISTENT => true,
      \PDO::ATTR_EMULATE_PREPARES => false,
      \PDO::ATTR_STRINGIFY_FETCHES => false
      ]
      );
      $pdo->query ( "CREATE TABLE test_points(point_id LONG PRIMARY KEY NOT NULL, coord_x LONG NOT NULL, coord_y LONG NOT NULL)" );
      $st = $pdo->prepare ( "INSERT INTO test_points VALUES (?, ?, ?)" );
      $st->bindValue ( 1, 1 );
      $st->bindValue ( 2, 1 );
      $st->bindValue ( 3, 1 );
      $st->execute ();

      $reader = new \alessio\entphp\ReaderSQL ( $pdo, new alessio\entphp\drivers\SQLiteDriver ( $pdo ) );
      $point = $reader->fetch_by_primary ( Point::class, 1 );

      $this->assertInstanceOf ( Point::class, $point, $point::class );
      $this->assertEquals ( $point->x, 1 );
      } */
}
