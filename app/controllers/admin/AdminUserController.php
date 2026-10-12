<?php
// app/controllers/admin/AdminUserController.php
require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/app/models/UserModel.php';

class AdminUserController extends Controller {

    public function index(): void {
        $this->requireRole('admin');
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $keyword = trim($_GET['q'] ?? '');
        $role    = $_GET['role'] ?? null;
        $model   = new UserModel();
        $result  = $model->search($keyword, $role, $page, 20);

        $this->render('admin/user/index', [
            'title'   => 'Quản lý người dùng',
            'result'  => $result,
            'keyword' => $keyword,
            'role'    => $role,
        ]);
    }

    public function updateStatus(string $id): void {
        $this->requireRole('admin');
        verifyCsrf();

        // Không được khóa chính mình
        if ((int)$id === (int)$_SESSION['user_id']) {
            $this->json(['success' => false, 'message' => 'Không thể thay đổi trạng thái tài khoản của chính mình.'], 400);
            return;
        }

        $status  = $this->input('status');
        $allowed = ['active', 'inactive', 'banned'];
        if (!in_array($status, $allowed)) {
            $this->json(['success' => false, 'message' => 'Trạng thái không hợp lệ.'], 400);
            return;
        }

        (new UserModel())->update((int)$id, ['status' => $status]);
        $this->json(['success' => true]);
    }
}
