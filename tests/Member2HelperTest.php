<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../app/helpers/Member2Helper.php';

/**
 * Unit Test tự động cho 3 hàm phân công cho Lê Xuân Huynh:
 *   - isValid()           (sheet "CouponValidation" - 10 case)
 *   - calcDiscount()      (sheet "CouponDiscount"    - 9 case)
 *   - createOrderSummary()(sheet "OrderSummary"      - 7 case, bản pure của createFromCart)
 */
class Member2HelperTest extends TestCase
{
    private function makeCoupon(array $overrides = []): array
    {
        return array_merge([
            'status'        => 'active',
            'min_order'     => 0,
            'expires_at'    => date('Y-m-d H:i:s', strtotime('+1 day')),
            'max_uses'      => null,
            'used_count'    => 0,
            'discount_type' => 'percent',
            'discount_value'=> 10,
        ], $overrides);
    }

    // ==========================================================
    // 1. isValid() — 10 test case
    // ==========================================================
    public function isValidProvider(): array
    {
        $future = date('Y-m-d H:i:s', strtotime('+1 day'));
        $past   = date('Y-m-d H:i:s', strtotime('-1 day'));

        return [
            'UTCID01 - active, total>min, còn hạn, used<max' => [
                $this->makeCoupon(['min_order' => 100000, 'expires_at' => $future, 'max_uses' => 5, 'used_count' => 1]),
                200000, true
            ],
            'UTCID02 - inactive' => [
                $this->makeCoupon(['status' => 'inactive', 'min_order' => 100000, 'expires_at' => $future, 'max_uses' => 5, 'used_count' => 1]),
                200000, false
            ],
            'UTCID03 - total < min_order' => [
                $this->makeCoupon(['min_order' => 300000, 'expires_at' => $future, 'max_uses' => 5, 'used_count' => 1]),
                200000, false
            ],
            'UTCID04 - total = min_order (boundary)' => [
                $this->makeCoupon(['min_order' => 200000, 'expires_at' => $future, 'max_uses' => 5, 'used_count' => 1]),
                200000, true
            ],
            'UTCID05 - min_order=0 (không yêu cầu)' => [
                $this->makeCoupon(['min_order' => 0, 'expires_at' => $future, 'max_uses' => 5, 'used_count' => 1]),
                50000, true
            ],
            'UTCID06 - hết hạn (quá khứ)' => [
                $this->makeCoupon(['min_order' => 100000, 'expires_at' => $past, 'max_uses' => 5, 'used_count' => 1]),
                200000, false
            ],
            'UTCID07 - expires_at=null (không giới hạn)' => [
                $this->makeCoupon(['min_order' => 100000, 'expires_at' => null, 'max_uses' => 5, 'used_count' => 1]),
                200000, true
            ],
            'UTCID08 - used_count = max_uses (boundary)' => [
                $this->makeCoupon(['min_order' => 100000, 'expires_at' => $future, 'max_uses' => 5, 'used_count' => 5]),
                200000, false
            ],
            'UTCID09 - used_count > max_uses' => [
                $this->makeCoupon(['min_order' => 100000, 'expires_at' => $future, 'max_uses' => 5, 'used_count' => 6]),
                200000, false
            ],
            'UTCID10 - max_uses=null (không giới hạn)' => [
                $this->makeCoupon(['min_order' => 100000, 'expires_at' => $future, 'max_uses' => null, 'used_count' => 999]),
                200000, true
            ],
        ];
    }

    /** @dataProvider isValidProvider */
    public function testIsValid(array $coupon, float $orderTotal, bool $expected): void
    {
        $this->assertSame($expected, isValid($coupon, $orderTotal));
    }

    // ==========================================================
    // 2. calcDiscount() — 9 test case
    // ==========================================================
    public function calcDiscountProvider(): array
    {
        return [
            'UTCID01 - percent 10%, total=1,000,000' => [
                $this->makeCoupon(['discount_type' => 'percent', 'discount_value' => 10]), 1000000, 100000.0
            ],
            'UTCID02 - percent 100% (boundary), total=300,000' => [
                $this->makeCoupon(['discount_type' => 'percent', 'discount_value' => 100]), 300000, 300000.0
            ],
            'UTCID03 - percent 0% (boundary), total=300,000' => [
                $this->makeCoupon(['discount_type' => 'percent', 'discount_value' => 0]), 300000, 0.0
            ],
            'UTCID04 - fixed 50,000 < total=200,000' => [
                $this->makeCoupon(['discount_type' => 'fixed', 'discount_value' => 50000]), 200000, 50000.0
            ],
            'UTCID05 - fixed 500,000 > total=200,000 (boundary, cap tại total)' => [
                $this->makeCoupon(['discount_type' => 'fixed', 'discount_value' => 500000]), 200000, 200000.0
            ],
            'UTCID06 - fixed 200,000 = total=200,000 (boundary)' => [
                $this->makeCoupon(['discount_type' => 'fixed', 'discount_value' => 200000]), 200000, 200000.0
            ],
            'UTCID07 - fixed 0 (boundary)' => [
                $this->makeCoupon(['discount_type' => 'fixed', 'discount_value' => 0]), 200000, 0.0
            ],
            'UTCID08 - percent 10%, total=0 (boundary)' => [
                $this->makeCoupon(['discount_type' => 'percent', 'discount_value' => 10]), 0, 0.0
            ],
            'UTCID09 - discount_type khác/không hợp lệ -> xử lý như fixed' => [
                $this->makeCoupon(['discount_type' => 'khác', 'discount_value' => 50000]), 200000, 50000.0
            ],
        ];
    }

    /** @dataProvider calcDiscountProvider */
    public function testCalcDiscount(array $coupon, float $orderTotal, float $expected): void
    {
        $this->assertSame($expected, calcDiscount($coupon, $orderTotal));
    }

    // ==========================================================
    // 3. createOrderSummary() — 7 test case (bản pure của createFromCart)
    // ==========================================================
    public function orderSummaryProvider(): array
    {
        $validPercentCoupon = $this->makeCoupon(['discount_type' => 'percent', 'discount_value' => 10]);
        $expiredCoupon      = $this->makeCoupon(['status' => 'inactive']);

        return [
            'UTCID01 - giỏ hàng rỗng -> throw' => [
                [], null, 'exception'
            ],
            'UTCID02 - 1 sp, qty=2, giá=100,000, không coupon' => [
                [['sale_price' => null, 'price' => 100000, 'quantity' => 2]], null,
                ['subtotal' => 200000.0, 'discount' => 0.0, 'total' => 200000.0]
            ],
            'UTCID03 - nhiều sp, coupon percent 10% hợp lệ' => [
                [
                    ['sale_price' => null, 'price' => 600000, 'quantity' => 1],
                    ['sale_price' => null, 'price' => 400000, 'quantity' => 1],
                ],
                $validPercentCoupon,
                ['subtotal' => 1000000.0, 'discount' => 100000.0, 'total' => 900000.0]
            ],
            'UTCID04 - nhiều sp, coupon không hợp lệ/hết hạn -> discount=0' => [
                [
                    ['sale_price' => null, 'price' => 300000, 'quantity' => 1],
                    ['sale_price' => null, 'price' => 200000, 'quantity' => 1],
                ],
                $expiredCoupon,
                ['subtotal' => 500000.0, 'discount' => 0.0, 'total' => 500000.0]
            ],
            'UTCID05 - 1 sp sale_price=80,000 (giá gốc 100,000), không coupon' => [
                [['sale_price' => 80000, 'price' => 100000, 'quantity' => 1]], null,
                ['subtotal' => 80000.0, 'discount' => 0.0, 'total' => 80000.0]
            ],
            'UTCID06 - coupon hợp lệ, discount = subtotal (boundary) -> total=0' => [
                [['sale_price' => null, 'price' => 200000, 'quantity' => 1]],
                $this->makeCoupon(['discount_type' => 'fixed', 'discount_value' => 200000]),
                ['subtotal' => 200000.0, 'discount' => 200000.0, 'total' => 0.0]
            ],
            'UTCID07 - 1 sp, qty=1 (boundary), không coupon' => [
                [['sale_price' => null, 'price' => 200000, 'quantity' => 1]], null,
                ['subtotal' => 200000.0, 'discount' => 0.0, 'total' => 200000.0]
            ],
        ];
    }

    /** @dataProvider orderSummaryProvider */
    public function testCreateOrderSummary(array $items, ?array $coupon, $expected): void
    {
        if ($expected === 'exception') {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Giỏ hàng trống.');
            createOrderSummary($items, $coupon);
            return;
        }

        $result = createOrderSummary($items, $coupon);
        $this->assertSame($expected['subtotal'], $result['subtotal']);
        $this->assertSame($expected['discount'], $result['discount']);
        $this->assertSame($expected['total'], $result['total']);
    }
}
