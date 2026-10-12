<?php
// app/models/UserModel.php
require_once ROOT_PATH . '/core/Model.php';

class UserModel extends Model {
    protected string $table = 'users';

    public function findByEmail(string $email): array|false {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function getShippers(): array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE role = 'shipper' AND status = 'active' ORDER BY full_name");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Thêm tìm kiếm + lọc theo role cho admin
    public function search(string $keyword, ?string $role, int $page, int $perPage = 20): array {
        $conditions = [];
        $params     = [];

        if ($keyword) {
            $conditions[] = "(full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
            $k = "%$keyword%";
            $params = array_merge($params, [$k, $k, $k]);
        }
        if ($role && in_array($role, ['admin', 'shipper', 'customer'])) {
            $conditions[] = "role = ?";
            $params[]     = $role;
        }

        $where  = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $offset = ($page - 1) * $perPage;

        $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM users $where");
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        $stmt = $this->db->prepare("SELECT * FROM users $where ORDER BY id DESC LIMIT $perPage OFFSET $offset");
        $stmt->execute($params);

        return [
            'data'         => $stmt->fetchAll(),
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => max(1, (int)ceil($total / $perPage)),
        ];
    }
}
