<?php require ROOT_PATH . '/views/layouts/admin_header.php'; ?>

<div class="row g-4">
  <!-- Form thêm mã -->
  <div class="col-md-4">
    <div class="card border-0 shadow-sm rounded-4 p-4">
      <h6 class="fw-bold mb-3"><i class="bi bi-plus-circle text-danger"></i> Thêm mã giảm giá</h6>
      <form method="POST" action="<?= BASE_URL ?>/admin/coupons">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

        <div class="mb-3">
          <label class="form-label fw-semibold">Mã coupon <span class="text-danger">*</span></label>
          <input type="text" name="code" class="form-control text-uppercase" required placeholder="VD: SUMMER20">
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Loại giảm giá</label>
          <select name="discount_type" class="form-select" id="discountType">
            <option value="fixed">Giảm tiền cố định (₫)</option>
            <option value="percent">Giảm theo % (%)</option>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold" id="discountLabel">Giá trị giảm (₫)</label>
          <input type="number" name="discount_value" class="form-control" required min="1" placeholder="50000">
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Đơn hàng tối thiểu (₫)</label>
          <input type="number" name="min_order" class="form-control" min="0" value="0">
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Số lần dùng tối đa</label>
          <input type="number" name="max_uses" class="form-control" min="1" placeholder="Để trống = không giới hạn">
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Hạn sử dụng</label>
          <input type="datetime-local" name="expires_at" class="form-control">
        </div>

        <button type="submit" class="btn btn-danger w-100 fw-bold">Tạo mã giảm giá</button>
      </form>
    </div>
  </div>

  <!-- Danh sách mã -->
  <div class="col-md-8">
    <div class="card border-0 shadow-sm rounded-4">
      <div class="table-responsive">
        <table class="table mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th class="ps-3">Mã</th>
              <th>Loại</th>
              <th>Giá trị</th>
              <th>Đã dùng</th>
              <th>Hạn dùng</th>
              <th>Trạng thái</th>
              <th class="text-end pe-3"></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($coupons as $c): ?>
            <tr>
              <td class="ps-3 fw-bold text-danger"><?= e($c['code']) ?></td>
              <td class="small"><?= $c['discount_type'] === 'percent' ? 'Phần trăm' : 'Cố định' ?></td>
              <td class="fw-semibold">
                <?= $c['discount_type'] === 'percent'
                    ? $c['discount_value'] . '%'
                    : formatMoney((float)$c['discount_value']) ?>
              </td>
              <td class="text-muted">
                <?= $c['used_count'] ?><?= $c['max_uses'] ? '/' . $c['max_uses'] : '' ?>
              </td>
              <td class="small text-muted">
                <?= $c['expires_at'] ? date('d/m/Y', strtotime($c['expires_at'])) : 'Không giới hạn' ?>
              </td>
              <td>
                <?= $c['status'] === 'active'
                    ? '<span class="badge bg-success">Hoạt động</span>'
                    : '<span class="badge bg-secondary">Đã tắt</span>' ?>
              </td>
              <td class="text-end pe-3">
                <button class="btn btn-sm btn-outline-danger btn-delete-coupon" data-id="<?= $c['id'] ?>">
                  <i class="bi bi-trash"></i>
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
const BASE_URL = "<?= BASE_URL ?>";
$('#discountType').on('change', function() {
  $('#discountLabel').text($(this).val() === 'percent' ? 'Giá trị giảm (%)' : 'Giá trị giảm (₫)');
});
$(document).on('click', '.btn-delete-coupon', function() {
  if (!confirm('Tắt mã giảm giá này?')) return;
  const id = $(this).data('id');
  $.post(`${BASE_URL}/admin/coupons/${id}/delete`, { csrf_token: '<?= csrfToken() ?>' }, () => location.reload());
});
</script>

<?php require ROOT_PATH . '/views/layouts/admin_footer.php'; ?>
