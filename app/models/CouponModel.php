<?php
// app/models/CouponModel.php
require_once ROOT_PATH . '/core/Model.php';

class CouponModel extends Model {
    protected string $table = 'coupons';

    public function findByCode(string $code): array|false {
        $stmt = $this->db->prepare("SELECT * FROM coupons WHERE code = ? LIMIT 1");
        $stmt->execute([strtoupper($code)]);
        return $stmt->fetch();
    }

    public function isValid(array $coupon, float $orderTotal): bool {
        if ($coupon['status'] !== 'active') return false;
        // FIX: Kiểm tra đơn tối thiểu
        if ($coupon['min_order'] > 0 && $orderTotal < $coupon['min_order']) return false;
        // FIX: Kiểm tra hạn sử dụng
        if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < time()) return false;
        // FIX: Kiểm tra số lần dùng
        if ($coupon['max_uses'] !== null && $coupon['used_count'] >= $coupon['max_uses']) return false;
        return true;
    }

    public function calcDiscount(array $coupon, float $orderTotal): float {
        if ($coupon['discount_type'] === 'percent') {
            $discount = $orderTotal * ($coupon['discount_value'] / 100);
        } else {
            $discount = (float)$coupon['discount_value'];
        }
        // FIX: Giảm không vượt quá tổng đơn
        return min($discount, $orderTotal);
    }

    public function incrementUse(int $couponId): void {
        $this->db->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?")
                 ->execute([$couponId]);
    }
}
