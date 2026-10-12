<?php
// app/controllers/admin/AdminDashboardController.php
require_once ROOT_PATH . '/core/Controller.php';

class AdminDashboardController extends Controller {

    public function index(): void {
        $this->requireRole('admin');
        $db = Database::getInstance();

        // Tổng quan
        $stats = [
            'total_orders'    => (int)$db->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
            'pending_orders'  => (int)$db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn(),
            'total_revenue'   => (float)$db->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE pay_status = 'paid'")->fetchColumn(),
            'total_customers' => (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn(),
            // FIX: Thêm đơn hủy tự động VNPay hôm nay
            'cancelled_today' => (int)$db->query("SELECT COUNT(*) FROM orders WHERE status='cancelled' AND DATE(created_at)=CURDATE()")->fetchColumn(),
        ];

        // Doanh thu 7 ngày gần nhất
        $stmt = $db->query("
            SELECT DATE(created_at) AS day,
                   COUNT(*) AS orders,
                   COALESCE(SUM(total_amount),0) AS revenue
            FROM orders
            WHERE pay_status = 'paid'
              AND created_at >= NOW() - INTERVAL 7 DAY
            GROUP BY DATE(created_at)
            ORDER BY day ASC
        ");
        $revenueByDay = $stmt->fetchAll();

        // Top 5 sản phẩm bán chạy
        $stmt = $db->query("
            SELECT p.name, SUM(oi.quantity) AS sold,
                   SUM(oi.quantity * oi.price) AS revenue
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN orders o ON oi.order_id = o.id
            WHERE o.pay_status = 'paid'
            GROUP BY p.id, p.name
            ORDER BY sold DESC
            LIMIT 5
        ");
        $topProducts = $stmt->fetchAll();

        // FIX: Đơn hàng mới nhất chờ xác nhận (5 đơn)
        $stmt = $db->query("
            SELECT o.*, u.full_name AS customer_name
            FROM orders o JOIN users u ON o.user_id = u.id
            WHERE o.status = 'pending'
            ORDER BY o.created_at DESC
            LIMIT 5
        ");
        $pendingOrders = $stmt->fetchAll();

        $this->render('admin/dashboard', [
            'title'         => 'Dashboard',
            'stats'         => $stats,
            'revenueByDay'  => $revenueByDay,
            'topProducts'   => $topProducts,
            'pendingOrders' => $pendingOrders,
        ]);
    }
}
