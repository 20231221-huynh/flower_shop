<?php
// app/controllers/customer/HomeController.php
require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/app/models/ProductModel.php';
require_once ROOT_PATH . '/app/models/CategoryModel.php';
require_once ROOT_PATH . '/app/models/CartModel.php';

class HomeController extends Controller {

    public function index(): void {
        $featured   = (new ProductModel())->getFeatured(8);
        $categories = (new CategoryModel())->all();

        // Cập nhật badge giỏ hàng trong session
        $cartCount = 0;
        if (isLoggedIn()) {
            $cartModel = new CartModel();
            $cartId    = $cartModel->getOrCreate((int)$_SESSION['user_id']);
            $cartCount = $cartModel->countItems($cartId);
        }

        $this->render('customer/home', [
            'title'      => 'Flower Shop – Hoa Tươi Giao Tận Nơi',
            'featured'   => $featured,
            'categories' => $categories,
            'cartCount'  => $cartCount,
        ]);
    }
}
