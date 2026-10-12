<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../app/helpers/Member1Helper.php';

/**
 * Unit Test tự động cho 3 hàm được phân công cho Phạm Quang Văn:
 *   - validatePhone()       (UT_validatePhone       - sheet "validatePhone")
 *   - calcCartSubtotal()    (UT_calcCartSubtotal    - sheet "calcCartSubtotal")
 *   - checkStockAvailable() (UT_checkStockAvailable - sheet "checkStockAvailable")
 *
 * Mỗi test case bên dưới ánh xạ 1-1 với một dòng UTCIDxx trong file Excel
 * "Phạm_Quang_Văn_UnitTest_finalfixed.xlsx" để tiện đối chiếu khi báo cáo.
 */
class Member1HelperTest extends TestCase
{
    // ==========================================================
    // 1. validatePhone() — 7 test case (UTCID01 - UTCID07)
    // ==========================================================
    public static function validatePhoneProvider(): array
    {
        return [
            // UTCID => [phone, expected]
            'UTCID01 - 0 + 9 số (Normal)'                    => ['0912345678', true],
            'UTCID02 - +84 + 9 số (Normal)'                  => ['+84912345678', true],
            'UTCID03 - Không có prefix 0/+84 (Abnormal)'     => ['12345678', false],
            'UTCID04 - 0 + 8 số, boundary tối thiểu'         => ['012345678', true],
            'UTCID05 - 0 + 10 số, boundary tối đa'           => ['01234567890', true],
            'UTCID06 - 0 + 11 số, vượt boundary'             => ['012345678901', false],
            'UTCID07 - Chuỗi rỗng (Abnormal)'                => ['', false],
        ];
    }

    /** @dataProvider validatePhoneProvider */
    public function testValidatePhone(string $phone, bool $expected): void
    {
        $this->assertSame($expected, validatePhone($phone));
    }

    // ==========================================================
    // 2. calcCartSubtotal() — 6 test case (UTCID01 - UTCID06)
    // ==========================================================
    public static function calcCartSubtotalProvider(): array
    {
        return [
            'UTCID01 - Giỏ hàng rỗng' => [
                [], 0.0
            ],
            'UTCID02 - 1 sp, sale_price=null, price=100000, qty=2' => [
                [['sale_price' => null, 'price' => 100000, 'quantity' => 2]], 200000.0
            ],
            'UTCID03 - 1 sp, sale_price=80000 < price=100000, qty=2' => [
                [['sale_price' => 80000, 'price' => 100000, 'quantity' => 2]], 160000.0
            ],
            'UTCID04 - 1 sp, sale_price=0 (falsy, fallback price), price=100000, qty=1' => [
                [['sale_price' => 0, 'price' => 100000, 'quantity' => 1]], 100000.0
            ],
            'UTCID05 - 2 sp khác nhau (100000x2 + 150000x1)' => [
                [
                    ['sale_price' => null, 'price' => 100000, 'quantity' => 2],
                    ['sale_price' => null, 'price' => 150000, 'quantity' => 1],
                ], 350000.0
            ],
            'UTCID06 - 1 sp, quantity=0' => [
                [['sale_price' => null, 'price' => 100000, 'quantity' => 0]], 0.0
            ],
        ];
    }

    /** @dataProvider calcCartSubtotalProvider */
    public function testCalcCartSubtotal(array $items, float $expected): void
    {
        $this->assertSame($expected, calcCartSubtotal($items));
    }

    // ==========================================================
    // 3. checkStockAvailable() — 6 test case (UTCID01 - UTCID06)
    // ==========================================================
    public static function checkStockAvailableProvider(): array
    {
        return [
            'UTCID01 - stock=10, existing=0, add=5 (Normal, ok)' => [
                10, 0, 5, true, ''
            ],
            'UTCID02 - stock=5, existing=0, add=10 (vượt tồn kho)' => [
                5, 0, 10, false, 'Chỉ còn 5 sản phẩm trong kho.'
            ],
            'UTCID03 - stock=5, existing=3, add=4 (3+4=7 > 5)' => [
                5, 3, 4, false, 'Giỏ hàng đã có 3 sản phẩm. Tồn kho chỉ còn 5.'
            ],
            'UTCID04 - stock=5, existing=3, add=2 (3+2=5, boundary bằng)' => [
                5, 3, 2, true, ''
            ],
            'UTCID05 - stock=0, existing=0, add=1' => [
                0, 0, 1, false, 'Chỉ còn 0 sản phẩm trong kho.'
            ],
            'UTCID06 - stock=5, existing=0, add=5 (boundary đúng bằng stock)' => [
                5, 0, 5, true, ''
            ],
        ];
    }

    /** @dataProvider checkStockAvailableProvider */
    public function testCheckStockAvailable(int $stock, int $existingQty, int $addQty, bool $expectedOk, string $expectedMessage): void
    {
        $result = checkStockAvailable($stock, $existingQty, $addQty);
        $this->assertSame($expectedOk, $result['ok']);
        if ($expectedOk) {
            $this->assertSame('', $result['message']);
        } else {
            $this->assertSame($expectedMessage, $result['message']);
        }
    }
}
