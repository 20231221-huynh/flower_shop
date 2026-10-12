<?php
// app/models/CartModel.php
require_once ROOT_PATH . '/core/Model.php';

class CartModel extends Model {
    protected string $table = 'carts';

    public function getOrCreate(int $userId): int {
        $stmt = $this->db->prepare("SELECT id FROM carts WHERE user_id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        if ($row) return (int)$row['id'];
        return $this->insert(['user_id' => $userId]);
    }

    public function getItems(int $cartId): array {
        $stmt = $this->db->prepare("
            SELECT ci.*, p.name, p.price, p.sale_price, p.stock, pi.image_url AS image
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.id
            LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
            WHERE ci.cart_id = ? AND p.status = 'active'
        ");
        $stmt->execute([$cartId]);
        return $stmt->fetchAll();
    }

    // FIX: Thêm method lấy qty hiện tại của 1 sản phẩm trong giỏ
    public function getItemQty(int $cartId, int $productId): int {
        $stmt = $this->db->prepare("SELECT quantity FROM cart_items WHERE cart_id = ? AND product_id = ?");
        $stmt->execute([$cartId, $productId]);
        return (int)($stmt->fetchColumn() ?: 0);
    }

    public function addItem(int $cartId, int $productId, int $qty): void {
        $stmt = $this->db->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?");
        $stmt->execute([$cartId, $productId]);
        $existing = $stmt->fetch();
        if ($existing) {
            $this->db->prepare("UPDATE cart_items SET quantity = quantity + ? WHERE id = ?")
                     ->execute([$qty, $existing['id']]);
        } else {
            $this->db->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?,?,?)")
                     ->execute([$cartId, $productId, $qty]);
        }
    }

    public function updateItem(int $cartId, int $productId, int $qty): void {
        if ($qty <= 0) {
            $this->removeItem($cartId, $productId);
            return;
        }
        $this->db->prepare("UPDATE cart_items SET quantity = ? WHERE cart_id = ? AND product_id = ?")
                 ->execute([$qty, $cartId, $productId]);
    }

    public function removeItem(int $cartId, int $productId): void {
        $this->db->prepare("DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?")
                 ->execute([$cartId, $productId]);
    }

    public function clearCart(int $cartId): void {
        $this->db->prepare("DELETE FROM cart_items WHERE cart_id = ?")->execute([$cartId]);
    }

    public function countItems(int $cartId): int {
        $stmt = $this->db->prepare("SELECT SUM(quantity) FROM cart_items WHERE cart_id = ?");
        $stmt->execute([$cartId]);
        return (int)$stmt->fetchColumn();
    }

    // FIX: đếm số LOẠI sản phẩm khác nhau đang có trong giỏ (dùng để giới hạn tối đa 20 loại)
    public function countDistinctItems(int $cartId): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM cart_items WHERE cart_id = ?");
        $stmt->execute([$cartId]);
        return (int)$stmt->fetchColumn();
    }
}