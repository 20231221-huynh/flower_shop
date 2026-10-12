<?php require ROOT_PATH . '/views/layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="fw-bold mb-0"><i class="bi bi-bag text-danger"></i> Đơn hàng của tôi</h4>
</div>

<!-- Bộ lọc -->
<div class="d-flex flex-wrap gap-2 mb-3">
  <?php
  $filters = [
    '' => ['Tất cả', 'secondary'], 'pending' => ['Chờ xác nhận', 'warning'],
    'confirmed' => ['Đã xác nhận', 'info'], 'shipping' => ['Đang giao', 'primary'],
    'delivered' => ['Đã giao', 'success'], 'cancelled' => ['Đã hủy', 'danger'],
  ];
  $curStatus = $_GET['status'] ?? '';
  foreach ($filters as $val => $item):
    $active = ($curStatus === $val) ? 'btn-' . $item[1] : 'btn-outline-' . $item[1];
  ?>
    <a href="<?= BASE_URL ?>/orders?status=<?= $val ?>" class="btn btn-sm <?= $active ?>"><?= $item[0] ?></a>
  <?php endforeach; ?>
</div>

<?php
// Filter client-side (orders already fetched for user)
$filtered = $curStatus
  ? array_filter($orders, fn($o) => $o['status'] === $curStatus)
  : $orders;
?>

<?php if (empty($filtered)): ?>
  <div class="text-center py-5 text-muted">
    <i class="bi bi-inbox display-5 d-block mb-2"></i>
    Không có đơn hàng nào.
    <div class="mt-3"><a href="<?= BASE_URL ?>/products" class="btn btn-danger btn-sm">Mua sắm ngay</a></div>
  </div>
<?php else: ?>
  <div class="d-flex flex-column gap-3">
    <?php foreach ($filtered as $o): ?>
    <div class="card border-0 shadow-sm rounded-4">
      <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
          <div>
            <span class="fw-bold text-muted small">Đơn #<?= $o['id'] ?></span>
            <span class="ms-2"><?= statusLabel($o['status']) ?></span>
            <span class="ms-1"><?= statusLabel($o['pay_status']) ?></span>
          </div>
          <div class="text-muted small"><?= formatDate($o['created_at']) ?></div>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
          <div>
            <span class="text-muted small"><?= payMethodLabel($o['pay_method']) ?></span>
            <?php if ($o['pay_status'] === 'unpaid' && $o['status'] !== 'cancelled'): ?>
              <a href="<?= BASE_URL ?>/payment/process?order_id=<?= $o['id'] ?>"
                 class="badge bg-warning text-dark ms-2 text-decoration-none">Thanh toán</a>
            <?php endif; ?>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span class="fw-bold text-danger"><?= formatMoney((float)$o['total_amount']) ?></span>
            <a href="<?= BASE_URL ?>/orders/<?= $o['id'] ?>" class="btn btn-sm btn-outline-primary">
              Chi tiết
            </a>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require ROOT_PATH . '/views/layouts/footer.php'; ?>
