<?php require ROOT_PATH . '/views/layouts/header.php'; ?>

<!-- Hero Banner -->
<div class="rounded-4 overflow-hidden mb-5 position-relative" style="background:linear-gradient(135deg,#c0392b,#e74c3c);min-height:320px">
    <div class="d-flex align-items-center h-100 p-5" style="min-height:320px">
        <div class="text-white">
            <h1 class="display-5 fw-bold">Hoa Tươi – Trao Yêu Thương 🌹</h1>
            <p class="lead mb-4">Đặt hoa online, giao tận nơi nhanh chóng. Hàng nghìn mẫu hoa đẹp cho mọi dịp.</p>
            <a href="<?= BASE_URL ?>/products" class="btn btn-warning btn-lg fw-bold px-4">
                <i class="bi bi-flower2"></i> Chọn hoa ngay
            </a>
        </div>
    </div>
</div>

<!-- Danh mục -->
<?php if (!empty($categories)): ?>
<section class="mb-5">
    <h4 class="fw-bold mb-3"><i class="bi bi-tag text-danger"></i> Danh mục hoa</h4>
    <div class="d-flex flex-wrap gap-2">
        <?php foreach ($categories as $cat): ?>
            <a href="<?= BASE_URL ?>/category/<?= e($cat['slug']) ?>" class="btn btn-outline-danger rounded-pill">
                <?= e($cat['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Sản phẩm nổi bật -->
<section>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0"><i class="bi bi-stars text-danger"></i> Hoa nổi bật</h4>
        <a href="<?= BASE_URL ?>/products" class="btn btn-sm btn-outline-danger">Xem tất cả</a>
    </div>
    <div class="row row-cols-2 row-cols-md-4 g-3">
        <?php foreach ($featured as $p): ?>
            <div class="col">
                <div class="card h-100 border-0 shadow-sm product-card">
                    <a href="<?= BASE_URL ?>/products/<?= $p['id'] ?>">
                        <img src="<?= e($p['primary_image'] ?? BASE_URL . '/img/no-image.png') ?>"
                             class="card-img-top" style="height:200px;object-fit:cover" alt="<?= e($p['name']) ?>">
                    </a>
                    <div class="card-body p-3">
                        <h6 class="card-title mb-1">
                            <a href="<?= BASE_URL ?>/products/<?= $p['id'] ?>" class="text-dark text-decoration-none">
                                <?= e($p['name']) ?>
                            </a>
                        </h6>
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <?php if ($p['sale_price']): ?>
                                    <span class="text-danger fw-bold"><?= formatMoney((float)$p['sale_price']) ?></span>
                                    <small class="text-muted text-decoration-line-through ms-1"><?= formatMoney((float)$p['price']) ?></small>
                                <?php else: ?>
                                    <span class="text-danger fw-bold"><?= formatMoney((float)$p['price']) ?></span>
                                <?php endif; ?>
                            </div>
                            <button class="btn btn-sm btn-danger btn-add-cart"
                                    data-id="<?= $p['id'] ?>" data-name="<?= e($p['name']) ?>">
                                <i class="bi bi-cart-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php require ROOT_PATH . '/views/layouts/footer.php'; ?>
