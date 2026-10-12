<?php
// app/controllers/customer/CartController.php
require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/app/models/CartModel.php';
require_once ROOT_PATH . '/app/models/ProductModel.php';
require_once ROOT_PATH . '/app/models/CouponModel.php';

class CartController extends Controller {

    // FIX: giới hạn số lượng tối đa 1 sản phẩm và số loại sản phẩm khác nhau trong giỏ
    private const MAX_QTY_PER_PRODUCT = 100;
    private const MAX_DISTINCT_ITEMS  = 20;

    private function getCartId(): int {
        $this->requireLogin();
        return (new CartModel())->getOrCreate((int)$_SESSION['user_id']);
    }

    public function index(): void {
        $this->requireLogin();
        $cartModel = new CartModel();
        $cartId    = $cartModel->getOrCreate((int)$_SESSION['user_id']);
        $items     = $cartModel->getItems($cartId);
        $subtotal  = array_sum(array_map(fn($i) => ($i['sale_price'] ?: $i['price']) * $i['quantity'], $items));

        $coupon   = $_SESSION['coupon'] ?? null;
        $discount = 0;
        if ($coupon) {
            $couponModel = new CouponModel();
            if ($couponModel->isValid($coupon, $subtotal)) {
                $discount = $couponModel->calcDiscount($coupon, $subtotal);
            } else {
                unset($_SESSION['coupon']);
                $coupon   = null;
            }
        }

        $this->render('customer/cart/index', [
            'title'    => 'Giỏ hàng',
            'items'    => $items,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total'    => $subtotal - $discount,
            'coupon'   => $coupon,
        ]);
    }

    // FIX: Thêm endpoint GET /cart/count cho JS badge
    public function count(): void {
        if (!isLoggedIn()) {
            $this->json(['count' => 0]);
            return;
        }
        $cartModel = new CartModel();
        $cartId    = $cartModel->getOrCreate((int)$_SESSION['user_id']);
        $this->json(['count' => $cartModel->countItems($cartId)]);
    }

    public function add(): void {
        $this->requireLogin();
        $productId = (int)$this->input('product_id');
        $qty       = max(1, (int)$this->input('quantity', 1));

        $productModel = new ProductModel();
        $product      = $productModel->find($productId);

        if (!$product || $product['status'] !== 'active') {
            $this->json(['success' => false, 'message' => 'Sản phẩm không tồn tại.'], 404);
            return;
        }
        if ($product['stock'] < $qty) {
            $this->json(['success' => false, 'message' => "Chỉ còn {$product['stock']} sản phẩm trong kho."], 400);
            return;
        }

        $cartModel = new CartModel();
        $cartId    = $cartModel->getOrCreate((int)$_SESSION['user_id']);

        $existingQty = $cartModel->getItemQty($cartId, $productId);
        if ($existingQty + $qty > $product['stock']) {
            $this->json(['success' => false, 'message' => "Giỏ hàng đã có $existingQty sản phẩm. Tồn kho chỉ còn {$product['stock']}."], 400);
            return;
        }

        // FIX: giới hạn tối đa self::MAX_QTY_PER_PRODUCT sản phẩm cho 1 loại
        if ($existingQty + $qty > self::MAX_QTY_PER_PRODUCT) {
            $this->json(['success' => false, 'message' => 'Số lượng tối đa cho 1 sản phẩm là ' . self::MAX_QTY_PER_PRODUCT . '.'], 400);
            return;
        }

        // FIX: giới hạn tối đa self::MAX_DISTINCT_ITEMS loại sản phẩm trong giỏ
        // (chỉ chặn khi thêm MỚI 1 loại chưa có trong giỏ, sản phẩm đã có sẵn thì vẫn cho tăng số lượng)
        if ($existingQty === 0 && $cartModel->countDistinctItems($cartId) >= self::MAX_DISTINCT_ITEMS) {
            $this->json(['success' => false, 'message' => 'Giỏ hàng chỉ chứa tối đa ' . self::MAX_DISTINCT_ITEMS . ' loại sản phẩm.'], 400);
            return;
        }

        $cartModel->addItem($cartId, $productId, $qty);
        $count = $cartModel->countItems($cartId);
        $this->json(['success' => true, 'cart_count' => $count, 'message' => 'Đã thêm vào giỏ hàng!']);
    }

    public function update(): void {
        $this->requireLogin();
        $productId = (int)$this->input('product_id');
        $qty       = (int)$this->input('quantity');

        $product = (new ProductModel())->find($productId);
        if ($product && $qty > $product['stock']) {
            $this->json(['success' => false, 'message' => "Tồn kho chỉ còn {$product['stock']} sản phẩm."], 400);
            return;
        }

        // FIX: giới hạn tối đa self::MAX_QTY_PER_PRODUCT khi chỉnh số lượng trong giỏ
        if ($qty > self::MAX_QTY_PER_PRODUCT) {
            $this->json(['success' => false, 'message' => 'Số lượng tối đa cho 1 sản phẩm là ' . self::MAX_QTY_PER_PRODUCT . '.'], 400);
            return;
        }

        $cartModel = new CartModel();
        $cartId    = $cartModel->getOrCreate((int)$_SESSION['user_id']);
        $cartModel->updateItem($cartId, $productId, $qty);

        $items    = $cartModel->getItems($cartId);
        $subtotal = array_sum(array_map(fn($i) => ($i['sale_price'] ?: $i['price']) * $i['quantity'], $items));
        $count    = $cartModel->countItems($cartId);

        $this->json(['success' => true, 'subtotal' => $subtotal, 'cart_count' => $count]);
    }

    public function remove(): void {
        $this->requireLogin();
        $productId = (int)$this->input('product_id');
        $cartModel = new CartModel();
        $cartId    = $cartModel->getOrCreate((int)$_SESSION['user_id']);
        $cartModel->removeItem($cartId, $productId);
        $count = $cartModel->countItems($cartId);
        $this->json(['success' => true, 'cart_count' => $count]);
    }

    public function applyCoupon(): void {
        $this->requireLogin();
        $code        = strtoupper(trim($this->input('code')));
        $subtotal    = (float)$this->input('subtotal');
        $couponModel = new CouponModel();
        $coupon      = $couponModel->findByCode($code);

        if (!$coupon || !$couponModel->isValid($coupon, $subtotal)) {
            unset($_SESSION['coupon']);
            $this->json(['success' => false, 'message' => 'Mã giảm giá không hợp lệ hoặc đã hết hạn.']);
            return;
        }

        $_SESSION['coupon'] = $coupon;
        $discount = $couponModel->calcDiscount($coupon, $subtotal);
        $this->json([
            'success'  => true,
            'discount' => $discount,
            'total'    => max(0, $subtotal - $discount),
            'message'  => 'Áp dụng mã thành công! Giảm ' . number_format($discount, 0, ',', '.') . ' ₫',
        ]);
    }
}