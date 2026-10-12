<?php
// app/services/VNPayService.php

class VNPayService {

    public function createPaymentUrl(int $orderId, float $amount, string $orderInfo): string {
        date_default_timezone_set('Asia/Ho_Chi_Minh');

        $tmnCode    = VNPAY_TMN_CODE;
        $hashSecret = VNPAY_HASH_SECRET;
        $txnRef     = $orderId . '_' . time();
        $createDate = date('YmdHis');
        $expireDate = date('YmdHis', strtotime('+15 minutes'));
        $ipAddr     = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if ($ipAddr === '::1') $ipAddr = '127.0.0.1';

        $inputData = [
            'vnp_Version'    => '2.1.0',
            'vnp_Command'    => 'pay',
            'vnp_TmnCode'    => $tmnCode,
            'vnp_Amount'     => (int)($amount * 100),
            'vnp_CurrCode'   => 'VND',
            'vnp_TxnRef'     => $txnRef,
            'vnp_OrderInfo'  => $orderInfo,
            'vnp_OrderType'  => 'other',
            'vnp_Locale'     => 'vn',
            'vnp_ReturnUrl'  => VNPAY_RETURN_URL,
            'vnp_IpAddr'     => $ipAddr,
            'vnp_CreateDate' => $createDate,
            'vnp_ExpireDate' => $expireDate,
        ];

        ksort($inputData);
        $queryParts = [];
        foreach ($inputData as $key => $value) {
            $queryParts[] = urlencode($key) . '=' . urlencode($value);
        }
        $queryString = implode('&', $queryParts);
        $secureHash  = hash_hmac('sha512', $queryString, $hashSecret);
        $paymentUrl  = VNPAY_URL . '?' . $queryString . '&vnp_SecureHash=' . $secureHash;

        Database::getInstance()
            ->prepare("UPDATE payments SET txn_ref = ? WHERE order_id = ?")
            ->execute([$txnRef, $orderId]);

        return $paymentUrl;
    }

    public function verifyReturn(array $params): bool {
        $secureHash = $params['vnp_SecureHash'] ?? '';
        unset($params['vnp_SecureHash'], $params['vnp_SecureHashType']);
        ksort($params);

        $queryParts = [];
        foreach ($params as $key => $value) {
            $queryParts[] = urlencode($key) . '=' . urlencode($value);
        }
        $queryString = implode('&', $queryParts);
        $hash = hash_hmac('sha512', $queryString, VNPAY_HASH_SECRET);
        return hash_equals(strtoupper($hash), strtoupper($secureHash));
    }

    public function handleReturn(array $params): array {
        if (!$this->verifyReturn($params)) {
            return ['success' => false, 'message' => 'Sai chữ ký. Giao dịch không hợp lệ.'];
        }

        $txnRef  = $params['vnp_TxnRef'] ?? '';
        $orderId = (int)explode('_', $txnRef)[0];
        $db      = Database::getInstance();

        // FIX: Kiểm tra đơn tồn tại và chưa thanh toán trước khi xử lý
        $order = $db->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
        $order->execute([$orderId]);
        $order = $order->fetch();

        if (!$order) {
            return ['success' => false, 'message' => 'Đơn hàng không tồn tại.'];
        }

        // FIX: Idempotent – tránh xử lý lại đơn đã paid (VNPay đôi khi gọi return 2 lần)
        if ($order['pay_status'] === 'paid') {
            return ['success' => true, 'order_id' => $orderId];
        }

        if ($params['vnp_ResponseCode'] === '00') {
            $db->prepare("
                UPDATE payments SET status = 'success', paid_at = NOW()
                WHERE order_id = ? AND status = 'pending'
            ")->execute([$orderId]);

            $db->prepare("
                UPDATE orders SET pay_status = 'paid', status = 'confirmed'
                WHERE id = ? AND pay_status = 'unpaid'
            ")->execute([$orderId]);

            // FIX: Kho đã bị trừ lúc tạo đơn trong OrderService - KHÔNG trừ lại ở đây

            return ['success' => true, 'order_id' => $orderId];
        }

        // Thanh toán thất bại
        $db->prepare("UPDATE payments SET status = 'failed' WHERE order_id = ? AND status = 'pending'")
           ->execute([$orderId]);

        $responseMessages = [
            '24' => 'Giao dịch bị hủy.',
            '51' => 'Tài khoản không đủ số dư.',
            '65' => 'Vượt hạn mức giao dịch trong ngày.',
            '75' => 'Ngân hàng thanh toán đang bảo trì.',
        ];
        $msg = $responseMessages[$params['vnp_ResponseCode'] ?? ''] ?? 'Thanh toán thất bại. Vui lòng thử lại.';

        return ['success' => false, 'message' => $msg, 'order_id' => $orderId];
    }
}
