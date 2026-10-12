<?php
// app/controllers/customer/ProfileController.php
require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/app/models/UserModel.php';

class ProfileController extends Controller {

    public function index(): void {
        $this->requireLogin();
        $user = (new UserModel())->find((int)$_SESSION['user_id']);
        $this->render('customer/profile', ['title' => 'Hồ sơ của tôi', 'user' => $user]);
    }

    public function update(): void {
        $this->requireLogin();
        verifyCsrf();

        $userId   = (int)$_SESSION['user_id'];
        $fullName = trim($this->input('full_name'));
        $phone    = trim($this->input('phone'));
        $password = $this->input('password');
        $confirm  = $this->input('confirm_password');

        $errors = [];
        if (strlen($fullName) < 2) $errors[] = 'Họ tên phải có ít nhất 2 ký tự.';
        if ($phone && !preg_match('/^(0|\+84)[0-9]{8,10}$/', $phone))
            $errors[] = 'Số điện thoại không hợp lệ.';
        if ($password) {
            if (strlen($password) < 8)  $errors[] = 'Mật khẩu mới phải có ít nhất 8 ký tự.';
            if ($password !== $confirm)  $errors[] = 'Mật khẩu xác nhận không khớp.';
        }

        if ($errors) {
            foreach ($errors as $e) $this->flash('error', $e);
            $this->redirect('/profile');
            return;
        }

        $data = ['full_name' => $fullName, 'phone' => $phone];
        if ($password) {
            $data['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
        }

        (new UserModel())->update($userId, $data);
        $_SESSION['full_name'] = $fullName;

        $this->flash('success', 'Cập nhật hồ sơ thành công!');
        $this->redirect('/profile');
    }
}
