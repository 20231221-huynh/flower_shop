</div><!-- /container -->
</main>

<footer class="bg-dark text-light py-4 mt-5">
  <div class="container">
    <div class="row">
      <div class="col-md-4">
        <h6 class="fw-bold"><i class="bi bi-flower1"></i> Flower Shop</h6>
        <p class="small text-white-50">Hoa tươi – Tình yêu tươi mới. Giao hoa tận nơi tại TP.HCM.</p>
      </div>
      <div class="col-md-4">
        <h6 class="fw-bold">Liên kết</h6>
        <ul class="list-unstyled small">
          <li><a href="<?= BASE_URL ?>/" class="text-white-50 text-decoration-none">Trang chủ</a></li>
          <li><a href="<?= BASE_URL ?>/products" class="text-white-50 text-decoration-none">Sản phẩm</a></li>
          <li><a href="<?= BASE_URL ?>/orders" class="text-white-50 text-decoration-none">Đơn hàng</a></li>
        </ul>
      </div>
      <div class="col-md-4">
        <h6 class="fw-bold">Liên hệ</h6>
        <p class="small text-white-50">
          <i class="bi bi-telephone"></i> 0876831903<br>
          <i class="bi bi-envelope"></i> pqvan@flowershop.vn
        </p>
      </div>
    </div>
    <hr class="border-secondary">
    <p class="text-center text-muted small mb-0">&copy; <?= date('Y') ?> Flower Shop. All rights reserved.</p>
  </div>
</footer>

<!-- FIX: Chỉ load Bootstrap + jQuery 1 lần, bỏ bản jQuery trùng lặp ở cuối -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="<?= BASE_URL ?>/js/main.js?v=<?= filemtime(ROOT_PATH . '/public/js/main.js') ?>"></script>
</body>
</html>