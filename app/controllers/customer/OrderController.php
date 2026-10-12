<?php
// app/controllers/customer/OrderController.php
require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/app/models/OrderModel.php';
require_once ROOT_PATH . '/app/models/CartModel.php';
require_once ROOT_PATH . '/app/services/OrderService.php';
require_once ROOT_PATH . '/app/services/VNPayService.php';

class OrderController extends Controller {

    public function checkout(): void {
        $this->requireLogin();
        $cartModel = new CartModel();
        $cartId    = $cartModel->getOrCreate((int)$_SESSION['user_id']);
        $items     = $cartModel->getItems($cartId);

        if (empty($items)) {
            $this->flash('error', 'Giỏ hàng trống, không thể đặt hàng.');
            $this->redirect('/cart');
        }

        $subtotal = array_sum(array_map(fn($i) => ($i['sale_price'] ?: $i['price']) * $i['quantity'], $items));
        $coupon   = $_SESSION['coupon'] ?? null;
        $discount = 0;

        if ($coupon) {
            require_once ROOT_PATH . '/app/models/CouponModel.php';
            $couponModel = new CouponModel();
            if ($couponModel->isValid($coupon, $subtotal)) {
                $discount = $couponModel->calcDiscount($coupon, $subtotal);
            } else {
                // FIX: Xóa coupon hết hạn khỏi session khi vào trang checkout
                unset($_SESSION['coupon']);
                $coupon   = null;
                $this->flash('error', 'Mã giảm giá đã hết hạn và đã được xóa.');
            }
        }

        $this->render('customer/order/checkout', [
            'title'    => 'Đặt hàng',
            'items'    => $items,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total'    => $subtotal - $discount,
            'coupon'   => $coupon,
        ]);
    }

    public function placeOrder(): void {
        $this->requireLogin();
        if (function_exists('verifyCsrf')) verifyCsrf();

        $data = [
            'receiver_name'    => trim($this->input('receiver_name')),
            'receiver_phone'   => trim($this->input('receiver_phone')),
            'delivery_address' => trim($this->input('delivery_address')),
            'note'             => trim($this->input('note')),
            'pay_method'       => $this->input('pay_method', 'cod'),
        ];

        $errors = [];
        if (!$data['receiver_name'])                                   $errors[] = 'Vui lòng nhập họ tên người nhận.';
        if (!$data['receiver_phone'])                                  $errors[] = 'Vui lòng nhập số điện thoại.';
        if (!preg_match('/^(0|\+84)[0-9]{8,10}$/', $data['receiver_phone'])) $errors[] = 'Số điện thoại không hợp lệ.';
        if (!$data['delivery_address'])                                $errors[] = 'Vui lòng nhập địa chỉ giao hàng.';
        if (!in_array($data['pay_method'], ['cod', 'vnpay', 'momo'])) $errors[] = 'Phương thức thanh toán không hợp lệ.';

        if ($errors) {
            $this->flash('error', implode('<br>', $errors));
            $this->redirect('/checkout');
        }

        try {
            $service = new OrderService();
            $orderId = $service->createFromCart((int)$_SESSION['user_id'], $data);

            if ($data['pay_method'] === 'vnpay') {
                require_once ROOT_PATH . '/app/models/OrderModel.php';
                $order  = (new OrderModel())->find($orderId);
                $vnpay  = new VNPayService();
                $payUrl = $vnpay->createPaymentUrl($orderId, (float)$order['total_amount'], "Thanh toan don hang #$orderId");
                header('Location: ' . $payUrl);
                exit;
            }

            // FIX: momo dùng QR MB Bank (giữ nguyên flow hiện tại)
            if ($data['pay_method'] === 'momo') {
                $this->redirect('/payment/process?order_id=' . $orderId);
            }

            // COD: xóa giỏ hàng sau khi đặt thành công
            $cartModel = new CartModel();
            $cartId    = $cartModel->getOrCreate((int)$_SESSION['user_id']);
            $cartModel->clearCart($cartId);

            $this->flash('success', "Đặt hàng thành công! Mã đơn: #$orderId");
            $this->redirect('/orders/' . $orderId);

        } catch (Exception $e) {
            $this->flash('error', 'Lỗi: ' . $e->getMessage());
            $this->redirect('/checkout');
        }
    }

    public function myOrders(): void {
        $this->requireLogin();
        $orders = (new OrderModel())->getByUser((int)$_SESSION['user_id']);
        $this->render('customer/order/list', [
            'title'  => 'Đơn hàng của tôi',
            'orders' => $orders,
        ]);
    }

    public function detail(string $id): void {
        $this->requireLogin();
        $order = (new OrderModel())->getWithItems((int)$id);

        if (!$order || (int)$order['user_id'] !== (int)$_SESSION['user_id']) {
            http_response_code(403);
            die('Bạn không có quyền xem đơn hàng này.');
        }

        $this->render('customer/order/detail', [
            'title' => "Đơn hàng #$id",
            'order' => $order,
        ]);
    }

    public function cancel($id): void {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json');

        try {
            if (!function_exists('verifyCsrf')) {
                throw new Exception("Hệ thống thiếu thư viện xác thực.");
            }
            $this->requireLogin();
            verifyCsrf();

            $orderModel = new OrderModel();
            $order      = $orderModel->find((int)$id);

            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Đơn hàng không tồn tại.']);
                exit;
            }
            if ((int)$order['user_id'] !== (int)($_SESSION['user_id'] ?? 0)) {
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền hủy đơn này.']);
                exit;
            }
            if ($order['status'] !== 'pending') {
                echo json_encode(['success' => false, 'message' => 'Đơn hàng đang được xử lý, không thể hủy.']);
                exit;
            }

            // FIX: Cập nhật cả pay_status khi hủy
            $db = Database::getInstance();
            $db->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?")->execute([$id]);
            $db->prepare("UPDATE payments SET status = 'failed' WHERE order_id = ? AND status = 'pending'")->execute([$id]);

            echo json_encode(['success' => true, 'message' => 'Đã hủy đơn hàng thành công.']);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi Server: ' . $e->getMessage()]);
        }
        exit;
    }

    public function paymentProcess(): void {
        $this->requireLogin();
        $orderId    = (int)$this->input('order_id');
        $orderModel = new OrderModel();
        $order      = $orderModel->find($orderId);

        if (!$order || (int)$order['user_id'] !== (int)$_SESSION['user_id']) {
            http_response_code(404);
            die('Đơn hàng không tồn tại hoặc bạn không có quyền.');
        }

        // FIX: Không cho xem trang này nếu đã thanh toán hoặc đã hủy
        if ($order['pay_status'] === 'paid') {
            $this->flash('success', 'Đơn hàng này đã được thanh toán.');
            $this->redirect('/orders/' . $orderId);
        }
        if ($order['status'] === 'cancelled') {
            $this->flash('error', 'Đơn hàng này đã bị hủy.');
            $this->redirect('/orders');
        }

        $this->render('customer/order/payment/payment_process', [
            'title' => 'Thanh toán chuyển khoản',
            'order' => $order,
        ]);
    }
}
