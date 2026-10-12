<?php
// app/services/OrderService.php
require_once ROOT_PATH . '/app/models/OrderModel.php';
require_once ROOT_PATH . '/app/models/CartModel.php';
require_once ROOT_PATH . '/app/models/ProductModel.php';
require_once ROOT_PATH . '/app/models/CouponModel.php';

class OrderService {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Tạo đơn hàng từ giỏ hàng – dùng PDO Transaction
     * FIX: Trừ kho ngay khi đặt hàng (đặt rồi mới trừ tránh race condition)
     */
    public function createFromCart(int $userId, array $formData): int {
        $cartModel = new CartModel();
        $cartId    = $cartModel->getOrCreate($userId);
        $items     = $cartModel->getItems($cartId);

        if (empty($items)) {
            throw new RuntimeException('Giỏ hàng trống.');
        }

        $subtotal = array_sum(
            array_map(fn($i) => ($i['sale_price'] ?: $i['price']) * $i['quantity'], $items)
        );

        $couponId = null;
        $discount = 0;

        if (!empty($_SESSION['coupon'])) {
            $couponModel = new CouponModel();
            $coupon      = $_SESSION['coupon'];
            if ($couponModel->isValid($coupon, $subtotal)) {
                $discount = $couponModel->calcDiscount($coupon, $subtotal);
                $couponId = $coupon['id'];
            } else {
                // FIX: Xóa coupon hết hạn ngay trong transaction
                unset($_SESSION['coupon']);
            }
        }

        $total = max(0, $subtotal - $discount);

        $this->db->beginTransaction();

        try {
            $productModel = new ProductModel();

            // FIX: Dùng SELECT ... FOR UPDATE để tránh race condition khi nhiều user mua cùng lúc
            foreach ($items as $item) {
                $stmt = $this->db->prepare("SELECT stock, status FROM products WHERE id = ? FOR UPDATE");
                $stmt->execute([$item['product_id']]);
                $product = $stmt->fetch();

                if (!$product || $product['status'] !== 'active') {
                    throw new RuntimeException("Sản phẩm \"{$item['name']}\" không còn bán.");
                }
                if ($product['stock'] < $item['quantity']) {
                    throw new RuntimeException("Sản phẩm \"{$item['name']}\" chỉ còn {$product['stock']} trong kho.");
                }
            }

            $orderModel = new OrderModel();
            $orderId = $orderModel->insert([
                'user_id'          => $userId,
                'coupon_id'        => $couponId,
                'receiver_name'    => $formData['receiver_name'],
                'receiver_phone'   => $formData['receiver_phone'],
                'delivery_address' => $formData['delivery_address'],
                'note'             => $formData['note'] ?? '',
                'total_amount'     => $total,
                'discount_amount'  => $discount,
                'pay_method'       => $formData['pay_method'],
                'status'           => 'pending',
                'pay_status'       => 'unpaid',
                'delivery_status'  => 'pending',
            ]);

            // Tạo order_items + FIX: Trừ kho ngay
            foreach ($items as $item) {
                $price = $item['sale_price'] ?: $item['price'];
                $orderModel->insertItem([
                    'order_id'   => $orderId,
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                    'price'      => $price,
                ]);

                // FIX: Trừ kho ngay sau khi tạo order_item
                $this->db->prepare("
                    UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?
                ")->execute([$item['quantity'], $item['product_id'], $item['quantity']]);
            }

            // Tạo bản ghi payment
            $this->db->prepare("
                INSERT INTO payments (order_id, method, amount, status)
                VALUES (?, ?, ?, 'pending')
            ")->execute([$orderId, $formData['pay_method'], $total]);

            // Đánh dấu coupon đã dùng
            if ($couponId) {
                (new CouponModel())->incrementUse($couponId);
                unset($_SESSION['coupon']);
            }

            // FIX: Xóa giỏ hàng ngay trong transaction
            $cartModel->clearCart($cartId);

            $this->db->commit();
            return $orderId;

        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
