<?php
// app/controllers/customer/ReviewController.php
require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/app/models/ReviewModel.php';
require_once ROOT_PATH . '/app/models/OrderModel.php';

class ReviewController extends Controller {
    public function store(): void {
        $this->requireLogin();
        verifyCsrf();

        $productId = (int)$this->input('product_id');
        $rating    = (int)$this->input('rating');
        $comment   = trim($this->input('comment'));
        $userId    = (int)$_SESSION['user_id'];

        if ($rating < 1 || $rating > 5) {
            $this->flash('error', 'Đánh giá không hợp lệ.');
            $this->redirect('/products/' . $productId);
            return;
        }

        // FIX: Chỉ cho đánh giá nếu đã mua sản phẩm này (đơn delivered)
        $db        = Database::getInstance();
        $hasBought = $db->prepare("
            SELECT COUNT(*) FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'delivered'
        ");
        $hasBought->execute([$userId, $productId]);
        if (!(int)$hasBought->fetchColumn()) {
            $this->flash('error', 'Bạn chỉ có thể đánh giá sản phẩm sau khi đã nhận hàng.');
            $this->redirect('/products/' . $productId);
            return;
        }

        $model = new ReviewModel();
        if ($model->hasReviewed($userId, $productId)) {
            $this->flash('error', 'Bạn đã đánh giá sản phẩm này rồi.');
            $this->redirect('/products/' . $productId);
            return;
        }

        // FIX: Giới hạn độ dài comment
        if (strlen($comment) > 1000) {
            $this->flash('error', 'Bình luận quá dài (tối đa 1000 ký tự).');
            $this->redirect('/products/' . $productId);
            return;
        }

        $model->insert([
            'user_id'    => $userId,
            'product_id' => $productId,
            'rating'     => $rating,
            'comment'    => $comment,
        ]);

        $this->flash('success', 'Cảm ơn bạn đã đánh giá!');
        $this->redirect('/products/' . $productId);
    }
}
