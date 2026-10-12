<?php
// app/controllers/customer/ProductController.php
require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/app/models/ProductModel.php';
require_once ROOT_PATH . '/app/models/CategoryModel.php';
require_once ROOT_PATH . '/app/models/ReviewModel.php';

class ProductController extends Controller {

    public function index(): void {
        $keyword    = trim($_GET['q'] ?? '');
        $categoryId = (int)($_GET['category'] ?? 0) ?: null;
        $page       = max(1, (int)($_GET['page'] ?? 1));
        $model      = new ProductModel();
        $result     = $model->search($keyword, $categoryId, $page);
        $categories = (new CategoryModel())->all();

        $this->render('customer/product/list', [
            'title'      => 'Sản phẩm',
            'result'     => $result,
            'categories' => $categories,
            'keyword'    => $keyword,
            'categoryId' => $categoryId,
        ]);
    }

    public function detail(string $id): void {
        $model   = new ProductModel();
        $product = $model->getWithCategory((int)$id);
        if (!$product) { http_response_code(404); die('Sản phẩm không tồn tại.'); }

        $images      = $model->getImages((int)$id);
        $reviewModel = new ReviewModel();
        $reviews     = $reviewModel->getByProduct((int)$id);
        $avgRating   = $reviewModel->getAvgRating((int)$id);

        // Xác định quyền đánh giá
        $canReview   = false;
        $hasReviewed = false;
        if (isLoggedIn()) {
            $userId      = (int)$_SESSION['user_id'];
            $hasReviewed = $reviewModel->hasReviewed($userId, (int)$id);
            if (!$hasReviewed) {
                // Kiểm tra đã mua và đã nhận hàng chưa
                $db  = Database::getInstance();
                $stmt = $db->prepare("
                    SELECT COUNT(*) FROM order_items oi
                    JOIN orders o ON oi.order_id = o.id
                    WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'delivered'
                ");
                $stmt->execute([$userId, $id]);
                $canReview = (int)$stmt->fetchColumn() > 0;
            }
        }

        $this->render('customer/product/detail', [
            'title'       => $product['name'],
            'product'     => $product,
            'images'      => $images,
            'reviews'     => $reviews,
            'avgRating'   => $avgRating,
            'canReview'   => $canReview,
            'hasReviewed' => $hasReviewed,
        ]);
    }

    public function byCategory(string $slug): void {
        $catModel = new CategoryModel();
        $category = $catModel->findBySlug($slug);
        if (!$category) { http_response_code(404); die('Danh mục không tồn tại.'); }

        $page   = max(1, (int)($_GET['page'] ?? 1));
        $model  = new ProductModel();
        $result = $model->search('', $category['id'], $page);

        $this->render('customer/product/list', [
            'title'      => $category['name'],
            'result'     => $result,
            'categories' => $catModel->all(),
            'keyword'    => '',
            'categoryId' => $category['id'],
        ]);
    }
}
