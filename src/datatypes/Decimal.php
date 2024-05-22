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

namespace entphp\datatypes;

/**
 * Description of Decimal
 *
 * @author Alessio
 */
class Decimal {

    public function __construct(private string $value, private int $scale = 2) {

    }

    private function operand(int|float|string|Decimal $operand, int $scale): array {
        $value = $operand;
        if ( $operand instanceof Decimal ) {
            $value = $operand->value;

            if ( $scale === null ) {
                $scale = max( $operand->scale, $this->scale );
            }
        } else {
            if ( $scale === null ) {
                $scale = $this->scale;
            }

            if ( is_float( $value ) || is_int( $value ) ) {
                $value = number_format( $operand, $scale, '.', '' );
            }
        }

        return [ $value, $scale ];
    }

    /**
     * Add another number, the result keeps the maximum precision between the operand if not explicitly specified
     * @param int|float|string|Decimal $operand
     * @param int|null $scale
     * @return Decimal
     */
    public function add(int|float|string|Decimal $operand, ?int $scale = null): Decimal {
        [ $value, $target_scale ] = $this->operand( $operand, $scale );

        return new Decimal( bcadd( $this->value, $value, $target_scale ), $target_scale );
    }

    /**
     * Sub another number, the result keeps the maximum precision between the operand if not explicitly specified
     * @param int|float|string|Decimal $operand
     * @param int|null $scale
     * @return Decimal
     */
    public function sub(int|float|string|Decimal $operand, ?int $scale = null): Decimal {
        [ $value, $target_scale ] = $this->operand( $operand, $scale );

        return new Decimal( bcsub( $this->value, $value, $target_scale ), $target_scale );
    }

    /**
     * Mul another number, the result keeps the maximum precision between the operand if not explicitly specified
     * @param int|float|string|Decimal $operand
     * @param int|null $scale
     * @return Decimal
     */
    public function mul(int|float|string|Decimal $operand, ?int $scale = null): Decimal {
        [ $value, $target_scale ] = $this->operand( $operand, $scale );

        return new Decimal( bcmul( $this->value, $value, $target_scale ), $target_scale );
    }

    /**
     * Div another number, the result keeps the maximum precision between the operand if not explicitly specified
     * @param int|float|string|Decimal $operand
     * @param int|null $scale
     * @return Decimal
     */
    public function div(int|float|string|Decimal $operand, ?int $scale = null): Decimal {
        [ $value, $target_scale ] = $this->operand( $operand, $scale );

        return new Decimal( bcdiv( $this->value, $value, $target_scale ), $target_scale );
    }

    /**
     * Divide this amount in the requested partitions, with the last having more weights
     * @param int $partitions
     * @param int|null $scale
     * @return array
     */
    public function distribute(int $partitions, ?int $scale = null): array {
        if ( $partitions < 1 ) {
            return [];
        }

        if ( $scale === null ) {
            $scale = $this->scale;
        }

        $left       = new Decimal( $this->value, $scale );
        $base_share = $left->div( $partitions );
        $partitions --;

        $shares = [];

        while ( $partitions > 0 ) {
            $shares[] = $base_share;
            $left     = $left->sub( $base_share );
            $partitions --;
        }
        $shares[] = $left;

        return $shares;
    }

    public function value(): string {
        return $this->value;
    }

    public function scale(): int {
        return $this->scale;
    }

}
