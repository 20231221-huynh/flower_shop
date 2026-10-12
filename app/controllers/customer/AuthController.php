<?php
// app/controllers/customer/AuthController.php
require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/app/models/UserModel.php';

class AuthController extends Controller {

    public function showRegister(): void {
        if (isLoggedIn()) $this->redirect('/');
        $this->render('customer/auth/register', ['title' => 'Đăng ký tài khoản']);
    }

    public function register(): void {
        verifyCsrf();
        $fullName = trim($this->input('full_name'));
        $email    = trim($this->input('email'));
        $phone    = trim($this->input('phone'));
        $password = $this->input('password');
        $confirm  = $this->input('confirm_password');
        $errors   = [];

        if (strlen($fullName) < 2)                    $errors[] = 'Họ tên phải có ít nhất 2 ký tự.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ.';
        // FIX: validate phone
        if ($phone && !preg_match('/^(0|\+84)[0-9]{8,10}$/', $phone)) {
            $errors[] = 'Số điện thoại không hợp lệ.';
        }
        if (strlen($password) < 8)                    $errors[] = 'Mật khẩu phải có ít nhất 8 ký tự.';
        if ($password !== $confirm)                   $errors[] = 'Mật khẩu xác nhận không khớp.';

        $model = new UserModel();
        if ($model->findByEmail($email)) $errors[] = 'Email đã được sử dụng.';

        if ($errors) {
            $this->render('customer/auth/register', [
                'title'  => 'Đăng ký tài khoản',
                'errors' => $errors,
                'old'    => compact('fullName', 'email', 'phone'),
            ]);
            return;
        }

        $model->insert([
            'full_name'     => $fullName,
            'email'         => $email,
            'phone'         => $phone,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'role'          => 'customer',
            'status'        => 'active',
        ]);

        $this->flash('success', 'Đăng ký thành công! Hãy đăng nhập.');
        $this->redirect('/auth/login');
    }

    public function showLogin(): void {
        if (isLoggedIn()) $this->redirect('/');
        $this->render('customer/auth/login', ['title' => 'Đăng nhập']);
    }

    public function login(): void {
        verifyCsrf();
        $email    = trim($this->input('email'));
        $password = $this->input('password');
        $model    = new UserModel();
        $user     = $model->findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->render('customer/auth/login', [
                'title' => 'Đăng nhập',
                'error' => 'Email hoặc mật khẩu không đúng.',
                'old'   => ['email' => $email],
            ]);
            return;
        }

        if ($user['status'] !== 'active') {
            $this->render('customer/auth/login', [
                'title' => 'Đăng nhập',
                'error' => 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.',
            ]);
            return;
        }

        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role']      = $user['role'];
        // FIX: Lưu thêm email vào session để dùng ở các nơi khác nếu cần
        $_SESSION['email']     = $user['email'];
        // FIX: Lưu thêm phone vào session để checkout.php tự động điền receiver_phone
        $_SESSION['phone']     = $user['phone'];

        match ($user['role']) {
            'admin'   => $this->redirect('/admin'),
            'shipper' => $this->redirect('/shipper'),
            default   => $this->redirect('/'),
        };
    }

    public function logout(): void {
        // FIX: Xóa session sạch sẽ, bao gồm giỏ hàng tạm thời
        $keepCart = $_SESSION['coupon'] ?? null; // Không giữ coupon sau logout
        session_unset();
        session_destroy();
        session_start();
        session_regenerate_id(true);
        $this->redirect('/auth/login');
    }
}