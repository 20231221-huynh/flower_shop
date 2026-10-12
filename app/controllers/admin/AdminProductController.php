<?php
// app/controllers/admin/AdminProductController.php
require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/app/models/ProductModel.php';
require_once ROOT_PATH . '/app/models/CategoryModel.php';

class AdminProductController extends Controller {

    public function index(): void {
        $this->requireRole('admin');
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $keyword = trim($_GET['q'] ?? '');
        $model   = new ProductModel();
        $result  = $model->searchAdmin($keyword, $page, 15);
        $this->render('admin/product/list', [
            'title'   => 'Quản lý sản phẩm',
            'result'  => $result,
            'keyword' => $keyword,
        ]);
    }

    public function create(): void {
        $this->requireRole('admin');
        $categories = (new CategoryModel())->all();
        $this->render('admin/product/form', [
            'title'      => 'Thêm sản phẩm mới',
            'product'    => null,
            'images'     => [],
            'categories' => $categories,
        ]);
    }

    public function store(): void {
        $this->requireRole('admin');
        verifyCsrf();
        [$data, $errors] = $this->validateProduct();
        if ($errors) {
            $this->render('admin/product/form', [
                'title'      => 'Thêm sản phẩm mới',
                'errors'     => $errors,
                'product'    => $data,
                'images'     => [],
                'categories' => (new CategoryModel())->all(),
            ]);
            return;
        }

        $model     = new ProductModel();
        $productId = $model->insert($data);
        $this->handleImages($productId);
        $this->flash('success', 'Thêm sản phẩm thành công!');
        $this->redirect('/admin/products');
    }

    public function edit(string $id): void {
        $this->requireRole('admin');
        $model   = new ProductModel();
        $product = $model->find((int)$id);
        if (!$product) { http_response_code(404); die('Không tìm thấy sản phẩm.'); }
        $images  = $model->getImages((int)$id);
        $this->render('admin/product/form', [
            'title'      => 'Sửa sản phẩm',
            'product'    => $product,
            'images'     => $images,
            'categories' => (new CategoryModel())->all(),
        ]);
    }

    public function update(string $id): void {
        $this->requireRole('admin');
        verifyCsrf();
        [$data, $errors] = $this->validateProduct();
        $model   = new ProductModel();
        $product = $model->find((int)$id);
        if (!$product) { http_response_code(404); die('Không tìm thấy sản phẩm.'); }

        if ($errors) {
            $this->render('admin/product/form', [
                'title'      => 'Sửa sản phẩm',
                'errors'     => $errors,
                'product'    => array_merge($product, $data),
                'images'     => $model->getImages((int)$id),
                'categories' => (new CategoryModel())->all(),
            ]);
            return;
        }
        $model->update((int)$id, $data);
        $this->handleImages((int)$id);
        $this->flash('success', 'Cập nhật sản phẩm thành công!');
        $this->redirect('/admin/products');
    }

    public function delete(string $id): void {
        $this->requireRole('admin');
        verifyCsrf();
        // Soft-delete: giữ lịch sử đơn hàng
        $result = (new ProductModel())->update((int)$id, ['status' => 'deleted']);
        $this->json(['success' => $result, 'message' => $result ? 'Đã ẩn sản phẩm.' : 'Lỗi khi ẩn sản phẩm.']);
    }

    // ── Private helpers ──────────────────────────────────────
    private function validateProduct(): array {
        $salePrice = $this->input('sale_price');
        $data = [
            'name'        => trim($this->input('name')),
            'category_id' => (int)$this->input('category_id'),
            'price'       => (float)$this->input('price'),
            'sale_price'  => ($salePrice !== '' && $salePrice !== null) ? (float)$salePrice : null,
            'stock'       => (int)$this->input('stock'),
            'description' => trim($this->input('description')),
            'status'      => $this->input('status', 'active'),
        ];
        $errors = [];
        if (!$data['name'])                                    $errors[] = 'Tên sản phẩm không được trống.';
        if ($data['price'] <= 0)                               $errors[] = 'Giá sản phẩm phải lớn hơn 0.';
        if ($data['category_id'] <= 0)                         $errors[] = 'Vui lòng chọn danh mục.';
        if ($data['stock'] < 0)                                $errors[] = 'Tồn kho không được âm.';
        // FIX: Giá khuyến mãi phải nhỏ hơn giá gốc
        if ($data['sale_price'] !== null && $data['sale_price'] >= $data['price']) {
            $errors[] = 'Giá khuyến mãi phải nhỏ hơn giá gốc.';
        }
        return [$data, $errors];
    }

    private function handleImages(int $productId): void {
        if (empty($_FILES['images']['name'][0])) return;
        $model     = new ProductModel();
        $uploadDir = UPLOAD_DIR . 'products/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        // FIX: Chỉ xóa ảnh cũ khi chỉnh sửa nếu có upload ảnh mới
        $existingImages = $model->getImages($productId);
        $isFirst = empty($existingImages); // ảnh đầu là primary nếu chưa có ảnh

        foreach ($_FILES['images']['tmp_name'] as $i => $tmpName) {
            if (!is_uploaded_file($tmpName)) continue;
            $ext     = strtolower(pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($ext, $allowed)) continue;
            // FIX: Kiểm tra kích thước file (tối đa 5MB)
            if ($_FILES['images']['size'][$i] > 5 * 1024 * 1024) continue;
            $filename = uniqid('product_') . '.' . $ext;
            move_uploaded_file($tmpName, $uploadDir . $filename);
            $url = UPLOAD_URL . 'products/' . $filename;
            $model->addImage($productId, $url, $isFirst);
            $isFirst = false;
        }
    }
}
