<?php
// app/helpers/FormatHelper.php

function formatMoney(float $amount): string {
    return number_format($amount, 0, ',', '.') . ' ₫';
}

function formatDate(string $datetime): string {
    return date('d/m/Y H:i', strtotime($datetime));
}

function slug(string $text): string {
    $text = mb_strtolower($text, 'UTF-8');
    $map  = ['à'=>'a','á'=>'a','ả'=>'a','ã'=>'a','ạ'=>'a','ă'=>'a','ắ'=>'a','ặ'=>'a','ằ'=>'a','ẳ'=>'a','ẵ'=>'a','â'=>'a','ấ'=>'a','ầ'=>'a','ẩ'=>'a','ẫ'=>'a','ậ'=>'a','đ'=>'d','è'=>'e','é'=>'e','ẻ'=>'e','ẽ'=>'e','ẹ'=>'e','ê'=>'e','ế'=>'e','ề'=>'e','ể'=>'e','ễ'=>'e','ệ'=>'e','ì'=>'i','í'=>'i','ỉ'=>'i','ĩ'=>'i','ị'=>'i','ò'=>'o','ó'=>'o','ỏ'=>'o','õ'=>'o','ọ'=>'o','ô'=>'o','ố'=>'o','ồ'=>'o','ổ'=>'o','ỗ'=>'o','ộ'=>'o','ơ'=>'o','ớ'=>'o','ờ'=>'o','ở'=>'o','ỡ'=>'o','ợ'=>'o','ù'=>'u','ú'=>'u','ủ'=>'u','ũ'=>'u','ụ'=>'u','ư'=>'u','ứ'=>'u','ừ'=>'u','ử'=>'u','ữ'=>'u','ự'=>'u','ỳ'=>'y','ý'=>'y','ỷ'=>'y','ỹ'=>'y','ỵ'=>'y'];
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', trim($text));
    return $text;
}

function e(mixed $str): string {
    // FIX: Chấp nhận null/int thay vì chỉ string (tránh PHP 8 TypeError)
    return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8');
}

function statusLabel(string $status): string {
    return match($status) {
        'pending'           => '<span class="badge bg-warning text-dark">Chờ xác nhận</span>',
        'confirmed'         => '<span class="badge bg-info text-white">Đã xác nhận</span>',
        'shipping'          => '<span class="badge bg-primary">Đang giao</span>',
        'delivered'         => '<span class="badge bg-success">Đã giao</span>',
        'cancelled'         => '<span class="badge bg-danger">Đã hủy</span>',
        'paid'              => '<span class="badge bg-success">Đã thanh toán</span>',
        'refunded'          => '<span class="badge bg-warning text-dark">⚠️ Cần hoàn tiền</span>',
        'refund_done'       => '<span class="badge bg-success">✅ Đã hoàn tiền</span>',
        'unpaid'            => '<span class="badge bg-secondary">Chưa TT</span>',
        'failed'            => '<span class="badge bg-danger">Thất bại</span>',
        // FIX: Thêm các trạng thái giao hàng của shipper
        'assigned'          => '<span class="badge bg-info text-white">Đã phân công</span>',
        'failed_no_contact' => '<span class="badge bg-dark">Không nghe máy</span>',
        'failed_address'    => '<span class="badge bg-dark">Sai địa chỉ</span>',
        'failed_rejected'   => '<span class="badge bg-dark">Khách trả hàng</span>',
        default             => "<span class='badge bg-secondary'>" . htmlspecialchars($status, ENT_QUOTES) . "</span>",
    };
}

// FIX: Thêm helper hiển thị phương thức thanh toán cho gọn
function payMethodLabel(string $method): string {
    return match($method) {
        'cod'           => '💵 Tiền mặt (COD)',
        'vnpay'         => '🏦 VNPay',
        'momo'          => '💜 MoMo / Chuyển khoản',
        'bank_transfer' => '🏧 Chuyển khoản MB Bank',
        default         => strtoupper($method),
    };
}