<?php
// app/controllers/admin/AdminCouponController.php
require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/app/models/CouponModel.php';

class AdminCouponController extends Controller {

    public function index(): void {
        $this->requireRole('admin');
        $coupons = (new CouponModel())->all("1 ORDER BY id DESC");
        $this->render('admin/coupon/index', [
            'title'   => 'Quản lý mã giảm giá',
            'coupons' => $coupons,
        ]);
    }

    public function store(): void {
        $this->requireRole('admin');
        verifyCsrf();

        $code          = strtoupper(trim($this->input('code')));
        $discountType  = $this->input('discount_type', 'fixed');
        $discountValue = (float)$this->input('discount_value');
        $minOrder      = (float)$this->input('min_order', 0);
        $maxUses       = $this->input('max_uses') !== '' ? (int)$this->input('max_uses') : null;
        $expiresAt     = $this->input('expires_at') ?: null;

        // Validate
        $errors = [];
        if (!preg_match('/^[A-Z0-9_\-]{3,20}$/', $code))
            $errors[] = 'Mã coupon chỉ gồm chữ hoa, số, dấu - hoặc _ (3–20 ký tự).';
        if ($discountValue <= 0)
            $errors[] = 'Giá trị giảm phải lớn hơn 0.';
        if ($discountType === 'percent' && $discountValue > 100)
            $errors[] = 'Phần trăm giảm không được vượt quá 100%.';

        // Kiểm tra trùng mã
        $existing = (new CouponModel())->findByCode($code);
        if ($existing) $errors[] = "Mã \"$code\" đã tồn tại.";

        if ($errors) {
            foreach ($errors as $e) $this->flash('error', $e);
            $this->redirect('/admin/coupons');
            return;
        }

        (new CouponModel())->insert([
            'code'           => $code,
            'discount_type'  => $discountType,
            'discount_value' => $discountValue,
            'min_order'      => $minOrder,
            'max_uses'       => $maxUses,
            'expires_at'     => $expiresAt,
            'status'         => 'active',
        ]);

        $this->flash('success', "Đã tạo mã giảm giá \"$code\" thành công!");
        $this->redirect('/admin/coupons');
    }

    public function delete(string $id): void {
        $this->requireRole('admin');
        verifyCsrf();
        (new CouponModel())->update((int)$id, ['status' => 'inactive']);
        $this->json(['success' => true]);
    }
}
