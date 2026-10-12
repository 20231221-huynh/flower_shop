<?php
/**
 * Member3Helper.php — Người phụ trách: Hứa Đức Lương
 *
 * LƯU Ý QUAN TRỌNG:
 * 3 hàm gốc của Lương (F01 updateStatus, F02 HandleDeliveryFailure/cancelStalledVnpayOrders,
 * F03 placeOrder) đều là phương thức Controller/Service gắn chặt với Database (PDO),
 * $_SESSION, transaction và request. Đây KHÔNG phải là hàm thuần (pure function) nên
 * không thể/không nên "Unit Test" y nguyên như validatePhone() hay isValid() — muốn test
 * đúng bản chất DB + transaction thì phải dùng Integration Test (IT) với CSDL thật hoặc
 * mock PDO, việc đó nằm ngoài phạm vi Unit Test.
 *
 * Cách xử lý ở đây (đúng thực hành phổ biến khi viết Unit Test cho code có side-effect):
 * tách phần LOGIC QUYẾT ĐỊNH (business rule / nhánh rẽ) ra thành các hàm thuần, không đụng
 * tới DB, rồi unit test các hàm thuần đó. Phần thao tác DB thật (UPDATE, transaction,
 * rollback) vẫn giữ nguyên trong Controller/Service gốc và nên được kiểm thử ở tầng
 * Integration/System Test.
 *
 * Các hàm thuần bên dưới tái hiện đúng các nhánh rẽ đã mô tả trong file Excel test case
 * (sheet ShipperDeliveryModule / OrderInventoryRefundModule / ValidationServiceModule).
 */

require_once __DIR__ . '/Member1Helper.php'; // dùng lại validatePhone()

/**
 * F01 — Logic quyết định của ShipperController::updateStatus(), KHÔNG bao gồm thao tác DB.
 *
 * @param array|null $order  ['id'=>int,'shipper_id'=>int,'delivery_status'=>string,'pay_status'=>string,'pay_method'=>string] hoặc null nếu không tồn tại
 * @param int        $currentUserId  user_id của shipper đang đăng nhập
 * @param string     $requestedStatus  'shipping' | 'delivered' | 'failed' | ...
 * @param string|null $reason  lý do giao thất bại (bắt buộc khi requestedStatus='failed')
 * @return array ['success'=>bool,'httpCode'=>int,'message'=>string,'newDeliveryStatus'=>?string,'newStatus'=>?string,'newPayStatus'=>?string,'refundNeeded'=>bool]
 */
function decideShipperUpdate(?array $order, int $currentUserId, string $requestedStatus, ?string $reason): array
{
    if (!$order) {
        return ['success' => false, 'httpCode' => 404, 'message' => 'Đơn hàng không tồn tại.',
                'newDeliveryStatus' => null, 'newStatus' => null, 'newPayStatus' => null, 'refundNeeded' => false];
    }

    if ((int)$order['shipper_id'] !== $currentUserId) {
        return ['success' => false, 'httpCode' => 403, 'message' => 'Bạn không có quyền cập nhật đơn này.',
                'newDeliveryStatus' => null, 'newStatus' => null, 'newPayStatus' => null, 'refundNeeded' => false];
    }

    if (!in_array($requestedStatus, ['shipping', 'delivered', 'failed'], true)) {
        return ['success' => false, 'httpCode' => 400, 'message' => 'Trạng thái không hợp lệ.',
                'newDeliveryStatus' => null, 'newStatus' => null, 'newPayStatus' => null, 'refundNeeded' => false];
    }

    if ($requestedStatus === 'failed') {
        $validReasons = ['failed_no_contact', 'failed_address', 'failed_rejected'];
        if (!in_array($reason, $validReasons, true)) {
            return ['success' => false, 'httpCode' => 400, 'message' => 'Vui lòng chọn lý do thất bại.',
                    'newDeliveryStatus' => null, 'newStatus' => null, 'newPayStatus' => null, 'refundNeeded' => false];
        }

        $refundNeeded = ($order['pay_status'] === 'paid');
        return [
            'success' => true, 'httpCode' => 200,
            'message' => $refundNeeded
                ? 'Đã ghi nhận giao thất bại. Đơn đã thanh toán – admin sẽ xử lý hoàn tiền.'
                : 'Đã ghi nhận giao thất bại.',
            'newDeliveryStatus' => 'failed',
            'newStatus' => $order['delivery_status'] ?? null, // status tổng không đổi khi failed
            'newPayStatus' => $refundNeeded ? 'refunded' : ($order['pay_status'] ?? null),
            'refundNeeded' => $refundNeeded,
        ];
    }

    // shipping | delivered
    $newPayStatus = $order['pay_status'] ?? null;
    if ($requestedStatus === 'delivered' && $order['pay_method'] === 'cod' && $order['pay_status'] === 'unpaid') {
        $newPayStatus = 'paid';
    }

    return [
        'success' => true, 'httpCode' => 200, 'message' => 'Cập nhật trạng thái đơn hàng thành công.',
        'newDeliveryStatus' => $requestedStatus, 'newStatus' => $requestedStatus,
        'newPayStatus' => $newPayStatus, 'refundNeeded' => false,
    ];
}

/**
 * F02(a) — Logic quyết định khi ghi nhận giao hàng thất bại (không gồm UPDATE DB thật).
 *
 * @return array ['ok'=>bool,'message'=>string,'restoreStock'=>bool,'newPayStatus'=>?string,'newPaymentRecordStatus'=>?string]
 */
function decideDeliveryFailure(string $currentDeliveryStatus, ?string $reason, string $payStatus): array
{
    if ($currentDeliveryStatus === 'failed') {
        return ['ok' => false, 'message' => 'Đơn hàng đã được xử lý giao thất bại trước đó.',
                'restoreStock' => false, 'newPayStatus' => null, 'newPaymentRecordStatus' => null];
    }

    $validReasons = ['failed_no_contact', 'failed_address', 'failed_rejected'];
    if (!in_array($reason, $validReasons, true)) {
        return ['ok' => false, 'message' => 'Vui lòng chọn lý do thất bại.',
                'restoreStock' => false, 'newPayStatus' => null, 'newPaymentRecordStatus' => null];
    }

    $alreadyPaid = ($payStatus === 'paid');
    return [
        'ok' => true,
        'message' => 'Đã ghi nhận giao thất bại.',
        'restoreStock' => true,
        'newPayStatus' => $alreadyPaid ? 'refunded' : $payStatus,
        'newPaymentRecordStatus' => $alreadyPaid ? 'failed' : null,
    ];
}

/**
 * F02(b) — Hoàn lại tồn kho cho danh sách order_items (thuần, không đụng DB).
 *
 * @param array $items [['stock'=>int,'quantity'=>int], ...]
 * @return int[] danh sách stock mới, cùng thứ tự với $items
 */
function restoreStockForItems(array $items): array
{
    return array_map(fn($i) => $i['stock'] + $i['quantity'], $items);
}

/**
 * F02(c) — Điều kiện tự động hủy đơn VNPay quá hạn thanh toán (cancelStalledVnpayOrders).
 */
function shouldAutoCancelVnpayOrder(string $payMethod, string $payStatus, string $orderStatus, int $secondsSinceCreated, int $thresholdSeconds = 60): bool
{
    return $payMethod === 'vnpay'
        && $payStatus === 'unpaid'
        && $orderStatus === 'pending'
        && $secondsSinceCreated >= $thresholdSeconds;
}

/**
 * F02(d) — Đếm số đơn VNPay đủ điều kiện tự động hủy trong 1 danh sách đơn.
 *
 * @param array $orders [['pay_method'=>string,'pay_status'=>string,'status'=>string,'seconds'=>int], ...]
 */
function countAutoCancelableOrders(array $orders): int
{
    $count = 0;
    foreach ($orders as $o) {
        if (shouldAutoCancelVnpayOrder($o['pay_method'], $o['pay_status'], $o['status'], $o['seconds'])) {
            $count++;
        }
    }
    return $count;
}

/**
 * F02(e) — Minh họa ngữ nghĩa "rollback toàn bộ transaction nếu có lỗi giữa chừng":
 * chỉ trả về mảng stock mới nếu XỬ LÝ HẾT các item thành công; nếu có lỗi giữa chừng,
 * ném exception và KHÔNG trả về gì (giữ nguyên mảng gốc phía người gọi) — mô phỏng hành vi
 * $db->rollBack() của bản gốc mà không cần một CSDL thật.
 *
 * @throws RuntimeException nếu $simulateFailure = true
 */
function applyStockRestoreTransactionally(array $stocks, array $items, bool $simulateFailure = false): array
{
    $newStocks = $stocks; // làm việc trên bản sao — dữ liệu gốc $stocks không đổi cho tới khi return
    foreach ($items as $idx => $item) {
        if ($simulateFailure && $idx === 1) {
            throw new RuntimeException('Lỗi hệ thống khi cập nhật tồn kho — transaction đã rollback.');
        }
        $newStocks[$item['product_id']] = ($newStocks[$item['product_id']] ?? 0) + $item['quantity'];
    }
    return $newStocks;
}

/**
 * F03 — Logic validate dữ liệu checkout của OrderController::placeOrder().
 *
 * @return string[] Danh sách lỗi; mảng rỗng nghĩa là dữ liệu hợp lệ.
 */
function validateCheckoutForm(string $name, string $phone, string $address, string $payMethod): array
{
    $name = trim($name);
    $phone = trim($phone);
    $address = trim($address);

    $errors = [];

    if ($name === '') {
        $errors[] = 'Vui lòng nhập họ tên người nhận.';
    }

    if ($phone === '') {
        $errors[] = 'Vui lòng nhập số điện thoại.';
    } elseif (!validatePhone($phone)) {
        $errors[] = 'Số điện thoại không hợp lệ.';
    }

    if ($address === '') {
        $errors[] = 'Vui lòng nhập địa chỉ giao hàng.';
    }

    // So khớp chính xác (phân biệt hoa/thường) theo đúng hành vi hiện tại của code
    if (!in_array($payMethod, ['cod', 'vnpay', 'momo'], true)) {
        $errors[] = 'Phương thức thanh toán không hợp lệ.';
    }

    return $errors;
}
