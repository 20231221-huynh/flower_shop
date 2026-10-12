<?php require ROOT_PATH . '/views/layouts/admin_header.php'; ?>

<a href="<?= BASE_URL ?>/admin/orders" class="btn btn-sm btn-outline-secondary mb-3">← Quay lại</a>

<div class="row g-4">
  <div class="col-lg-8">
    <!-- Sản phẩm -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-3">
      <h6 class="fw-bold mb-3">Sản phẩm trong đơn</h6>
      <?php foreach ($order['items'] as $item): ?>
        <div class="d-flex justify-content-between py-2 border-bottom">
          <div class="d-flex align-items-center gap-3">
            <img src="<?= e($item['image'] ?? BASE_URL . '/img/no-image.png') ?>"
                 style="width:48px;height:48px;object-fit:cover;border-radius:8px">
            <div>
              <div class="fw-semibold"><?= e($item['product_name']) ?></div>
              <small class="text-muted">x<?= $item['quantity'] ?> × <?= formatMoney((float)$item['price']) ?></small>
            </div>
          </div>
          <span class="fw-bold"><?= formatMoney((float)$item['price'] * $item['quantity']) ?></span>
        </div>
      <?php endforeach; ?>
      <?php if (!empty($order['discount_amount']) && $order['discount_amount'] > 0): ?>
      <div class="d-flex justify-content-between mt-2 text-success">
        <span>Giảm giá</span>
        <span>-<?= formatMoney((float)$order['discount_amount']) ?></span>
      </div>
      <?php endif; ?>
      <div class="d-flex justify-content-between mt-3 fw-bold fs-5">
        <span>Tổng cộng</span>
        <span class="text-danger"><?= formatMoney((float)$order['total_amount']) ?></span>
      </div>
    </div>

    <!-- Thông tin giao hàng -->
    <div class="card border-0 shadow-sm rounded-4 p-4">
      <h6 class="fw-bold mb-3">Thông tin giao hàng & Thanh toán</h6>
      <div class="row g-2">
        <div class="col-md-6">
          <p class="mb-1"><strong>Người nhận:</strong> <?= e($order['receiver_name']) ?></p>
          <p class="mb-1"><strong>SĐT:</strong> <a href="tel:<?= e($order['receiver_phone']) ?>"><?= e($order['receiver_phone']) ?></a></p>
          <p class="mb-1"><strong>Địa chỉ:</strong> <?= e($order['delivery_address']) ?></p>
          <?php if ($order['note']): ?>
            <p class="mb-1"><strong>Ghi chú:</strong> <?= e($order['note']) ?></p>
          <?php endif; ?>
        </div>
        <div class="col-md-6">
          <p class="mb-1"><strong>Phương thức TT:</strong>
            <span class="fw-semibold text-primary"><?= payMethodLabel($order['pay_method']) ?></span>
          </p>
          <p class="mb-1"><strong>Trạng thái TT:</strong> <?= statusLabel($order['pay_status']) ?></p>
          <p class="mb-1"><strong>Ngày đặt:</strong> <?= formatDate($order['created_at']) ?></p>
        </div>
      </div>

      <?php if ($order['pay_status'] === 'unpaid' && $order['status'] !== 'cancelled'): ?>
      <div class="mt-3 pt-2 border-top">
        <button class="btn btn-success btn-sm btn-confirm-paid" data-id="<?= $order['id'] ?>">
          <i class="bi bi-cash-coin"></i> Xác nhận đã nhận tiền
        </button>
      </div>
      <?php endif; ?>

      <?php if ($order['pay_status'] === 'refunded'): ?>
      <div class="mt-3 pt-2 border-top">
        <div class="alert alert-warning py-2 mb-2 small">
          <i class="bi bi-exclamation-triangle-fill"></i>
          <strong>Giao thất bại – Khách đã thanh toán.</strong>
          Cần hoàn <strong><?= formatMoney((float)$order['total_amount']) ?></strong>
          qua <?= payMethodLabel($order['pay_method']) ?>.
        </div>
        <button class="btn btn-warning btn-sm btn-confirm-refund" data-id="<?= $order['id'] ?>">
          <i class="bi bi-arrow-counterclockwise"></i> Xác nhận đã hoàn tiền
        </button>
      </div>
      <?php elseif ($order['pay_status'] === 'refund_done'): ?>
      <div class="mt-3 pt-2 border-top">
        <div class="alert alert-success py-2 small mb-0">
          <i class="bi bi-check-circle-fill"></i>
          <strong>Đã hoàn tiền thành công</strong> cho khách.
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-lg-4">
    <!-- Cập nhật trạng thái -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-3">
      <h6 class="fw-bold mb-3">Cập nhật trạng thái</h6>
      <div class="mb-3">
        <small class="text-muted">Hiện tại</small><br>
        <?= statusLabel($order['status']) ?>
        <span class="ms-2"><?= statusLabel($order['delivery_status']) ?></span>
      </div>
      <form id="formStatus">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <select name="status" class="form-select form-select-sm mb-2">
          <?php foreach (['pending' => 'Chờ xác nhận', 'confirmed' => 'Đã xác nhận', 'shipping' => 'Đang giao', 'delivered' => 'Đã giao', 'cancelled' => 'Đã hủy'] as $val => $label): ?>
            <option value="<?= $val ?>" <?= $order['status'] === $val ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-warning btn-sm w-100">
          <i class="bi bi-arrow-repeat"></i> Cập nhật
        </button>
      </form>
    </div>

    <!-- Phân công shipper -->
    <?php if ($order['status'] !== 'cancelled' && $order['status'] !== 'delivered'): ?>
    <div class="card border-0 shadow-sm rounded-4 p-3">
      <h6 class="fw-bold mb-3">Phân công giao hàng</h6>
      <?php if ($order['shipper_id']): ?>
        <div class="alert alert-info py-2 small mb-2">
          <i class="bi bi-truck"></i> Đã giao cho: <strong><?= e($order['customer_name'] ?? '') ?></strong>
        </div>
      <?php endif; ?>
      <form id="formShipper">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <select name="shipper_id" class="form-select form-select-sm mb-2">
          <option value="">-- Chọn shipper --</option>
          <?php foreach ($shippers as $s): ?>
            <option value="<?= $s['id'] ?>" <?= $order['shipper_id'] == $s['id'] ? 'selected' : '' ?>>
              <?= e($s['full_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (empty($shippers)): ?>
          <p class="text-muted small">Chưa có shipper nào. <a href="<?= BASE_URL ?>/admin/users">Thêm tài khoản shipper</a></p>
        <?php else: ?>
          <button type="submit" class="btn btn-primary btn-sm w-100">
            <i class="bi bi-person-check"></i> Xác nhận giao
          </button>
        <?php endif; ?>
      </form>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
const orderId = <?= (int)$order['id'] ?>;

// Xác nhận đã hoàn tiền
$(document).on('click', '.btn-confirm-refund', function() {
  if (!confirm('Xác nhận đã hoàn tiền thành công cho đơn #' + orderId + '?')) return;
  $.ajax({
    url: BASE_URL + '/admin/orders/' + orderId + '/confirm-refund',
    method: 'POST',
    data: { csrf_token: $('meta[name="csrf-token"]').attr('content') },
    success: function(res) {
      if (res.success) { alert('Đã ghi nhận hoàn tiền!'); location.reload(); }
      else alert('Lỗi: ' + (res.message || 'Không xác định'));
    },
    error: function() { alert('Lỗi kết nối.'); }
  });
});

// Xác nhận đã nhận tiền (chuyển khoản)
$(document).on('click', '.btn-confirm-paid', function() {
  if (!confirm('Xác nhận đã nhận đủ tiền cho đơn #' + orderId + '?')) return;
  $.ajax({
    url: BASE_URL + '/admin/orders/' + orderId + '/confirm-paid',
    method: 'POST',
    data: { csrf_token: $('meta[name="csrf-token"]').attr('content') },
    success: function(res) {
      if (res.success) { alert('Đã cập nhật thanh toán thành công!'); location.reload(); }
      else alert('Lỗi: ' + (res.message || 'Không xác định'));
    },
    error: function() { alert('Lỗi kết nối, vui lòng thử lại.'); }
  });
});

// Cập nhật trạng thái
$('#formStatus').on('submit', function(e) {
  e.preventDefault();
  $.post(BASE_URL + '/admin/orders/' + orderId + '/status', $(this).serialize())
    .done(function(res) {
      if (res.success) { alert('Cập nhật thành công!'); location.reload(); }
      else alert('Lỗi: ' + (res.message || 'Không xác định'));
    })
    .fail(function() { alert('Lỗi kết nối.'); });
});

// Phân công shipper
$('#formShipper').on('submit', function(e) {
  e.preventDefault();
  if (!$(this).find('select').val()) { alert('Vui lòng chọn shipper.'); return; }
  $.post(BASE_URL + '/admin/orders/' + orderId + '/assign', $(this).serialize())
    .done(function(res) {
      if (res.success) { alert('Phân công shipper thành công!'); location.reload(); }
      else alert('Lỗi: ' + (res.message || 'Không xác định'));
    })
    .fail(function() { alert('Lỗi kết nối.'); });
});
</script>

<?php require ROOT_PATH . '/views/layouts/admin_footer.php'; ?>