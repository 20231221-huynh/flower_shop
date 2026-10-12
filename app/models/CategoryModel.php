<?php
// app/models/CategoryModel.php
require_once ROOT_PATH . '/core/Model.php';

class CategoryModel extends Model {
    protected string $table = 'categories';

    public function findBySlug(string $slug): array|false {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE slug = ?");
        $stmt->execute([$slug]);
        return $stmt->fetch();
    }

}