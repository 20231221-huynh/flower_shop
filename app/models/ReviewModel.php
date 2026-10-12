<?php
// app/models/ReviewModel.php
require_once ROOT_PATH . '/core/Model.php';

class ReviewModel extends Model {
    protected string $table = 'reviews';

    public function getByProduct(int $productId): array {
        $stmt = $this->db->prepare("
            SELECT r.*, u.full_name
            FROM reviews r JOIN users u ON r.user_id = u.id
            WHERE r.product_id = ?
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }

    public function getAvgRating(int $productId): float {
        $stmt = $this->db->prepare("SELECT AVG(rating) FROM reviews WHERE product_id = ?");
        $stmt->execute([$productId]);
        return round((float)$stmt->fetchColumn(), 1);
    }

    public function hasReviewed(int $userId, int $productId): bool {
        $stmt = $this->db->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        return (bool)$stmt->fetch();
    }
}
