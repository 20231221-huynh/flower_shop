<?php require ROOT_PATH . '/views/layouts/header.php'; ?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="fw-bold mb-0"><i class="bi bi-truck text-danger"></i> Đơn hàng của tôi</h4>
  <button class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
    <i class="bi bi-arrow-clockwise"></i> Làm mới
  </button>
</div>

<!-- Tabs -->
<ul class="nav nav-pills mb-3 gap-1">
  <?php
  $tabs = [
    'active' => ['Đang giao',  'danger',    'bi-truck',       $counts['active']],
    'done'   => ['Đã xử lý',  'success',   'bi-check-circle',$counts['done']],
    'all'    => ['Tất cả',    'secondary', 'bi-list-ul',     $counts['active'] + $counts['done']],
  ];
  foreach ($tabs as $key => [$label, $color, $icon, $count]):
    $active = $tab === $key;
  ?>
  <li class="nav-item">
    <a href="?tab=<?= $key ?>"
       class="nav-link <?= $active ? "active bg-$color border-0" : "text-$color border border-$color" ?>">
      <i class="bi <?= $icon ?>"></i> <?= $label ?>
      <span class="badge <?= $active ? 'bg-white text-'.$color : 'bg-'.$color.' text-white' ?> ms-1">
        <?= $count ?>
      </span>
    </a>
  </li>
  <?php endforeach; ?>
</ul>

<?php if (empty($orders)): ?>
  <div class="text-center py-5 text-muted">
    <i class="bi bi-inbox display-4 d-block mb-3 opacity-50"></i>
    <?php if ($tab === 'active'): ?>
      <p class="fw-semibold">Không có đơn nào đang giao.</p>
      <p class="small">Admin chưa phân công thêm đơn cho bạn.</p>
    <?php elseif ($tab === 'done'): ?>
      <p class="fw-semibold">Chưa có đơn nào hoàn thành.</p>
    <?php else: ?>
      <p class="fw-semibold">Chưa có đơn nào được phân công.</p>
    <?php endif; ?>
  </div>

<?php else: ?>

<!-- Thống kê nhanh (chỉ hiện ở tab done/all) -->
<?php if ($tab !== 'active' && !empty($orders)): ?>
<?php
  $delivered = count(array_filter($orders, fn($o) => $o['delivery_status'] === 'delivered'));
  $failed    = count(array_filter($orders, fn($o) => $o['delivery_status'] === 'failed'));
  $revenue   = array_sum(array_map(
    fn($o) => $o['delivery_status'] === 'delivered' && $o['pay_method'] === 'cod' ? (float)$o['total_amount'] : 0,
    $orders
  ));
?>
<div class="row g-2 mb-3">
  <div class="col-4">
    <div class="card border-0 bg-success bg-opacity-10 rounded-3 p-2 text-center">
      <div class="fw-bold text-success fs-5"><?= $delivered ?></div>
      <div class="text-muted" style="font-size:.75rem">Giao thành công</div>
    </div>
  </div>
  <div class="col-4">
    <div class="card border-0 bg-danger bg-opacity-10 rounded-3 p-2 text-center">
      <div class="fw-bold text-danger fs-5"><?= $failed ?></div>
      <div class="text-muted" style="font-size:.75rem">Giao thất bại</div>
    </div>
  </div>
  <div class="col-4">
    <div class="card border-0 bg-warning bg-opacity-10 rounded-3 p-2 text-center">
      <div class="fw-bold text-warning fs-5"><?= number_format($revenue/1000) ?>K</div>
      <div class="text-muted" style="font-size:.75rem">Thu COD (₫)</div>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="d-flex flex-column gap-3">
  <?php foreach ($orders as $o):
    $isDone      = in_array($o['delivery_status'], ['delivered', 'failed']);
    $isDelivered = $o['delivery_status'] === 'delivered';
    $isFailed    = $o['delivery_status'] === 'failed';

    // Border màu theo trạng thái
    $borderColor = match(true) {
      $isDelivered => '#198754',
      $isFailed    => '#dc3545',
      $o['delivery_status'] === 'shipping' => '#0d6efd',
      default      => '#ffc107',
    };
  ?>
  <div class="card border-0 shadow-sm rounded-4 overflow-hidden"
       style="border-left: 4px solid <?= $borderColor ?> !important; border-left-width: 4px !important">
    <div class="card-body p-3">

      <!-- Row 1: ID + trạng thái + thời gian -->
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="d-flex align-items-center gap-2">
          <span class="fw-bold text-muted small">#<?= $o['id'] ?></span>
          <?php
          $dsLabel = match($o['delivery_status']) {
            'assigned'          => '<span class="badge bg-warning text-dark"><i class="bi bi-hourglass"></i> Chờ lấy hàng</span>',
            'shipping'          => '<span class="badge bg-primary"><i class="bi bi-truck"></i> Đang giao</span>',
            'delivered'         => '<span class="badge bg-success"><i class="bi bi-check-all"></i> Đã giao</span>',
            'failed_no_contact' => '<span class="badge bg-dark">📵 Không nghe máy</span>',
            'failed_address'    => '<span class="badge bg-dark">🏠 Sai địa chỉ</span>',
            'failed_rejected'   => '<span class="badge bg-dark">↩ KH trả hàng</span>',
            default             => '<span class="badge bg-secondary">' . e($o['delivery_status']) . '</span>',
          };
          echo $dsLabel;
          ?>
          <?php if ($o['pay_method'] === 'cod' && !$isDone): ?>
            <span class="badge bg-warning text-dark" style="font-size:.65rem">💵 Thu tiền mặt</span>
          <?php endif; ?>
          <?php if ($isFailed && $o['pay_status'] === 'refunded'): ?>
            <span class="badge bg-danger" style="font-size:.65rem">⚠️ Cần hoàn tiền</span>
          <?php endif; ?>
        </div>
        <span class="text-muted" style="font-size:.72rem"><?= formatDate($o['created_at']) ?></span>
      </div>

      <!-- Row 2: Thông tin KH -->
      <div class="d-flex align-items-start gap-2 mb-1">
        <i class="bi bi-person-fill text-muted mt-1" style="font-size:.85rem;flex-shrink:0"></i>
        <div>
          <span class="fw-semibold small"><?= e($o['receiver_name']) ?></span>
          <a href="tel:<?= e($o['receiver_phone']) ?>"
             class="btn btn-sm btn-outline-success py-0 px-2 ms-2 rounded-pill"
             style="font-size:.72rem">
            <i class="bi bi-telephone-fill"></i> <?= e($o['receiver_phone']) ?>
          </a>
        </div>
      </div>

      <div class="d-flex align-items-start gap-2 mb-2">
        <i class="bi bi-geo-alt-fill text-danger mt-1" style="font-size:.85rem;flex-shrink:0"></i>
        <span class="small text-muted"><?= e($o['delivery_address']) ?></span>
      </div>

      <?php if ($o['note']): ?>
      <div class="d-flex align-items-start gap-2 mb-2">
        <i class="bi bi-chat-left-text-fill text-warning mt-1" style="font-size:.85rem;flex-shrink:0"></i>
        <span class="small fst-italic text-muted"><?= e($o['note']) ?></span>
      </div>
      <?php endif; ?>

      <!-- Row 3: Tiền + nút hành động -->
      <div class="d-flex justify-content-between align-items-center pt-2 border-top">
        <div>
          <span class="fw-bold text-danger"><?= formatMoney((float)$o['total_amount']) ?></span>
          <span class="text-muted small ms-1"><?= payMethodLabel($o['pay_method']) ?></span>
        </div>

        <?php if (!$isDone): ?>
        <!-- Đơn đang giao: hiện nút hành động -->
        <div class="d-flex gap-2">
          <button class="btn btn-success btn-sm px-3 btn-delivered fw-semibold"
                  data-id="<?= $o['id'] ?>">
            <i class="bi bi-check2-all"></i> Đã giao
          </button>
          <button class="btn btn-outline-danger btn-sm px-3 btn-fail-open"
                  data-id="<?= $o['id'] ?>"
                  data-bs-toggle="modal" data-bs-target="#modalFail">
            <i class="bi bi-x-circle"></i> Thất bại
          </button>
        </div>

        <?php elseif ($isDelivered): ?>
        <!-- Đã giao thành công -->
        <span class="text-success small fw-semibold">
          <i class="bi bi-patch-check-fill"></i> Giao thành công
        </span>

        <?php else: ?>
        <!-- Giao thất bại -->
        <span class="text-danger small fw-semibold">
          <i class="bi bi-x-circle-fill"></i> Giao thất bại
        </span>
        <?php endif; ?>
      </div>

    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php endif; ?>

<!-- Modal chọn lý do thất bại -->
<div class="modal fade" id="modalFail" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 rounded-4 shadow">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold text-danger">
          <i class="bi bi-x-circle"></i> Giao hàng thất bại
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-2">
        <p class="text-muted small mb-3">Đơn hàng <strong id="failOrderId"></strong> — Chọn lý do:</p>
        <input type="hidden" id="failOrderIdVal">

        <div class="d-flex flex-column gap-2">
          <label class="fail-option border rounded-3 p-3 d-flex align-items-center gap-3"
                 style="cursor:pointer" data-reason="failed_no_contact">
            <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0"
                 style="width:44px;height:44px;font-size:1.3rem">📵</div>
            <div>
              <div class="fw-semibold">Khách không nghe máy</div>
              <div class="text-muted small">Đã gọi nhiều lần nhưng không liên lạc được</div>
            </div>
          </label>

          <label class="fail-option border rounded-3 p-3 d-flex align-items-center gap-3"
                 style="cursor:pointer" data-reason="failed_address">
            <div class="rounded-circle bg-danger bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0"
                 style="width:44px;height:44px;font-size:1.3rem">🏠</div>
            <div>
              <div class="fw-semibold">Sai địa chỉ giao hàng</div>
              <div class="text-muted small">Không tìm được địa chỉ hoặc địa chỉ không tồn tại</div>
            </div>
          </label>

          <label class="fail-option border rounded-3 p-3 d-flex align-items-center gap-3"
                 style="cursor:pointer" data-reason="failed_rejected">
            <div class="rounded-circle bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0"
                 style="width:44px;height:44px;font-size:1.3rem">↩</div>
            <div>
              <div class="fw-semibold">Khách từ chối nhận hàng</div>
              <div class="text-muted small">Khách không muốn nhận hoặc đổi ý</div>
            </div>
          </label>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
        <button class="btn btn-danger px-4 fw-semibold" id="btnConfirmFail" disabled>
          <i class="bi bi-check-lg"></i> Xác nhận thất bại
        </button>
      </div>
    </div>
  </div>
</div>

<style>
.fail-option { transition: all .15s; user-select: none; }
.fail-option:hover { border-color: #dc3545 !important; background: #fff5f5; }
.fail-option.selected { border-color: #dc3545 !important; background: #fff5f5; box-shadow: 0 0 0 2px #dc354540; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const CSRF = $('meta[name="csrf-token"]').attr('content');
  let selectedReason = null;

  function postStatus(id, data) {
    data.csrf_token = CSRF;
    $.post(`${BASE_URL}/shipper/orders/${id}/status`, data)
      .done(function(res) {
        if (res.success) {
          if (res.need_refund) {
            // Hiện thông báo nổi bật trước khi redirect
            const msg = document.createElement('div');
            msg.innerHTML = `<div style="position:fixed;top:80px;left:50%;transform:translateX(-50%);z-index:9999;
              background:#dc3545;color:#fff;padding:14px 24px;border-radius:12px;font-size:14px;
              font-weight:500;box-shadow:0 4px 20px rgba(0,0,0,.25);max-width:90vw;text-align:center">
              ⚠️ Đơn đã thanh toán trước — Admin sẽ xử lý hoàn tiền cho khách.
            </div>`;
            document.body.appendChild(msg);
            setTimeout(() => { window.location.href = `${BASE_URL}/shipper?tab=done`; }, 2500);
          } else {
            window.location.href = `${BASE_URL}/shipper?tab=done`;
          }
        } else {
          alert(res.message || 'Có lỗi xảy ra.');
        }
      })
      .fail(function() { alert('Lỗi kết nối, vui lòng thử lại.'); });
  }

  // Đã giao thành công
  $(document).on('click', '.btn-delivered', function() {
    const id  = $(this).data('id');
    const btn = $(this);
    if (!confirm(`Xác nhận đã giao thành công đơn #${id}?`)) return;
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
    postStatus(id, { delivery_status: 'delivered' });
  });

  // Mở modal thất bại — lưu order id
  $(document).on('click', '.btn-fail-open', function() {
    const id = $(this).data('id');
    $('#failOrderId').text('#' + id);
    $('#failOrderIdVal').val(id);
    // Reset lựa chọn
    selectedReason = null;
    $('.fail-option').removeClass('selected');
    $('#btnConfirmFail').prop('disabled', true);
  });

  // Chọn lý do
  $(document).on('click', '.fail-option', function() {
    $('.fail-option').removeClass('selected');
    $(this).addClass('selected');
    selectedReason = $(this).data('reason');
    $('#btnConfirmFail').prop('disabled', false);
  });

  // Xác nhận thất bại
  $('#btnConfirmFail').on('click', function() {
    if (!selectedReason) return;
    const id = $('#failOrderIdVal').val();
    $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Đang xử lý...');
    bootstrap.Modal.getInstance(document.getElementById('modalFail')).hide();
    postStatus(id, { delivery_status: 'failed', reason: selectedReason });
  });
});
</script>

<?php require ROOT_PATH . '/views/layouts/footer.php'; ?>