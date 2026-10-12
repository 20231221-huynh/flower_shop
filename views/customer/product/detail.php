<?php require ROOT_PATH . '/views/layouts/header.php'; ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/">Trang chủ</a></li>
    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/products">Sản phẩm</a></li>
    <li class="breadcrumb-item active"><?= e($product['name']) ?></li>
  </ol>
</nav>

<div class="row g-4 mb-5">
  <!-- Ảnh sản phẩm -->
  <div class="col-md-5">
    <div class="rounded-4 overflow-hidden border bg-white" style="height:380px">
      <img src="<?= e($product['primary_image'] ?? BASE_URL . '/img/no-image.png') ?>"
           id="mainImg" class="w-100 h-100" style="object-fit:cover" alt="<?= e($product['name']) ?>">
    </div>
    <?php if (count($images) > 1): ?>
    <div class="d-flex gap-2 mt-2 overflow-auto">
      <?php foreach ($images as $img): ?>
        <img src="<?= e($img['image_url']) ?>" class="rounded border thumb-img"
             style="width:72px;height:72px;object-fit:cover;cursor:pointer"
             onclick="document.getElementById('mainImg').src=this.src">
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Thông tin -->
  <div class="col-md-7">
    <p class="text-muted small mb-1"><?= e($product['category_name'] ?? '') ?></p>
    <h2 class="fw-bold mb-2"><?= e($product['name']) ?></h2>

    <div class="d-flex align-items-center gap-2 mb-3">
      <div class="text-warning">
        <?php for ($i = 1; $i <= 5; $i++): ?>
          <i class="bi bi-star<?= $i <= round($avgRating) ? '-fill' : '' ?>"></i>
        <?php endfor; ?>
      </div>
      <span class="small text-muted"><?= $avgRating ?>/5 (<?= count($reviews) ?> đánh giá)</span>
    </div>

    <div class="mb-3">
      <?php if ($product['sale_price']): ?>
        <span class="fs-3 fw-bold text-danger"><?= formatMoney((float)$product['sale_price']) ?></span>
        <span class="fs-5 text-muted text-decoration-line-through ms-2"><?= formatMoney((float)$product['price']) ?></span>
        <span class="badge bg-danger ms-2">
          -<?= round((1 - $product['sale_price'] / $product['price']) * 100) ?>%
        </span>
      <?php else: ?>
        <span class="fs-3 fw-bold text-danger"><?= formatMoney((float)$product['price']) ?></span>
      <?php endif; ?>
    </div>

    <p class="text-muted"><?= nl2br(e($product['description'] ?? '')) ?></p>

    <div class="d-flex align-items-center gap-2 mb-4">
      <?php if ($product['stock'] > 5): ?>
        <span class="badge bg-success"><i class="bi bi-check-circle"></i> Còn hàng (<?= $product['stock'] ?>)</span>
      <?php elseif ($product['stock'] > 0): ?>
        <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle"></i> Sắp hết (còn <?= $product['stock'] ?>)</span>
      <?php else: ?>
        <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Hết hàng</span>
      <?php endif; ?>
    </div>

    <?php if ($product['stock'] > 0): ?>
    <div class="d-flex gap-3 align-items-center">
      <div class="input-group" style="width:130px">
        <button class="btn btn-outline-secondary" id="btnMinus" type="button">−</button>
        <input type="number" id="qty" class="form-control text-center" value="1"
               min="1" max="<?= $product['stock'] ?>">
        <button class="btn btn-outline-secondary" id="btnPlus" type="button">+</button>
      </div>
      <button class="btn btn-danger px-4 fw-bold btn-add-cart"
              data-id="<?= $product['id'] ?>" data-use-qty="true">
        <i class="bi bi-cart-plus"></i> Thêm vào giỏ
      </button>
    </div>
    <script>
    document.getElementById('btnMinus').onclick = function() {
        var el = document.getElementById('qty');
        if (+el.value > 1) el.value = +el.value - 1;
    };
    document.getElementById('btnPlus').onclick = function() {
        var el = document.getElementById('qty');
        if (+el.value < <?= $product['stock'] ?>) el.value = +el.value + 1;
    };
    </script>
    <?php else: ?>
      <button class="btn btn-secondary px-4" disabled>Hết hàng</button>
    <?php endif; ?>
  </div>
</div>

<!-- Đánh giá -->
<div id="reviews" class="card border-0 shadow-sm rounded-4 p-4 mb-4">
  <h5 class="fw-bold mb-3"><i class="bi bi-chat-square-text text-danger"></i> Đánh giá sản phẩm</h5>

  <?php if (isLoggedIn()): ?>
    <?php if ($canReview ?? false): ?>
    <form method="POST" action="<?= BASE_URL ?>/reviews" class="mb-4 p-3 bg-light rounded-3">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
      <p class="fw-semibold mb-2">Viết đánh giá của bạn</p>
      <div class="mb-2">
        <label class="form-label small">Chất lượng sản phẩm</label>
        <div class="d-flex gap-2">
          <?php for ($i = 5; $i >= 1; $i--): ?>
            <input type="radio" name="rating" value="<?= $i ?>" id="star<?= $i ?>" class="d-none" <?= $i == 5 ? 'checked' : '' ?>>
            <label for="star<?= $i ?>" class="fs-4 text-warning" style="cursor:pointer" title="<?= $i ?> sao">★</label>
          <?php endfor; ?>
        </div>
      </div>
      <div class="mb-2">
        <textarea name="comment" class="form-control" rows="3"
                  placeholder="Chia sẻ cảm nhận về sản phẩm..." maxlength="1000"></textarea>
        <small class="text-muted">Tối đa 1000 ký tự</small>
      </div>
      <button type="submit" class="btn btn-danger btn-sm px-4">Gửi đánh giá</button>
    </form>
    <?php elseif ($hasReviewed ?? false): ?>
      <div class="alert alert-info py-2 small mb-3"><i class="bi bi-check-circle"></i> Bạn đã đánh giá sản phẩm này.</div>
    <?php else: ?>
      <div class="alert alert-secondary py-2 small mb-3">
        <i class="bi bi-info-circle"></i> Chỉ khách hàng đã nhận hàng mới được đánh giá.
      </div>
    <?php endif; ?>
  <?php else: ?>
    <p class="text-muted"><a href="<?= BASE_URL ?>/auth/login" class="text-danger fw-semibold">Đăng nhập</a> để đánh giá sản phẩm.</p>
  <?php endif; ?>

  <?php if (empty($reviews)): ?>
    <p class="text-muted fst-italic">Chưa có đánh giá nào. Hãy là người đầu tiên!</p>
  <?php else: ?>
    <?php foreach ($reviews as $rv): ?>
      <div class="border-top pt-3 mt-3">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <span class="fw-semibold"><?= e($rv['full_name']) ?></span>
            <span class="text-warning ms-2"><?= str_repeat('★', $rv['rating']) ?><span class="text-muted"><?= str_repeat('★', 5 - $rv['rating']) ?></span></span>
          </div>
          <small class="text-muted"><?= formatDate($rv['created_at']) ?></small>
        </div>
        <?php if ($rv['comment']): ?>
          <p class="mb-0 text-muted mt-1"><?= e($rv['comment']) ?></p>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php require ROOT_PATH . '/views/layouts/footer.php'; ?>