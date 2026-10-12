<?php
/**
 * Member4Helper.php — Người phụ trách: Tạ Văn Tùng
 *
 * Nguồn gốc (theo sheet "Code_Tung" trong UnitTest.xlsx):
 *  - validateProductData(): bản pure của AdminProductController::validateProduct()
 *                            (nhận thẳng $data đã build sẵn thay vì đọc $this->input()
 *                            để test độc lập với request/HTTP).
 *  - decideProductDelete() : nghiệp vụ soft-delete khi xóa sản phẩm đã phát sinh đơn hàng.
 *  - formatSlug()          : chuẩn hoá tên sản phẩm tiếng Việt thành slug URL.
 *  - adjustStock()         : bản pure của InventoryService::updateStock()/decreaseStock(),
 *                            tách phần tính toán/điều kiện ra khỏi câu lệnh UPDATE thật.
 */

/**
 * Validate dữ liệu form thêm/sửa sản phẩm.
 *
 * @param array $data ['name'=>?string,'price'=>?float,'stock'=>?int,'sale_price'=>?float,...]
 * @return string[] Danh sách lỗi; mảng rỗng nghĩa là hợp lệ.
 */
function validateProductData(array $data): array
{
    $errors = [];

    if (!isset($data['name']) || trim((string)$data['name']) === '') {
        $errors[] = 'Tên sản phẩm không được để trống.';
    }

    if (!array_key_exists('price', $data) || $data['price'] === null) {
        $errors[] = 'Giá bán không được để trống.';
    } elseif ((float)$data['price'] <= 0) {
        $errors[] = 'Giá bán phải lớn hơn 0.';
    }

    if (!array_key_exists('stock', $data) || $data['stock'] === null) {
        $errors[] = 'Tồn kho không được để trống.';
    } elseif (!is_int($data['stock']) || $data['stock'] < 0) {
        $errors[] = 'Tồn kho phải là số nguyên >= 0.';
    }

    if (!empty($errors)) {
        return $errors; // dừng sớm giống code gốc: các lỗi cơ bản được liệt kê trước
    }

    if (isset($data['sale_price']) && $data['sale_price'] !== null && (float)$data['sale_price'] >= (float)$data['price']) {
        $errors[] = 'Giá khuyến mãi phải nhỏ hơn giá gốc.';
    }

    return $errors;
}

/**
 * Nghiệp vụ xóa sản phẩm: nếu sản phẩm đã phát sinh đơn hàng thì Soft Delete
 * (chuyển is_deleted=true) thay vì xóa cứng khỏi CSDL.
 *
 * @return array ['action'=>'soft_delete'|'hard_delete','message'=>string]
 */
function decideProductDelete(bool $hasOrders): array
{
    if ($hasOrders) {
        return ['action' => 'soft_delete', 'message' => 'Sản phẩm đã được ẩn (is_deleted=true) vì đã phát sinh đơn hàng.'];
    }
    return ['action' => 'hard_delete', 'message' => 'Sản phẩm đã được xóa khỏi hệ thống.'];
}

/**
 * Chuẩn hoá chuỗi tên sản phẩm tiếng Việt thành slug URL:
 * chữ thường, bỏ dấu, bỏ ký tự đặc biệt (thay bằng khoảng trắng để không dính chữ),
 * gộp khoảng trắng/gạch ngang liên tiếp thành 1 dấu "-", cắt "-" ở 2 đầu.
 *
 * @throws InvalidArgumentException nếu $text là null
 */
function formatSlug(?string $text): string
{
    if ($text === null) {
        throw new InvalidArgumentException('Tên sản phẩm không được null');
    }

    $text = mb_strtolower($text, 'UTF-8');

    $map = [
        'à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a','â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a',
        'ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a',
        'è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e',
        'ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i',
        'ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o',
        'ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o',
        'ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u',
        'ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y',
        'đ'=>'d',
    ];
    $text = strtr($text, $map);

    // Ký tự không thuộc a-z0-9/khoảng trắng/gạch ngang -> thay bằng khoảng trắng
    // (thay vì xóa hẳn) để tránh 2 từ bị dính liền nhau, ví dụ "XL/XXL" -> "xl xxl".
    $text = preg_replace('/[^a-z0-9\s-]/', ' ', $text);
    $text = preg_replace('/[\s-]+/', '-', trim($text));

    return trim($text, '-');
}

/**
 * Bản pure của việc điều chỉnh tồn kho (nhập hàng / bán hàng / hoàn trả).
 *
 * @param int|null $currentStock Tồn kho hiện tại; null nếu sản phẩm không tồn tại
 * @param mixed    $adjustment   Số lượng điều chỉnh (dương: nhập/hoàn trả, âm: bán hàng)
 * @return array ['ok'=>bool,'newStock'=>?int,'message'=>string]
 * @throws InvalidArgumentException nếu $adjustment không phải số nguyên
 */
function adjustStock(?int $currentStock, $adjustment): array
{
    if ($currentStock === null) {
        return ['ok' => false, 'newStock' => null, 'message' => 'Sản phẩm không tồn tại.'];
    }

    if (!is_int($adjustment)) {
        throw new InvalidArgumentException('Số lượng điều chỉnh không hợp lệ');
    }

    $newStock = $currentStock + $adjustment;
    if ($newStock < 0) {
        return ['ok' => false, 'newStock' => null, 'message' => 'Không đủ hàng tồn kho.'];
    }

    return ['ok' => true, 'newStock' => $newStock, 'message' => ''];
}
