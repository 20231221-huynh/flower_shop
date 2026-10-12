<?php
// config/app.php

define('BASE_URL', 'http://localhost/flower_shop/public');
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}
define('UPLOAD_DIR', ROOT_PATH . '/public/uploads/');
define('UPLOAD_URL', BASE_URL . '/uploads/');

// Email (PHPMailer)
define('MAIL_HOST',     'smtp.gmail.com');
define('MAIL_PORT',     587);
define('MAIL_USERNAME', 'your@gmail.com');
define('MAIL_PASSWORD', 'your_app_password');
define('MAIL_FROM',     'noreply@flowershop.vn');
define('MAIL_FROM_NAME','Flower Shop');

// VNPay sandbox
define('VNPAY_TMN_CODE',    'YOUR_TMN_CODE');
define('VNPAY_HASH_SECRET', 'YOUR_SECRET');
define('VNPAY_URL',         'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');
define('VNPAY_RETURN_URL',  BASE_URL . '/payment/vnpay-return');

// Session timeout (giây)
define('SESSION_LIFETIME', 3600);
