<?php
// app/controllers/shipper/ShipperController.php
require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/app/models/OrderModel.php';

class ShipperController extends Controller {

    public function index(): void {
        $this->requireRole('shipper');
        $shipperId  = (int)$_SESSION['user_id'];
        $tab        = $_GET['tab'] ?? 'active';
        $orderModel = new OrderModel();
        $orders     = $orderModel->getByShipperTabbed($shipperId, $tab);
        $counts     = $orderModel->getShipperCounts($shipperId);

        $this->render('shipper/index', [
            'title'  => 'Đơn hàng của tôi',
            'orders' => $orders,
            'tab'    => $tab,
            'counts' => $counts,
        ]);
    }

    public function updateStatus(string $id): void {
        $this->requireRole('shipper');
        verifyCsrf();

        $orderModel     = new OrderModel();
        $order          = $orderModel->find((int)$id);
        $deliveryStatus = $this->input('delivery_status');

        if (!$order) {
            $this->json(['success' => false, 'message' => 'Đơn hàng không tồn tại.'], 404);
            return;
        }
        if ((int)$order['shipper_id'] !== (int)$_SESSION['user_id']) {
            $this->json(['success' => false, 'message' => 'Bạn không có quyền cập nhật đơn này.'], 403);
            return;
        }
        if (!in_array($deliveryStatus, ['shipping', 'delivered', 'failed'])) {
            $this->json(['success' => false, 'message' => 'Trạng thái không hợp lệ.'], 400);
            return;
        }

        $db = Database::getInstance();

        // FIX: đơn đã ở trạng thái cuối (đã giao / đã ghi nhận thất bại) thì không xử lý lại,
        // tránh cộng dồn note, hoàn kho nhiều lần khi bấm lặp lại.
        if (in_array($order['delivery_status'], ['delivered', 'failed'])) {
            $this->json(['success' => false, 'message' => 'Đơn hàng đã được xử lý xong, không thể cập nhật lại.'], 409);
            return;
        }

        // ── GIAO THẤT BẠI ─────────────────────────────────────
        if ($deliveryStatus === 'failed') {
            $reason       = $this->input('reason');
            $validReasons = ['failed_no_contact', 'failed_address', 'failed_rejected'];
            if (!in_array($reason, $validReasons)) {
                $this->json(['success' => false, 'message' => 'Vui lòng chọn lý do thất bại.'], 400);
                return;
            }

            $db->beginTransaction();
            try {
                // FIX: delivery_status là ENUM('pending','assigned','shipping','delivered','failed')
                // nên phải lưu đúng giá trị 'failed'; lý do cụ thể ($reason) chỉ lưu trong note.
                // (Trước đây gán $reason thẳng vào delivery_status khiến giá trị không hợp lệ,
                // nút hành động không biến mất và note bị lặp lại mỗi lần bấm.)
                $db->prepare("
                    UPDATE orders
                    SET status          = 'cancelled',
                        delivery_status = 'failed',
                        note            = CONCAT(IFNULL(note,''), ' [Thất bại: $reason]')
                    WHERE id = ?
                ")->execute([$id]);

                // FIX: Hoàn kho sản phẩm khi giao thất bại
                $items = $db->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
                $items->execute([$id]);
                foreach ($items->fetchAll() as $item) {
                    $db->prepare("UPDATE products SET stock = stock + ? WHERE id = ?")
                       ->execute([$item['quantity'], $item['product_id']]);
                }

                // FIX: Xử lý hoàn tiền theo từng trường hợp
                $alreadyPaid = $order['pay_status'] === 'paid';

                if ($alreadyPaid) {
                    // Đã thanh toán → đánh dấu cần hoàn tiền để admin xử lý
                    $db->prepare("
                        UPDATE orders SET pay_status = 'refunded' WHERE id = ?
                    ")->execute([$id]);

                    $db->prepare("
                        UPDATE payments SET status = 'failed' WHERE order_id = ?
                    ")->execute([$id]);
                }
                // COD chưa thu tiền hoặc unpaid → không cần làm gì thêm

                $db->commit();

                $message = $alreadyPaid
                    ? 'Đã ghi nhận giao thất bại. Đơn đã thanh toán — admin sẽ xử lý hoàn tiền.'
                    : 'Đã ghi nhận giao thất bại.';

                $this->json(['success' => true, 'message' => $message, 'need_refund' => $alreadyPaid]);

            } catch (\Throwable $e) {
                $db->rollBack();
                $this->json(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()], 500);
            }
            return;
        }

        // ── ĐANG GIAO / ĐÃ GIAO ───────────────────────────────
        $update = ['delivery_status' => $deliveryStatus];

        if ($deliveryStatus === 'delivered') {
            $update['status'] = 'delivered';
            // COD: tự động ghi nhận đã thu tiền khi giao thành công
            if ($order['pay_method'] === 'cod') {
                $update['pay_status'] = 'paid';
                $db->prepare("
                    UPDATE payments SET status = 'success', paid_at = NOW()
                    WHERE order_id = ? AND status = 'pending'
                ")->execute([$id]);
            }
        } else {
            $update['status'] = 'shipping';
        }

        $result = $orderModel->update((int)$id, $update);
        $this->json(['success' => $result]);
    }
}