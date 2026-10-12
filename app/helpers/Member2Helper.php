<?php
/**
 * Member2Helper.php — Người phụ trách: Lê Xuân Huynh
 *
 * Nguồn gốc (theo sheet "Code - Huynh" trong UnitTest.xlsx):
 *  - isValid()          : CouponModel::isValid()
 *  - calcDiscount()     : CouponModel::calcDiscount()
 *  - createOrderSummary(): bản thuần (pure) trích từ OrderService::createFromCart(),
 *                          nhận thẳng $items và $coupon làm tham số thay vì đọc từ
 *                          CartModel/$_SESSION để có thể unit test độc lập với CSDL.
 */

/**
 * Kiểm tra điều kiện hợp lệ của coupon.
 */
function isValid(array $coupon, float $orderTotal): bool
{
    if ($coupon['status'] !== 'active') return false;
    if ($coupon['min_order'] > 0 && $orderTotal < $coupon['min_order']) return false;
    if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < time()) return false;
    if ($coupon['max_uses'] !== null && $coupon['used_count'] >= $coupon['max_uses']) return false;
    return true;
}

/**
 * Tính số tiền được giảm của coupon (giả định coupon đã hợp lệ).
 */
function calcDiscount(array $coupon, float $orderTotal): float
{
    if ($coupon['discount_type'] === 'percent') {
        $discount = $orderTotal * ($coupon['discount_value'] / 100);
    } else {
        // fixed
        $discount = (float)$coupon['discount_value'];
    }
    // Giảm không vượt quá tổng đơn
    return min($discount, $orderTotal);
}

/**
 * Bản thuần của OrderService::createFromCart(): tính subtotal từ giỏ hàng,
 * áp dụng discount từ coupon (nếu có và hợp lệ), tính total cuối cùng.
 *
 * @param array      $items  Danh sách item giỏ hàng ['sale_price'=>?float,'price'=>float,'quantity'=>int]
 * @param array|null $coupon Coupon đang áp dụng (session), hoặc null nếu không có
 * @return array ['subtotal'=>float,'discount'=>float,'total'=>float]
 * @throws RuntimeException nếu giỏ hàng trống
 */
function createOrderSummary(array $items, ?array $coupon): array
{
    if (empty($items)) {
        throw new RuntimeException('Giỏ hàng trống.');
    }

    $subtotal = array_sum(array_map(fn($i) => ($i['sale_price'] ?: $i['price']) * $i['quantity'], $items));

    $discount = 0;
    if ($coupon !== null && isValid($coupon, $subtotal)) {
        $discount = calcDiscount($coupon, $subtotal);
    }
    // Coupon không hợp lệ/hết hạn => coi như không áp dụng (tương đương bị xóa khỏi session)

    $total = max(0, $subtotal - $discount);

    return ['subtotal' => (float)$subtotal, 'discount' => (float)$discount, 'total' => (float)$total];
}
