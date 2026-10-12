<?php
// public/index.php  – Front Controller
session_start();
define('ROOT_PATH', dirname(__DIR__));

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/core/Database.php';
require_once dirname(__DIR__) . '/core/Model.php';
require_once dirname(__DIR__) . '/core/Controller.php';
require_once dirname(__DIR__) . '/core/Router.php';
require_once dirname(__DIR__) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/app/helpers/FormatHelper.php';

$router = new Router();

// ── Auth ───────────────────────────────────────────────────────
$router->get('/auth/register',  'customer/AuthController', 'showRegister');
$router->post('/auth/register', 'customer/AuthController', 'register');
$router->get('/auth/login',     'customer/AuthController', 'showLogin');
$router->post('/auth/login',    'customer/AuthController', 'login');
$router->get('/auth/logout',    'customer/AuthController', 'logout');

// ── Customer – Trang chủ & Sản phẩm ───────────────────────────
$router->get('/',                'customer/HomeController',    'index');
$router->get('/products',        'customer/ProductController', 'index');
$router->get('/products/{id}',   'customer/ProductController', 'detail');
$router->get('/category/{slug}', 'customer/ProductController', 'byCategory');

// ── Customer – Giỏ hàng ────────────────────────────────────────
$router->get('/cart',               'customer/CartController', 'index');
$router->get('/cart/count',         'customer/CartController', 'count');        // FIX: Thêm route badge
$router->post('/cart/add',          'customer/CartController', 'add');
$router->post('/cart/update',       'customer/CartController', 'update');
$router->post('/cart/remove',       'customer/CartController', 'remove');
$router->post('/cart/coupon',       'customer/CartController', 'applyCoupon'); // FIX: Sửa tên route khớp JS

// ── Customer – Đặt hàng & Thanh toán ──────────────────────────
$router->get('/checkout',                'customer/OrderController',  'checkout');
$router->post('/checkout',               'customer/OrderController',  'placeOrder');
$router->get('/orders',                  'customer/OrderController',  'myOrders');
$router->get('/orders/{id}',             'customer/OrderController',  'detail');
$router->post('/orders/{id}/cancel',     'customer/OrderController',  'cancel');
$router->get('/payment/process',         'customer/OrderController',  'paymentProcess');
$router->get('/payment/vnpay-return',    'customer/PaymentController','vnpayReturn');

// ── Customer – Đánh giá & Hồ sơ ───────────────────────────────
$router->post('/reviews',  'customer/ReviewController',  'store');
$router->get('/profile',   'customer/ProfileController', 'index');
$router->post('/profile',  'customer/ProfileController', 'update');

// ── Admin – Dashboard & Báo cáo ───────────────────────────────
$router->get('/admin',          'admin/AdminDashboardController', 'index');
$router->get('/admin/reports',         'admin/AdminReportController', 'index');
$router->get('/admin/reports/export-excel', 'admin/AdminReportController', 'exportExcel');
$router->get('/admin/reports/export-pdf',   'admin/AdminReportController', 'exportPdf');

// ── Admin – Sản phẩm ──────────────────────────────────────────
$router->get('/admin/products',              'admin/AdminProductController', 'index');
$router->get('/admin/products/create',       'admin/AdminProductController', 'create');
$router->post('/admin/products/create',      'admin/AdminProductController', 'store');
$router->get('/admin/products/{id}/edit',    'admin/AdminProductController', 'edit');
$router->post('/admin/products/{id}/edit',   'admin/AdminProductController', 'update');
$router->post('/admin/products/{id}/delete', 'admin/AdminProductController', 'delete');

// ── Admin – Danh mục ──────────────────────────────────────────
$router->get('/admin/categories',              'admin/AdminCategoryController', 'index');
$router->post('/admin/categories',             'admin/AdminCategoryController', 'store');
$router->post('/admin/categories/{id}/delete', 'admin/AdminCategoryController', 'delete');

// ── Admin – Đơn hàng ──────────────────────────────────────────
$router->get('/admin/orders',                    'admin/AdminOrderController', 'index');
$router->get('/admin/orders/{id}',               'admin/AdminOrderController', 'detail');
$router->post('/admin/orders/{id}/status',       'admin/AdminOrderController', 'updateStatus');
$router->post('/admin/orders/{id}/assign',       'admin/AdminOrderController', 'assignShipper');
$router->post('/admin/orders/{id}/confirm-paid',   'admin/AdminOrderController', 'confirmPaid');
$router->post('/admin/orders/{id}/confirm-refund', 'admin/AdminOrderController', 'confirmRefund');

// ── Admin – Người dùng ────────────────────────────────────────
$router->get('/admin/users',               'admin/AdminUserController', 'index');
$router->post('/admin/users/{id}/status',  'admin/AdminUserController', 'updateStatus');

// ── Admin – Mã giảm giá ───────────────────────────────────────
$router->get('/admin/coupons',              'admin/AdminCouponController', 'index');
$router->post('/admin/coupons',             'admin/AdminCouponController', 'store');
$router->post('/admin/coupons/{id}/delete', 'admin/AdminCouponController', 'delete');

// ── Shipper ───────────────────────────────────────────────────
$router->get('/shipper',                     'shipper/ShipperController', 'index');
$router->post('/shipper/orders/{id}/status', 'shipper/ShipperController', 'updateStatus');

$router->dispatch();