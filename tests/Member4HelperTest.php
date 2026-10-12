<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../app/helpers/Member4Helper.php';

/**
 * Unit Test tự động cho 3 hàm phân công cho Tạ Văn Tùng:
 *   - validateProductData() (sheet "AdminProductModule"     - 14 case)
 *   - formatSlug()          (sheet "FormatSlugModule"       - 8 case)
 *   - adjustStock()         (sheet "InventoryControlModule" - 8 case)
 */
class Member4HelperTest extends TestCase
{
    // ==========================================================
    // 1. validateProductData() — 14 test case
    // ==========================================================
    public function productValidationProvider(): array
    {
        $valid = ['name' => 'Hoa hồng đỏ', 'price' => 100000, 'stock' => 10, 'sale_price' => null];

        return [
            'UTCID01 - tên rỗng' => [array_merge($valid, ['name' => '']), ['Tên sản phẩm không được để trống.']],
            // UTCID02, 03, 04, 05: file Excel gốc không ghi rõ input (chỉ đánh dấu Boundary),
            // xử lý như các biến thể dữ liệu hợp lệ khác nhau (happy path) khớp với kết quả
            // "Chấp nhận - lưu thành công" đã ghi cho UTCID03/04.
            'UTCID02 - dữ liệu hợp lệ (biến thể 1)' => [$valid, []],
            'UTCID03 - dữ liệu hợp lệ (biến thể 2)' => [array_merge($valid, ['name' => 'Hoa lan trắng']), []],
            'UTCID04 - dữ liệu hợp lệ (biến thể 3)' => [array_merge($valid, ['price' => 250000]), []],
            'UTCID05 - dữ liệu hợp lệ (biến thể 4)' => [array_merge($valid, ['stock' => 20]), []],
            'UTCID06 - giá bán null' => [array_merge($valid, ['price' => null]), ['Giá bán không được để trống.']],
            'UTCID07 - giá bán = 0' => [array_merge($valid, ['price' => 0]), ['Giá bán phải lớn hơn 0.']],
            'UTCID08 - giá bán = 0.01 (boundary nhỏ nhất hợp lệ)' => [array_merge($valid, ['price' => 0.01]), []],
            'UTCID09 - giá bán < 0 (-100)' => [array_merge($valid, ['price' => -100]), ['Giá bán phải lớn hơn 0.']],
            'UTCID10 - tồn kho null' => [array_merge($valid, ['stock' => null]), ['Tồn kho không được để trống.']],
            'UTCID11 - tồn kho = -1' => [array_merge($valid, ['stock' => -1]), ['Tồn kho phải là số nguyên >= 0.']],
            'UTCID12 - tồn kho = 0 (boundary hợp lệ)' => [array_merge($valid, ['stock' => 0]), []],
            'UTCID13 - tồn kho = 1.5 (không phải số nguyên)' => [array_merge($valid, ['stock' => 1.5]), ['Tồn kho phải là số nguyên >= 0.']],
        ];
    }

    /** @dataProvider productValidationProvider */
    public function testValidateProductData(array $data, array $expectedErrors): void
    {
        $this->assertSame($expectedErrors, validateProductData($data));
    }

    /** UTCID14 - xóa sản phẩm đã phát sinh đơn hàng -> Soft Delete */
    public function testDecideProductDelete_SoftDeleteWhenHasOrders(): void
    {
        $result = decideProductDelete(true);
        $this->assertSame('soft_delete', $result['action']);
    }

    public function testDecideProductDelete_HardDeleteWhenNoOrders(): void
    {
        $result = decideProductDelete(false);
        $this->assertSame('hard_delete', $result['action']);
    }

    // ==========================================================
    // 2. formatSlug() — 8 test case (UTCID01 - UTCID07 + UTCID08 dùng ở đây là index 8)
    // ==========================================================
    public function formatSlugProvider(): array
    {
        return [
            'UTCID01 - chuỗi tiếng Việt bình thường' => ['Hoa Hong Do', 'hoa-hong-do'],
            'UTCID02 - có khoảng trắng đầu/cuối' => ['  Hoa Hong  ', 'hoa-hong'],
            'UTCID03 - nhiều khoảng trắng liên tiếp ở giữa' => ['Hoa   Hong', 'hoa-hong'],
            'UTCID04 - chứa ký tự đặc biệt ( ) !' => ['Hoa Hong (Loai 1)!', 'hoa-hong-loai-1'],
            'UTCID05 - chữ hoa/thường xen kẽ, có số' => ['15 Bong hOA', '15-bong-hoa'],
            'UTCID06 - chuỗi rỗng -> trả về chuỗi rỗng' => ['', ''],
            'UTCID08 - ký tự đặc biệt + số + gạch ngang có sẵn' => ['Ao Thun Nu - Size: XL/XXL', 'ao-thun-nu-size-xl-xxl'],
        ];
    }

    /** @dataProvider formatSlugProvider */
    public function testFormatSlug(string $input, string $expected): void
    {
        $this->assertSame($expected, formatSlug($input));
    }

    /** UTCID07 - null -> ném exception với đúng thông điệp */
    public function testFormatSlug_NullThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tên sản phẩm không được null');
        formatSlug(null);
    }

    // ==========================================================
    // 3. adjustStock() — 8 test case (InventoryControlModule)
    // ==========================================================
    public function adjustStockProvider(): array
    {
        return [
            'UTCID01 - product_id không tồn tại' => [null, 5, ['ok' => false, 'newStock' => null, 'message' => 'Sản phẩm không tồn tại.']],
            'UTCID02 - +5 (nhập hàng), tồn kho 10 -> 15' => [10, 5, ['ok' => true, 'newStock' => 15, 'message' => '']],
            'UTCID03 - -5 (bán hàng), tồn kho 10 -> 5' => [10, -5, ['ok' => true, 'newStock' => 5, 'message' => '']],
            'UTCID04 - -10 (bán đúng bằng tồn kho, boundary) -> 0' => [10, -10, ['ok' => true, 'newStock' => 0, 'message' => '']],
            'UTCID05 - -11 (bán vượt tồn kho, không hợp lệ)' => [10, -11, ['ok' => false, 'newStock' => null, 'message' => 'Không đủ hàng tồn kho.']],
            'UTCID06 - 0 (không thay đổi) -> vẫn 10' => [10, 0, ['ok' => true, 'newStock' => 10, 'message' => '']],
            'UTCID08 - hoàn trả hàng +3, tồn kho 10 -> 13' => [10, 3, ['ok' => true, 'newStock' => 13, 'message' => '']],
        ];
    }

    /** @dataProvider adjustStockProvider */
    public function testAdjustStock(?int $currentStock, int $adjustment, array $expected): void
    {
        $this->assertSame($expected, adjustStock($currentStock, $adjustment));
    }

    /** UTCID07 - số lượng điều chỉnh không phải kiểu số ("abc") -> ném exception */
    public function testAdjustStock_NonIntegerAdjustmentThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Số lượng điều chỉnh không hợp lệ');
        adjustStock(10, 'abc');
    }
}
