<?php
// app/helpers/AuthHelper.php

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function isAdmin(): bool {
    return ($_SESSION['role'] ?? '') === 'admin';
}

function isShipper(): bool {
    return ($_SESSION['role'] ?? '') === 'shipper';
}

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        // FIX: JSON response nếu là AJAX request
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Phiên làm việc hết hạn. Vui lòng tải lại trang.']);
        } else {
            die('Phiên làm việc hết hạn. <a href="javascript:history.back()">Quay lại</a>');
        }
        exit;
    }
    // FIX: Không rotate token (gây lỗi khi multi-tab) – giữ nguyên token trong session
}
