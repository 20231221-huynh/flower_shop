<?php require ROOT_PATH . '/views/layouts/admin_header.php'; ?>

<!-- Stat cards -->
<div class="row g-3 mb-4">
  <?php
  $cards = [
    ['Tổng doanh thu',    formatMoney($stats['total_revenue']),  'bi-cash-stack',    '#e63946'],
    ['Tổng đơn hàng',     $stats['total_orders'],                'bi-bag-check',     '#2196f3'],
    ['Chờ xác nhận',      $stats['pending_orders'],              'bi-hourglass-split','#ff9800'],
    ['Khách hàng',        $stats['total_customers'],             'bi-people',        '#4caf50'],
  ];
  foreach ($cards as [$label, $value, $icon, $color]):
  ?>
  <div class="col-md-3 col-6">
    <div class="card border-0 shadow-sm rounded-4 p-3 stat-card h-100">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-circle d-flex align-items-center justify-content-center"
             style="width:48px;height:48px;background:<?= $color ?>20">
          <i class="bi <?= $icon ?> fs-4" style="color:<?= $color ?>"></i>
        </div>
        <div>
          <div class="text-muted small"><?= $label ?></div>
          <div class="fw-bold fs-5"><?= $value ?></div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-4">
  <!-- Biểu đồ doanh thu 7 ngày -->
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm rounded-4 p-4">
      <h6 class="fw-bold mb-3"><i class="bi bi-bar-chart text-danger"></i> Doanh thu 7 ngày gần nhất</h6>
      <?php if (empty($revenueByDay)): ?>
        <p class="text-muted text-center py-4">Chưa có dữ liệu doanh thu.</p>
      <?php else: ?>
        <canvas id="revenueChart" height="100"></canvas>
      <?php endif; ?>
    </div>
  </div>

  <!-- Top sản phẩm -->
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm rounded-4 p-4">
      <h6 class="fw-bold mb-3"><i class="bi bi-trophy text-warning"></i> Top sản phẩm bán chạy</h6>
      <?php if (empty($topProducts)): ?>
        <p class="text-muted small text-center py-3">Chưa có dữ liệu.</p>
      <?php else: ?>
        <?php foreach ($topProducts as $i => $p): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="d-flex align-items-center gap-2">
            <span class="badge rounded-pill" style="background:<?= ['#e63946','#f4a261','#2a9d8f','#457b9d','#6d6875'][$i] ?? '#888' ?>"><?= $i+1 ?></span>
            <span class="small"><?= e($p['name']) ?></span>
          </div>
          <div class="text-end">
            <div class="fw-semibold small"><?= $p['sold'] ?> bán</div>
            <div class="text-muted" style="font-size:.75rem"><?= formatMoney((float)$p['revenue']) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Đơn hàng chờ xác nhận -->
  <?php if (!empty($pendingOrders)): ?>
  <div class="col-12">
    <div class="card border-0 shadow-sm rounded-4">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h6 class="fw-bold mb-0"><i class="bi bi-hourglass-split text-warning"></i> Đơn chờ xác nhận</h6>
          <a href="<?= BASE_URL ?>/admin/orders?status=pending" class="btn btn-sm btn-outline-warning">Xem tất cả</a>
        </div>
        <div class="table-responsive">
          <table class="table table-sm mb-0 align-middle">
            <thead class="table-light">
              <tr><th>Mã đơn</th><th>Khách hàng</th><th>Tổng tiền</th><th>Thanh toán</th><th>Thời gian</th><th></th></tr>
            </thead>
            <tbody>
              <?php foreach ($pendingOrders as $o): ?>
              <tr>
                <td class="fw-bold">#<?= $o['id'] ?></td>
                <td><?= e($o['customer_name']) ?></td>
                <td class="text-danger fw-semibold"><?= formatMoney((float)$o['total_amount']) ?></td>
                <td><?= payMethodLabel($o['pay_method']) ?></td>
                <td class="text-muted small"><?= formatDate($o['created_at']) ?></td>
                <td><a href="<?= BASE_URL ?>/admin/orders/<?= $o['id'] ?>" class="btn btn-xs btn-outline-primary btn-sm">Xem</a></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php if (!empty($revenueByDay)): ?>
<script>
new Chart(document.getElementById('revenueChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_map(fn($r) => date('d/m', strtotime($r['day'])), $revenueByDay)) ?>,
    datasets: [{
      label: 'Doanh thu',
      data: <?= json_encode(array_map(fn($r) => (float)$r['revenue'], $revenueByDay)) ?>,
      backgroundColor: 'rgba(230,57,70,.7)',
      borderRadius: 6,
    }]
  },
  options: {
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { callback: v => (v/1000).toFixed(0)+'K ₫' } } }
  }
});
</script>
<?php endif; ?>

<?php require ROOT_PATH . '/views/layouts/admin_footer.php'; ?>
