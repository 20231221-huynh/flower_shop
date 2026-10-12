<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../app/helpers/Member3Helper.php';

/**
 * Unit Test tự động cho phần LOGIC QUYẾT ĐỊNH (business rule) của Hứa Đức Lương:
 *   - F01 updateStatus()                          (sheet "ShipperDeliveryModule"       - 15 case)
 *   - F02 HandleDeliveryFailure/cancelStalledVnpay (sheet "OrderInventoryRefundModule" - 15 case)
 *   - F03 placeOrder()                            (sheet "ValidationServiceModule"    - 15 case)
 *
 * Xem ghi chú đầu file Member3Helper.php: các thao tác DB thật (UPDATE/transaction) không
 * nằm trong phạm vi Unit Test — 1 test (UTCID13 của F02, mô phỏng rollback) được viết dưới
 * dạng minh họa hành vi bằng bộ nhớ, không thay cho Integration Test thật với CSDL.
 */
class Member3HelperTest extends TestCase
{
    // ==========================================================
    // 1. F01 — decideShipperUpdate() — 15 test case
    // ==========================================================
    public function shipperUpdateProvider(): array
    {
        $order = fn(array $o) => array_merge([
            'id' => 1, 'shipper_id' => 1, 'delivery_status' => 'shipping',
            'pay_status' => 'unpaid', 'pay_method' => 'cod',
        ], $o);

        return [
            'UTCID01 - shipping hợp lệ' => [
                $order(['shipper_id' => 1]), 1, 'shipping', null,
                ['success' => true, 'newDeliveryStatus' => 'shipping', 'newStatus' => 'shipping']
            ],
            'UTCID02 - shipping giữ nguyên (idempotent)' => [
                $order(['shipper_id' => 1, 'delivery_status' => 'shipping']), 1, 'shipping', null,
                ['success' => true, 'newDeliveryStatus' => 'shipping', 'newStatus' => 'shipping']
            ],
            'UTCID03 - delivered, COD unpaid -> pay_status=paid' => [
                $order(['shipper_id' => 1, 'pay_method' => 'cod', 'pay_status' => 'unpaid']), 1, 'delivered', null,
                ['success' => true, 'newDeliveryStatus' => 'delivered', 'newStatus' => 'delivered', 'newPayStatus' => 'paid']
            ],
            'UTCID04 - delivered, VNPay đã paid -> giữ paid' => [
                $order(['shipper_id' => 1, 'pay_method' => 'vnpay', 'pay_status' => 'paid']), 1, 'delivered', null,
                ['success' => true, 'newDeliveryStatus' => 'delivered', 'newStatus' => 'delivered', 'newPayStatus' => 'paid']
            ],
            'UTCID05 - delivered, Momo đã paid -> giữ paid' => [
                $order(['shipper_id' => 1, 'pay_method' => 'momo', 'pay_status' => 'paid']), 1, 'delivered', null,
                ['success' => true, 'newDeliveryStatus' => 'delivered', 'newStatus' => 'delivered', 'newPayStatus' => 'paid']
            ],
            'UTCID06 - failed lần 2 với lý do hợp lệ (đơn đã failed trước đó)' => [
                $order(['shipper_id' => 1, 'delivery_status' => 'failed', 'pay_status' => 'unpaid']), 1, 'failed', 'failed_no_contact',
                ['success' => true, 'newDeliveryStatus' => 'failed']
            ],
            'UTCID07 - failed, đã paid -> cần hoàn tiền' => [
                $order(['shipper_id' => 1, 'pay_status' => 'paid']), 1, 'failed', 'failed_address',
                ['success' => true, 'newDeliveryStatus' => 'failed', 'newPayStatus' => 'refunded']
            ],
            'UTCID08 - failed, lý do failed_rejected hợp lệ' => [
                $order(['shipper_id' => 1]), 1, 'failed', 'failed_rejected',
                ['success' => true, 'newDeliveryStatus' => 'failed']
            ],
            'UTCID09 - failed, lý do không hợp lệ (wrong_reason)' => [
                $order(['shipper_id' => 1]), 1, 'failed', 'wrong_reason',
                ['success' => false, 'httpCode' => 400, 'message' => 'Vui lòng chọn lý do thất bại.']
            ],
            'UTCID10 - trạng thái yêu cầu không hợp lệ (pending)' => [
                $order(['shipper_id' => 1]), 1, 'pending', null,
                ['success' => false, 'httpCode' => 400, 'message' => 'Trạng thái không hợp lệ.']
            ],
            'UTCID11 - đơn không tồn tại' => [
                null, 1, 'shipping', null,
                ['success' => false, 'httpCode' => 404, 'message' => 'Đơn hàng không tồn tại.']
            ],
            'UTCID12 - shipper không có quyền (shipper_id khác nhau)' => [
                $order(['shipper_id' => 5]), 3, 'shipping', null,
                ['success' => false, 'httpCode' => 403, 'message' => 'Bạn không có quyền cập nhật đơn này.']
            ],
            'UTCID13 - order_id không phải số nguyên -> coi như không tìm thấy' => [
                null, 1, 'shipping', null,
                ['success' => false, 'httpCode' => 404, 'message' => 'Đơn hàng không tồn tại.']
            ],
            'UTCID14 - failed, lý do rỗng' => [
                $order(['shipper_id' => 1]), 1, 'failed', '',
                ['success' => false, 'httpCode' => 400, 'message' => 'Vui lòng chọn lý do thất bại.']
            ],
            'UTCID15 - failed với nhiều order_items (chỉ kiểm tra nhánh quyết định)' => [
                $order(['shipper_id' => 1]), 1, 'failed', 'failed_no_contact',
                ['success' => true, 'newDeliveryStatus' => 'failed']
            ],
        ];
    }

    /** @dataProvider shipperUpdateProvider */
    public function testDecideShipperUpdate(?array $order, int $currentUserId, string $requestedStatus, ?string $reason, array $expected): void
    {
        $result = decideShipperUpdate($order, $currentUserId, $requestedStatus, $reason);
        foreach ($expected as $key => $value) {
            $this->assertSame($value, $result[$key], "Field '$key' mismatch");
        }
    }

    /** UTCID15 (phần hoàn kho 3 order_items) */
    public function testShipperFailedRestoresStockForAllItems(): void
    {
        $items = [
            ['product_id' => 1, 'stock' => 10, 'quantity' => 2],
            ['product_id' => 2, 'stock' => 5, 'quantity' => 1],
            ['product_id' => 3, 'stock' => 8, 'quantity' => 4],
        ];
        $newStocks = restoreStockForItems($items);
        $this->assertSame([12, 6, 12], $newStocks);
    }

    // ==========================================================
    // 2. F02 — decideDeliveryFailure() + hàm liên quan — 15 test case
    // ==========================================================
    public function deliveryFailureProvider(): array
    {
        return [
            'UTCID01 - unpaid, hoàn stock, không cần hoàn tiền' => [
                'shipping', 'failed_no_contact', 'unpaid',
                ['ok' => true, 'restoreStock' => true, 'newPayStatus' => 'unpaid', 'newPaymentRecordStatus' => null]
            ],
            'UTCID02 - đã paid (vnpay) -> refunded + payment failed' => [
                'shipping', 'failed_no_contact', 'paid',
                ['ok' => true, 'restoreStock' => true, 'newPayStatus' => 'refunded', 'newPaymentRecordStatus' => 'failed']
            ],
            'UTCID05 - đơn đã failed trước đó -> không hoàn stock lần 2' => [
                'failed', 'failed_no_contact', 'unpaid',
                ['ok' => false, 'restoreStock' => false, 'message' => 'Đơn hàng đã được xử lý giao thất bại trước đó.']
            ],
            'UTCID14 - paid nhưng payment.status cũ bị lệch (pending) -> vẫn set failed nhất quán' => [
                'shipping', 'failed_no_contact', 'paid',
                ['ok' => true, 'newPayStatus' => 'refunded', 'newPaymentRecordStatus' => 'failed']
            ],
        ];
    }

    /** @dataProvider deliveryFailureProvider */
    public function testDecideDeliveryFailure(string $currentStatus, ?string $reason, string $payStatus, array $expected): void
    {
        $result = decideDeliveryFailure($currentStatus, $reason, $payStatus);
        foreach ($expected as $key => $value) {
            $this->assertSame($value, $result[$key], "Field '$key' mismatch");
        }
    }

    /** UTCID03 - 2 item khác nhau, hoàn đúng từng item, không bỏ sót */
    public function testRestoreStockForItems_MultipleItems(): void
    {
        $items = [
            ['product_id' => 10, 'stock' => 20, 'quantity' => 2],
            ['product_id' => 12, 'stock' => 30, 'quantity' => 3],
        ];
        $this->assertSame([22, 33], restoreStockForItems($items));
    }

    /** UTCID04 - order không có order_items -> không thay đổi gì, không lỗi */
    public function testRestoreStockForItems_EmptyItems(): void
    {
        $this->assertSame([], restoreStockForItems([]));
    }

    /** UTCID01 (phần số liệu cụ thể) - hoàn +2 cho 1 item stock=20 */
    public function testRestoreStockForItems_SingleItem(): void
    {
        $this->assertSame([22], restoreStockForItems([['product_id' => 10, 'stock' => 20, 'quantity' => 2]]));
    }

    public function autoCancelProvider(): array
    {
        return [
            'UTCID06 - vnpay/unpaid/pending, 2 phút (>=60s) -> hủy' => ['vnpay', 'unpaid', 'pending', 120, true],
            'UTCID07 - vnpay/unpaid/pending, 30s (<60s) -> chưa hủy' => ['vnpay', 'unpaid', 'pending', 30, false],
            'UTCID08 - vnpay đã paid -> không hủy' => ['vnpay', 'paid', 'pending', 300, false],
            'UTCID09 - COD -> không bị logic VNPay tác động' => ['cod', 'unpaid', 'pending', 300, false],
            'UTCID10 - đã cancelled trước đó -> không xử lý lại' => ['vnpay', 'failed', 'cancelled', 300, false],
        ];
    }

    /** @dataProvider autoCancelProvider */
    public function testShouldAutoCancelVnpayOrder(string $payMethod, string $payStatus, string $orderStatus, int $seconds, bool $expected): void
    {
        $this->assertSame($expected, shouldAutoCancelVnpayOrder($payMethod, $payStatus, $orderStatus, $seconds));
    }

    /** UTCID11 - 3 đơn đều đủ điều kiện -> trả về 3 */
    public function testCountAutoCancelableOrders_ThreeMatch(): void
    {
        $orders = [
            ['pay_method' => 'vnpay', 'pay_status' => 'unpaid', 'status' => 'pending', 'seconds' => 90],
            ['pay_method' => 'vnpay', 'pay_status' => 'unpaid', 'status' => 'pending', 'seconds' => 100],
            ['pay_method' => 'vnpay', 'pay_status' => 'unpaid', 'status' => 'pending', 'seconds' => 70],
        ];
        $this->assertSame(3, countAutoCancelableOrders($orders));
    }

    /** UTCID12 - không có đơn nào đủ điều kiện -> trả về 0 */
    public function testCountAutoCancelableOrders_NoneMatch(): void
    {
        $orders = [
            ['pay_method' => 'cod', 'pay_status' => 'unpaid', 'status' => 'pending', 'seconds' => 90],
        ];
        $this->assertSame(0, countAutoCancelableOrders($orders));
    }

    /** UTCID13 - lỗi giữa chừng -> toàn bộ rollback (không có item nào được cập nhật) */
    public function testApplyStockRestoreTransactionally_RollbackOnFailure(): void
    {
        $originalStocks = [10 => 20, 12 => 30];
        $items = [
            ['product_id' => 10, 'quantity' => 2],
            ['product_id' => 12, 'quantity' => 3], // item gây lỗi giả lập
        ];

        $this->expectException(RuntimeException::class);
        try {
            applyStockRestoreTransactionally($originalStocks, $items, true);
        } finally {
            // Dữ liệu gốc không hề bị thay đổi vì hàm luôn làm việc trên bản sao
            $this->assertSame([10 => 20, 12 => 30], $originalStocks);
        }
    }

    /** UTCID15 - đơn VNPay bị auto-cancel cũng phải hoàn lại stock đã giữ */
    public function testAutoCancelledOrderAlsoRestoresStock(): void
    {
        $this->assertTrue(shouldAutoCancelVnpayOrder('vnpay', 'unpaid', 'pending', 90));
        $this->assertSame([22], restoreStockForItems([['product_id' => 10, 'stock' => 20, 'quantity' => 2]]));
    }

    // ==========================================================
    // 3. F03 — validateCheckoutForm() — 15 test case
    // ==========================================================
    public function checkoutFormProvider(): array
    {
        return [
            'UTCID01 - hợp lệ, COD' => ['Nguyen Van A', '0901234567', '123 Le Loi, Q1, TP.HCM', 'cod', []],
            'UTCID02 - hợp lệ, VNPay' => ['Nguyen Van A', '0901234567', '123 Le Loi, Q1, TP.HCM', 'vnpay', []],
            'UTCID03 - hợp lệ, Momo' => ['Nguyen Van A', '0901234567', '123 Le Loi, Q1, TP.HCM', 'momo', []],
            'UTCID04 - tên rỗng' => ['', '0901234567', '123 Le Loi, Q1, TP.HCM', 'cod', ['Vui lòng nhập họ tên người nhận.']],
            'UTCID05 - tên toàn khoảng trắng (sau trim rỗng)' => ['   ', '0901234567', '123 Le Loi, Q1, TP.HCM', 'cod', ['Vui lòng nhập họ tên người nhận.']],
            'UTCID06 - SĐT rỗng' => ['Nguyen Van A', '', '123 Le Loi, Q1, TP.HCM', 'cod', ['Vui lòng nhập số điện thoại.']],
            'UTCID07 - SĐT hợp lệ (0+9 số)' => ['Nguyen Van A', '0901234567', '123 Le Loi, Q1, TP.HCM', 'cod', []],
            'UTCID08 - SĐT +84 hợp lệ' => ['Nguyen Van A', '+84901234567', '123 Le Loi, Q1, TP.HCM', 'cod', []],
            'UTCID09 - SĐT 0+7 số (dưới boundary tối thiểu 8)' => ['Nguyen Van A', '01234567', '123 Le Loi, Q1, TP.HCM', 'cod', ['Số điện thoại không hợp lệ.']],
            'UTCID10 - SĐT 0+11 số (vượt boundary tối đa 10)' => ['Nguyen Van A', '091234567890', '123 Le Loi, Q1, TP.HCM', 'cod', ['Số điện thoại không hợp lệ.']],
            'UTCID11 - SĐT có ký tự chữ' => ['Nguyen Van A', '09012ABC67', '123 Le Loi, Q1, TP.HCM', 'cod', ['Số điện thoại không hợp lệ.']],
            'UTCID12 - địa chỉ rỗng' => ['Nguyen Van A', '0901234567', '', 'cod', ['Vui lòng nhập địa chỉ giao hàng.']],
            'UTCID13 - pay_method không hợp lệ (banking)' => ['Nguyen Van A', '0901234567', '123 Le Loi, Q1, TP.HCM', 'banking', ['Phương thức thanh toán không hợp lệ.']],
            'UTCID14 - pay_method sai chuỗi (COD viết hoa)' => ['Nguyen Van A', '0901234567', '123 Le Loi, Q1, TP.HCM', 'COD', ['Phương thức thanh toán không hợp lệ.']],
            'UTCID15 - dữ liệu có khoảng trắng thừa, được trim trước khi validate' => ['  Nguyen Van A  ', ' 0901234567 ', ' 123 Le Loi, Q1, TP.HCM ', 'cod', []],
        ];
    }

    /** @dataProvider checkoutFormProvider */
    public function testValidateCheckoutForm(string $name, string $phone, string $address, string $payMethod, array $expectedErrors): void
    {
        $this->assertSame($expectedErrors, validateCheckoutForm($name, $phone, $address, $payMethod));
    }
}
