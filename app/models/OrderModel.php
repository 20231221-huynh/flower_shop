<?php
// app/models/OrderModel.php
require_once ROOT_PATH . '/core/Model.php';

class OrderModel extends Model {
    protected string $table = 'orders';

    public function getWithItems(int $orderId): array|false {
        $order = $this->find($orderId);
        if (!$order) return false;
        $stmt = $this->db->prepare("
            SELECT oi.*, p.name AS product_name, pi.image_url AS image
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$orderId]);
        $order['items'] = $stmt->fetchAll();
        return $order;
    }

    public function getByUser(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT * FROM orders
            WHERE user_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getByShipper(int $shipperId): array {
        $stmt = $this->db->prepare("
            SELECT o.*, u.full_name AS customer_name
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.shipper_id = ?
              AND o.delivery_status IN ('assigned', 'shipping')
            ORDER BY o.created_at DESC
        ");
        $stmt->execute([$shipperId]);
        return $stmt->fetchAll();
    }

    public function getAllPaginated(int $page, int $perPage = 20, ?string $status = null, bool $filterRefund = false): array {
        $offset = ($page - 1) * $perPage;
        $where  = '';
        $params = [];

        if ($filterRefund) {
            $where    = " WHERE o.pay_status = 'refunded'";
        } elseif ($status) {
            $where    = " WHERE o.status = ?";
            $params[] = $status;
        }

        $stmtTotal = $this->db->prepare("SELECT COUNT(*) FROM orders o" . $where);
        $stmtTotal->execute($params);
        $total = (int)$stmtTotal->fetchColumn();

        // FIX: Dùng named limit/offset thay vì nối string trực tiếp
        $stmt = $this->db->prepare("
            SELECT o.*, u.full_name AS customer_name
            FROM orders o
            JOIN users u ON o.user_id = u.id
            $where
            ORDER BY o.created_at DESC
            LIMIT $perPage OFFSET $offset
        ");
        $stmt->execute($params);

        return [
            'data'         => $stmt->fetchAll(),
            'total'        => $total,
            'current_page' => $page,
            'last_page'    => max(1, (int)ceil($total / $perPage)),
            'per_page'     => $perPage,
        ];
    }

    public function insertItem(array $data): void {
        $this->db->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, price)
            VALUES (?, ?, ?, ?)
        ")->execute([$data['order_id'], $data['product_id'], $data['quantity'], $data['price']]);
    }

    public function updateStatus(int $id, string $status): bool {
        return $this->update($id, ['status' => $status]);
    }

    public function markDeliveryFailed(int $id, string $reasonStatus): bool {
        return $this->update($id, [
            'status'          => $reasonStatus,
            'delivery_status' => 'failed',
        ]);
    }

    /**
     * Tự động hủy đơn VNPay chưa thanh toán quá $minutes phút
     */
    public function cancelStalledVnpayOrders(int $minutes = 1): int {
        $stmt = $this->db->prepare("
            UPDATE orders
            SET status     = 'cancelled',
                pay_status = 'failed'
            WHERE pay_method = 'vnpay'
              AND pay_status = 'unpaid'
              AND status     = 'pending'
              AND created_at < NOW() - INTERVAL :minutes MINUTE
        ");
        $stmt->bindValue(':minutes', $minutes, \PDO::PARAM_INT);
        $stmt->execute();
        $cancelled = $stmt->rowCount();

        if ($cancelled > 0) {
            // Đồng bộ bảng payments
            $this->db->prepare("
                UPDATE payments p
                JOIN orders o ON p.order_id = o.id
                SET p.status = 'failed'
                WHERE o.pay_method = 'vnpay'
                  AND o.status      = 'cancelled'
                  AND p.status      = 'pending'
            ")->execute();
        }

        return $cancelled;
    }

    // Lấy đơn theo tab cho shipper
    public function getByShipperTabbed(int $shipperId, string $tab): array {
        $where = match($tab) {
            'done' => "o.shipper_id = ? AND o.delivery_status IN ('delivered','failed')",
            'all'  => "o.shipper_id = ?",
            default=> "o.shipper_id = ? AND o.delivery_status IN ('assigned','shipping')",
        };
        $stmt = $this->db->prepare("
            SELECT o.*, u.full_name AS customer_name
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE $where
            ORDER BY o.created_at DESC
        ");
        $stmt->execute([$shipperId]);
        return $stmt->fetchAll();
    }

    // Đếm từng nhóm đơn cho badge tab
    public function getShipperCounts(int $shipperId): array {
        $stmt = $this->db->prepare("
            SELECT
              SUM(delivery_status IN ('assigned','shipping'))    AS active,
              SUM(delivery_status IN ('delivered','failed'))     AS done
            FROM orders
            WHERE shipper_id = ?
        ");
        $stmt->execute([$shipperId]);
        $row = $stmt->fetch();
        return [
            'active' => (int)($row['active'] ?? 0),
            'done'   => (int)($row['done']   ?? 0),
        ];
    }
}