<?php require ROOT_PATH . '/views/layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="fw-bold mb-0"><i class="bi bi-receipt text-danger"></i> Đơn hàng #<?= $order['id'] ?></h4>
  <a href="<?= BASE_URL ?>/orders" class="btn btn-sm btn-outline-secondary">← Đơn của tôi</a>
</div>

<!-- Trạng thái timeline -->
<?php
$steps   = ['pending' => 'Chờ xác nhận', 'confirmed' => 'Đã xác nhận', 'shipping' => 'Đang giao', 'delivered' => 'Đã giao'];
$current = $order['status'];
$order_cancelled = $current === 'cancelled';
$stepKeys = array_keys($steps);
$currentIdx = array_search($current, $stepKeys);
?>
<?php if (!$order_cancelled): ?>
<div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
  <div class="d-flex justify-content-between align-items-center position-relative px-3">
    <div class="position-absolute top-50 start-0 end-0" style="height:3px;background:#dee2e6;z-index:0;margin:0 40px;transform:translateY(-50%)"></div>
    <?php foreach ($steps as $key => $label):
      $idx  = array_search($key, $stepKeys);
      $done = $currentIdx !== false && $idx <= $currentIdx;
    ?>
    <div class="text-center" style="z-index:1;flex:1">
      <div class="mx-auto rounded-circle d-flex align-items-center justify-content-center fw-bold"
           style="width:36px;height:36px;background:<?= $done ? '#e63946' : '#dee2e6' ?>;color:<?= $done ? '#fff' : '#aaa' ?>">
        <?= $idx + 1 ?>
      </div>
      <div class="small mt-1 <?= $done ? 'text-danger fw-semibold' : 'text-muted' ?>"><?= $label ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php else: ?>
<div class="alert alert-danger"><i class="bi bi-x-circle"></i> Đơn hàng này đã bị hủy.</div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-lg-8">
    <!-- Sản phẩm -->
    <div class="card border-0 shadow-sm rounded-4 mb-3">
      <div class="card-body">
        <h6 class="fw-bold mb-3">Sản phẩm đã đặt</h6>
        <?php foreach ($order['items'] as $item): ?>
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
          <div class="d-flex align-items-center gap-3">
            <img src="<?= e($item['image'] ?? BASE_URL . '/img/no-image.png') ?>"
                 style="width:52px;height:52px;object-fit:cover;border-radius:8px">
            <div>
              <div class="fw-semibold">
                <a href="<?= BASE_URL ?>/products/<?= $item['product_id'] ?>" class="text-dark text-decoration-none">
                  <?= e($item['product_name']) ?>
                </a>
              </div>
              <small class="text-muted">x<?= $item['quantity'] ?> × <?= formatMoney((float)$item['price']) ?></small>
              <?php if ($order['status'] === 'delivered'): ?>
                <div>
                  <a href="<?= BASE_URL ?>/products/<?= $item['product_id'] ?>#reviews"
                     class="small text-danger fw-semibold text-decoration-none">
                    <i class="bi bi-star"></i> Đánh giá sản phẩm
                  </a>
                </div>
              <?php endif; ?>
            </div>
          </div>
          <span class="fw-semibold"><?= formatMoney((float)$item['price'] * $item['quantity']) ?></span>
        </div>
        <?php endforeach; ?>
        <?php if ($order['discount_amount'] > 0): ?>
        <div class="d-flex justify-content-between mt-2 text-success">
          <span>Giảm giá</span><span>-<?= formatMoney((float)$order['discount_amount']) ?></span>
        </div>
        <?php endif; ?>
        <div class="d-flex justify-content-between mt-3 fw-bold fs-5">
          <span>Tổng cộng</span>
          <span class="text-danger"><?= formatMoney((float)$order['total_amount']) ?></span>
        </div>
      </div>
    </div>

    <!-- Địa chỉ -->
    <div class="card border-0 shadow-sm rounded-4">
      <div class="card-body">
        <h6 class="fw-bold mb-3">Thông tin giao hàng</h6>
        <p class="mb-1"><strong>Người nhận:</strong> <?= e($order['receiver_name']) ?></p>
        <p class="mb-1"><strong>SĐT:</strong> <a href="tel:<?= e($order['receiver_phone']) ?>"><?= e($order['receiver_phone']) ?></a></p>
        <p class="mb-1"><strong>Địa chỉ:</strong> <?= e($order['delivery_address']) ?></p>
        <?php if ($order['note']): ?>
          <p class="mb-0"><strong>Ghi chú:</strong> <?= e($order['note']) ?></p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Trạng thái & Hành động -->
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm rounded-4 p-3">
      <h6 class="fw-bold mb-3">Thông tin đơn hàng</h6>
      <div class="mb-2"><small class="text-muted">Trạng thái</small><br><?= statusLabel($order['status']) ?></div>
      <div class="mb-2">
        <small class="text-muted">Thanh toán</small><br>
        <?= statusLabel($order['pay_status']) ?>
        <span class="badge bg-secondary ms-1"><?= payMethodLabel($order['pay_method']) ?></span>
      </div>
      <div class="mb-2"><small class="text-muted">Giao hàng</small><br><?= statusLabel($order['delivery_status'] ?? 'pending') ?></div>
      <div class="mb-3"><small class="text-muted">Ngày đặt</small><br><span><?= formatDate($order['created_at']) ?></span></div>

      <!-- Thanh toán lại nếu chưa thanh toán -->
      <?php if ($order['pay_status'] === 'unpaid' && $order['status'] !== 'cancelled'): ?>
      <a href="<?= BASE_URL ?>/payment/process?order_id=<?= $order['id'] ?>"
         class="btn btn-danger w-100 mb-2 btn-sm">
        <i class="bi bi-credit-card"></i> Thanh toán ngay
      </a>
      <?php endif; ?>

      <!-- Hủy đơn -->
      <?php if ($order['status'] === 'pending'): ?>
      <button class="btn btn-outline-danger w-100 btn-sm btn-cancel-order" data-id="<?= $order['id'] ?>">
        <i class="bi bi-x-circle"></i> Hủy đơn hàng
      </button>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
$('.btn-cancel-order').on('click', function() {
  if (!confirm('Bạn có chắc chắn muốn hủy đơn hàng #<?= $order['id'] ?>?')) return;
  $.ajax({
    url: '<?= BASE_URL ?>/orders/<?= $order['id'] ?>/cancel',
    method: 'POST',
    data: { csrf_token: $('meta[name="csrf-token"]').attr('content') },
    success: function(res) {
      if (res.success) { alert('Đã hủy đơn hàng.'); location.reload(); }
      else alert(res.message || 'Không thể hủy đơn.');
    },
    error: function() { alert('Lỗi kết nối.'); }
  });
});
</script>

<?php require ROOT_PATH . '/views/layouts/footer.php'; ?>