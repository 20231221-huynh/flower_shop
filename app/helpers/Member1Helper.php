<?php
/**
 * Member1Helper.php
 *
 * Các hàm nghiệp vụ được tách ra để viết Unit Test tự động (PHPUnit).
 * Nguồn gốc (theo file UnitTest.xlsx - sheet "Code - Văn"):
 *  - validatePhone()       : trích từ app/controllers/customer/OrderController.php (dòng 64)
 *  - calcCartSubtotal()    : trích từ app/controllers/customer/CartController.php (dòng 20, 102)
 *  - checkStockAvailable() : trích từ app/controllers/customer/CartController.php hàm add() (dòng 60-79)
 *
 * Người phụ trách: Phạm Quang Văn
 */

/**
 * Kiểm tra định dạng số điện thoại người nhận (VN) hợp lệ trước khi tạo đơn hàng.
 * Hợp lệ khi: bắt đầu bằng "0" hoặc "+84", theo sau là 8-10 chữ số.
 *
 * @param string $phone
 * @return bool
 */
function validatePhone(string $phone): bool
{
    return (bool) preg_match('/^(0|\+84)[0-9]{8,10}$/', $phone);
}

/**
 * Tính tổng tiền tạm tính (subtotal) của giỏ hàng.
 * Ưu tiên sale_price nếu có (khác 0/null), ngược lại lấy price.
 *
 * @param array $items Mảng các item dạng ['sale_price' => ?float, 'price' => float, 'quantity' => int]
 * @return float
 */
function calcCartSubtotal(array $items): float
{
    return array_sum(array_map(fn($i) => ($i['sale_price'] ?: $i['price']) * $i['quantity'], $items));
}

/**
 * Kiểm tra số lượng thêm vào giỏ có vượt quá tồn kho hiện có hay không
 * (tính cả số lượng sản phẩm đã có sẵn trong giỏ).
 *
 * @param int $stock       Tồn kho hiện tại của sản phẩm
 * @param int $existingQty Số lượng sản phẩm này đã có sẵn trong giỏ
 * @param int $addQty      Số lượng muốn thêm vào giỏ
 * @return array ['ok' => bool, 'message' => string]
 */
function checkStockAvailable(int $stock, int $existingQty, int $addQty): array
{
    if ($addQty > $stock) {
        return ['ok' => false, 'message' => "Chỉ còn {$stock} sản phẩm trong kho."];
    }
    if ($existingQty + $addQty > $stock) {
        return ['ok' => false, 'message' => "Giỏ hàng đã có {$existingQty} sản phẩm. Tồn kho chỉ còn {$stock}."];
    }
    return ['ok' => true, 'message' => ''];
}
