<?php require ROOT_PATH . '/views/layouts/admin_header.php'; ?>

<!-- Bộ lọc trạng thái -->
<div class="d-flex flex-wrap gap-2 mb-3">
  <?php
  $filters = [
    null        => ['Tất cả',        'secondary'],
    'pending'   => ['Chờ xác nhận',  'warning'],
    'confirmed' => ['Đã xác nhận',   'info'],
    'shipping'  => ['Đang giao',     'primary'],
    'delivered' => ['Đã giao',       'success'],
    'cancelled' => ['Đã hủy',        'danger'],
    'refunded'  => ['⚠️ Cần hoàn tiền','warning'],
  ];
  foreach ($filters as $val => $item):
    $active = ($status === $val) ? 'btn-' . $item[1] : 'btn-outline-' . $item[1];
    $href   = $val ? '?status=' . $val : '?';
  ?>
    <a href="<?= BASE_URL ?>/admin/orders<?= $href ?>" class="btn btn-sm <?= $active ?>">
      <?= $item[0] ?>
    </a>
  <?php endforeach; ?>
</div>

<div class="card border-0 shadow-sm rounded-4">
  <div class="table-responsive">
    <table class="table mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th class="ps-3">Mã đơn</th>
          <th>Khách hàng</th>
          <th>Tổng tiền</th>
          <th>Thanh toán</th>
          <th>Trạng thái</th>
          <th>Giao hàng</th>
          <th>Ngày đặt</th>
          <th class="text-end pe-3">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($result['data'])): ?>
          <?php foreach ($result['data'] as $o): ?>
          <tr>
            <td class="ps-3 fw-bold">#<?= $o['id'] ?></td>
            <td><?= e($o['customer_name']) ?></td>
            <td class="fw-semibold text-danger"><?= formatMoney((float)$o['total_amount']) ?></td>
            <td>
              <?= statusLabel($o['pay_status']) ?>
              <br><small class="text-muted"><?= payMethodLabel($o['pay_method']) ?></small>
            </td>
            <td><?= statusLabel($o['status']) ?></td>
            <td><?= statusLabel($o['delivery_status'] ?? 'pending') ?></td>
            <td class="text-muted small"><?= formatDate($o['created_at']) ?></td>
            <td class="text-end pe-3">
              <a href="<?= BASE_URL ?>/admin/orders/<?= $o['id'] ?>"
                 class="btn btn-sm btn-outline-primary">
                <i class="bi bi-eye"></i> Chi tiết
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="8" class="text-center py-5 text-muted">
              <i class="bi bi-inbox display-6 d-block mb-2"></i>
              Không có đơn hàng nào.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Phân trang -->
<?php if (($result['last_page'] ?? 1) > 1): ?>
<nav class="mt-3">
  <ul class="pagination justify-content-center pagination-sm">
    <?php for ($i = 1; $i <= $result['last_page']; $i++): ?>
      <li class="page-item <?= $i == $result['current_page'] ? 'active' : '' ?>">
        <a class="page-link" href="?<?= $status ? 'status=' . $status . '&' : '' ?>page=<?= $i ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>

<?php require ROOT_PATH . '/views/layouts/admin_footer.php'; ?>