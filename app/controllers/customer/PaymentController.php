<?php
// app/controllers/customer/PaymentController.php
require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/app/services/VNPayService.php';

class PaymentController extends Controller {
    public function vnpayReturn(): void {
        $vnpay  = new VNPayService();
        $result = $vnpay->handleReturn($_GET);

        if ($result['success']) {
            $this->flash('success', 'Thanh toán VNPay thành công! Đơn hàng đã được xác nhận.');
            $this->redirect('/orders/' . $result['order_id']);
        } else {
            // FIX: Redirect về đơn hàng cụ thể nếu biết order_id, để user có thể thử lại
            $this->flash('error', $result['message'] ?? 'Thanh toán thất bại.');
            if (!empty($result['order_id'])) {
                $this->redirect('/orders/' . $result['order_id']);
            } else {
                $this->redirect('/orders');
            }
        }
    }
}
