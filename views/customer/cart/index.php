<?php require ROOT_PATH . '/views/layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="fw-bold mb-0"><i class="bi bi-cart3 text-danger"></i> Giỏ hàng
    <?php if (!empty($items)): ?>
      <span class="badge bg-danger ms-2" style="font-size:.7rem"><?= array_sum(array_column($items, 'quantity')) ?></span>
    <?php endif; ?>
  </h4>
  <a href="<?= BASE_URL ?>/products" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left"></i> Tiếp tục mua hàng
  </a>
</div>

<?php if (empty($items)): ?>
  <div class="text-center py-5">
    <i class="bi bi-cart-x display-1 text-muted opacity-50"></i>
    <h5 class="mt-3 text-muted">Giỏ hàng của bạn đang trống</h5>
    <p class="text-muted small">Hãy chọn những bó hoa đẹp cho người thân yêu!</p>
    <a href="<?= BASE_URL ?>/products" class="btn btn-danger px-4 mt-2">
      <i class="bi bi-flower2"></i> Khám phá sản phẩm
    </a>
  </div>

<?php else: ?>

<div class="row g-4">
  <!-- Danh sách sản phẩm -->
  <div class="col-lg-8">

    <!-- Desktop: table -->
    <div class="card border-0 shadow-sm rounded-4 d-none d-md-block">
      <div class="card-body p-0">
        <table class="table mb-0 align-middle">
          <thead class="table-light rounded-top">
            <tr>
              <th class="ps-4 py-3">Sản phẩm</th>
              <th class="text-center">Đơn giá</th>
              <th class="text-center">Số lượng</th>
              <th class="text-center">Thành tiền</th>
              <th></th>
            </tr>
          </thead>
          <tbody id="cartBody">
            <?php foreach ($items as $item):
              $price       = (float)($item['sale_price'] ?: $item['price']);
              $rowSubtotal = $price * $item['quantity'];
            ?>
            <tr data-id="<?= $item['product_id'] ?>" data-price="<?= $price ?>">
              <td class="ps-4">
                <div class="d-flex align-items-center gap-3">
                  <a href="<?= BASE_URL ?>/products/<?= $item['product_id'] ?>">
                    <img src="<?= e($item['image'] ?? BASE_URL . '/img/no-image.png') ?>"
                         style="width:64px;height:64px;object-fit:cover;border-radius:10px;border:1px solid #eee">
                  </a>
                  <div>
                    <a href="<?= BASE_URL ?>/products/<?= $item['product_id'] ?>"
                       class="text-dark fw-semibold text-decoration-none d-block"><?= e($item['name']) ?></a>
                    <?php if ($item['sale_price']): ?>
                      <small class="text-muted text-decoration-line-through"><?= formatMoney((float)$item['price']) ?></small>
                      <span class="badge bg-danger ms-1" style="font-size:.65rem">Sale</span>
                    <?php endif; ?>
                  </div>
                </div>
              </td>
              <td class="text-center fw-semibold text-danger"><?= formatMoney($price) ?></td>
              <td class="text-center">
                <div class="input-group input-group-sm justify-content-center" style="width:112px;margin:auto">
                  <button class="btn btn-outline-secondary btn-qty" data-action="minus" type="button">−</button>
                  <input type="number" class="form-control text-center qty-input fw-semibold"
                         data-id="<?= $item['product_id'] ?>"
                         value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock'] ?>"
                         style="border-left:0;border-right:0">
                  <button class="btn btn-outline-secondary btn-qty" data-action="plus" type="button">+</button>
                </div>
                <?php if ($item['stock'] <= 5): ?>
                  <div class="text-warning mt-1" style="font-size:.7rem"><i class="bi bi-exclamation-triangle"></i> Còn <?= $item['stock'] ?></div>
                <?php endif; ?>
              </td>
              <td class="text-center fw-bold item-total text-dark"><?= formatMoney($rowSubtotal) ?></td>
              <td class="pe-3">
                <button class="btn btn-sm btn-light text-danger btn-remove-cart rounded-circle"
                        data-id="<?= $item['product_id'] ?>" title="Xóa" style="width:32px;height:32px;padding:0">
                  <i class="bi bi-x-lg" style="font-size:.8rem"></i>
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Mobile: card list -->
    <div class="d-md-none d-flex flex-column gap-3">
      <?php foreach ($items as $item):
        $price = (float)($item['sale_price'] ?: $item['price']);
      ?>
      <div class="card border-0 shadow-sm rounded-4 p-3" data-id="<?= $item['product_id'] ?>" data-price="<?= $price ?>">
        <div class="d-flex gap-3">
          <img src="<?= e($item['image'] ?? BASE_URL . '/img/no-image.png') ?>"
               style="width:72px;height:72px;object-fit:cover;border-radius:10px;flex-shrink:0">
          <div class="flex-fill">
            <div class="fw-semibold small"><?= e($item['name']) ?></div>
            <div class="text-danger fw-bold"><?= formatMoney($price) ?></div>
            <div class="d-flex justify-content-between align-items-center mt-2">
              <div class="input-group input-group-sm" style="width:100px">
                <button class="btn btn-outline-secondary btn-qty" data-action="minus" type="button">−</button>
                <input type="number" class="form-control text-center qty-input"
                       data-id="<?= $item['product_id'] ?>"
                       value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock'] ?>">
                <button class="btn btn-outline-secondary btn-qty" data-action="plus" type="button">+</button>
              </div>
              <div class="fw-bold item-total"><?= formatMoney($price * $item['quantity']) ?></div>
              <button class="btn btn-sm btn-outline-danger btn-remove-cart rounded-pill"
                      data-id="<?= $item['product_id'] ?>">
                <i class="bi bi-trash"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Mã giảm giá -->
    <div class="card border-0 shadow-sm rounded-4 mt-3 p-4">
      <h6 class="fw-bold mb-3"><i class="bi bi-ticket-perforated text-danger"></i> Mã giảm giá</h6>
      <div id="couponStatus">
      <?php if ($coupon): ?>
        <div class="alert alert-success d-flex justify-content-between align-items-center py-2 mb-2">
          <span><i class="bi bi-check-circle"></i> Đang dùng: <strong><?= e($coupon['code']) ?></strong></span>
          <button class="btn btn-sm btn-outline-danger" id="btnRemoveCoupon">Bỏ mã</button>
        </div>
      <?php endif; ?>
      </div>
      <div class="input-group">
        <input type="text" id="couponCode" class="form-control text-uppercase"
               placeholder="Nhập mã giảm giá..." maxlength="20"
               value="<?= e($coupon['code'] ?? '') ?>">
        <button class="btn btn-danger px-4 fw-semibold" id="btnApplyCoupon">
          <i class="bi bi-check2"></i> Áp dụng
        </button>
      </div>
      <div id="coupon-msg" class="mt-2 small"></div>
    </div>
  </div>

  <!-- Tóm tắt -->
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm rounded-4 p-4 sticky-top" style="top:80px">
      <h5 class="fw-bold mb-3">Tóm tắt đơn hàng</h5>

      <div class="d-flex justify-content-between mb-2 text-muted small">
        <span><?= count($items) ?> sản phẩm</span>
        <span id="cart-subtotal" data-value="<?= $subtotal ?>"><?= formatMoney($subtotal) ?></span>
      </div>

      <div class="d-flex justify-content-between mb-2 text-muted small" id="discount-row"
           <?= $discount <= 0 ? 'style="display:none"' : '' ?>>
        <span><i class="bi bi-tag text-success"></i> Giảm giá</span>
        <span class="text-success fw-semibold" id="cart-discount">
          <?= $discount > 0 ? '-' . formatMoney($discount) : '' ?>
        </span>
      </div>

      <div class="d-flex justify-content-between mb-2 text-muted small">
        <span><i class="bi bi-truck text-primary"></i> Phí vận chuyển</span>
        <span class="text-success fw-semibold">Miễn phí</span>
      </div>

      <hr class="my-3">

      <div class="d-flex justify-content-between align-items-center mb-4">
        <span class="fw-bold">Tổng cộng</span>
        <span class="fw-bold fs-4 text-danger" id="cart-total"><?= formatMoney($total) ?></span>
      </div>

      <a href="<?= BASE_URL ?>/checkout" class="btn btn-danger w-100 fw-bold py-2 rounded-3">
        <i class="bi bi-bag-check"></i> Đặt hàng ngay
      </a>
      <div class="text-center mt-3 text-muted" style="font-size:.75rem">
        <i class="bi bi-shield-check text-success"></i> Thanh toán an toàn &nbsp;·&nbsp;
        <i class="bi bi-truck text-primary"></i> Giao hàng tận nơi
      </div>
    </div>
  </div>
</div>

<script>
// Chạy sau khi jQuery đã load (footer load jQuery sau view)
document.addEventListener('DOMContentLoaded', function() {
  // FIX: dùng delegated event vì nút #btnRemoveCoupon có thể được JS chèn động sau khi áp mã thành công
  $(document).on('click', '#btnRemoveCoupon', function() {
    $.post('<?= BASE_URL ?>/cart/coupon', {
      code: '', subtotal: parseFloat($('#cart-subtotal').data('value') || 0),
      csrf_token: $('meta[name="csrf-token"]').attr('content')
    }).always(function() { location.reload(); });
  });
});
</script>

<?php endif; ?>

<?php require ROOT_PATH . '/views/layouts/footer.php'; ?>