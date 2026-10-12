<?php
// app/models/ProductModel.php
require_once ROOT_PATH . '/core/Model.php';

class ProductModel extends Model {
    protected string $table = 'products';

    public function getWithCategory(int $id): array|false {
        $stmt = $this->db->prepare("
            SELECT p.*, c.name AS category_name, c.slug AS category_slug,
                   pi.image_url AS primary_image
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
            WHERE p.id = ? AND p.status = 'active'
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // Dùng cho trang khách hàng: chỉ hiện sản phẩm active
    public function search(string $keyword, ?int $categoryId, int $page, int $perPage = 12): array {
        $conditions = ["p.status = 'active'"];
        $params     = [];

        if ($keyword) {
            $conditions[] = "p.name LIKE ?";
            $params[]     = "%$keyword%";
        }
        if ($categoryId) {
            $conditions[] = "p.category_id = ?";
            $params[]     = $categoryId;
        }

        return $this->_paginate($conditions, $params, $page, $perPage);
    }

    // FIX: Dùng riêng cho admin: hiện tất cả kể cả inactive, không hiện deleted
    public function searchAdmin(string $keyword, int $page, int $perPage = 15): array {
        $conditions = ["p.status != 'deleted'"];
        $params     = [];

        if ($keyword) {
            $conditions[] = "p.name LIKE ?";
            $params[]     = "%$keyword%";
        }

        return $this->_paginate($conditions, $params, $page, $perPage);
    }

    private function _paginate(array $conditions, array $params, int $page, int $perPage): array {
        $where  = implode(' AND ', $conditions);
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM products p WHERE $where");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $this->db->prepare("
            SELECT p.*, c.name AS category_name,
                   pi.image_url AS primary_image
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
            WHERE $where
            ORDER BY p.id DESC
            LIMIT $perPage OFFSET $offset
        ");
        $stmt->execute($params);

        return [
            'data'         => $stmt->fetchAll(),
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => max(1, (int)ceil($total / $perPage)),
        ];
    }

    public function getImages(int $productId): array {
        $stmt = $this->db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC");
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }

    public function addImage(int $productId, string $url, bool $isPrimary = false): void {
        $this->db->prepare("INSERT INTO product_images (product_id, image_url, is_primary) VALUES (?,?,?)")
                 ->execute([$productId, $url, $isPrimary ? 1 : 0]);
    }

    public function deleteImages(int $productId): void {
        $this->db->prepare("DELETE FROM product_images WHERE product_id = ?")->execute([$productId]);
    }

    public function getFeatured(int $limit = 8): array {
        $stmt = $this->db->prepare("
            SELECT p.*, pi.image_url AS primary_image
            FROM products p
            LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
            WHERE p.status = 'active'
            ORDER BY p.id DESC LIMIT $limit
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function decreaseStock(PDO $db, int $productId, int $qty): void {
        $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?")
           ->execute([$qty, $productId, $qty]);
    }
}
