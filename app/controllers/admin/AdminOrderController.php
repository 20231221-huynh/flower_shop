<?php
// app/controllers/admin/AdminOrderController.php
require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/app/models/OrderModel.php';
require_once ROOT_PATH . '/app/models/UserModel.php';

class AdminOrderController extends Controller {

    public function index(): void {
        $this->requireRole('admin');

        // Tự động hủy đơn VNPay bị treo quá 1 phút
        $orderModel = new OrderModel();
        $cancelled  = $orderModel->cancelStalledVnpayOrders(1);
        if ($cancelled > 0) {
            $this->flash('info', "Đã tự động hủy $cancelled đơn VNPay chưa thanh toán quá 1 phút.");
        }

        $page   = max(1, (int)($_GET['page'] ?? 1));
        $status = $_GET['status'] ?? null;

        // FIX: Chỉ chấp nhận status hợp lệ
        $validStatuses = ['pending', 'confirmed', 'shipping', 'delivered', 'cancelled'];

        // FIX: 'refunded' lọc theo pay_status thay vì status
        $filterRefund = ($status === 'refunded');
        if ($filterRefund) $status = null;
        if ($status && !in_array($status, $validStatuses)) $status = null;

        $result = $orderModel->getAllPaginated($page, 20, $status, $filterRefund);

        $this->render('admin/order/list', [
            'title'  => 'Quản lý đơn hàng',
            'result' => $result,
            'status' => $status,
        ]);
    }

    public function detail(string $id): void {
        $this->requireRole('admin');
        $order    = (new OrderModel())->getWithItems((int)$id);
        $shippers = (new UserModel())->getShippers();
        if (!$order) {
            http_response_code(404);
            die('Không tìm thấy đơn hàng.');
        }
        $this->render('admin/order/detail', [
            'title'    => "Đơn hàng #$id",
            'order'    => $order,
            'shippers' => $shippers,
        ]);
    }

    public function updateStatus(string $id): void {
        $this->requireRole('admin');
        verifyCsrf();
        $status  = $this->input('status');
        $allowed = ['pending', 'confirmed', 'shipping', 'delivered', 'cancelled'];

        if (!in_array($status, $allowed)) {
            $this->json(['success' => false, 'message' => 'Trạng thái không hợp lệ'], 400);
            return;
        }

        $orderModel = new OrderModel();
        $order      = $orderModel->find((int)$id);
        if (!$order) {
            $this->json(['success' => false, 'message' => 'Đơn hàng không tồn tại'], 404);
            return;
        }

        $result = $orderModel->update((int)$id, ['status' => $status]);
        $this->json(['success' => $result]);
    }

    public function assignShipper(string $id): void {
        $this->requireRole('admin');
        verifyCsrf();
        $shipperId = (int)$this->input('shipper_id');

        if ($shipperId <= 0) {
            $this->json(['success' => false, 'message' => 'Vui lòng chọn shipper.'], 400);
            return;
        }

        $result = (new OrderModel())->update((int)$id, [
            'shipper_id'      => $shipperId,
            'delivery_status' => 'assigned',
            'status'          => 'shipping',
        ]);

        $this->json(['success' => $result]);
    }

    public function confirmRefund(string $id): void {
        $this->requireRole('admin');
        verifyCsrf();
        $db = Database::getInstance();
        // Đổi pay_status = 'refund_done' để phân biệt "cần hoàn" vs "đã hoàn xong"
        $db->prepare("UPDATE orders SET pay_status = 'refund_done' WHERE id = ? AND pay_status = 'refunded'")->execute([$id]);
        $db->prepare("UPDATE payments SET txn_ref = CONCAT(IFNULL(txn_ref,''), ' [REFUNDED]') WHERE order_id = ?")->execute([$id]);
        $this->json(['success' => true]);
    }

    public function confirmPaid(string $id): void {
        $this->requireRole('admin');
        // FIX: Thêm verifyCsrf để bảo mật
        verifyCsrf();

        $db    = Database::getInstance();
        $order = $db->prepare("SELECT * FROM orders WHERE id = ?")->execute([$id]);

        $db->prepare("UPDATE orders SET pay_status = 'paid' WHERE id = ? AND pay_status = 'unpaid'")->execute([$id]);
        $db->prepare("UPDATE payments SET status = 'success', paid_at = NOW() WHERE order_id = ? AND status = 'pending'")->execute([$id]);

        $this->json(['success' => true]);
    }
}