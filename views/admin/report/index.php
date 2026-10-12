<?php require ROOT_PATH . '/views/layouts/admin_header.php'; ?>

<!-- Bộ lọc -->
<div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
  <form method="GET" class="row g-3 align-items-end">
    <div class="col-md-4">
      <label class="form-label fw-semibold small">Từ ngày</label>
      <input type="date" name="from" class="form-control form-control-sm" value="<?= e($fromDate) ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label fw-semibold small">Đến ngày</label>
      <input type="date" name="to" class="form-control form-control-sm" value="<?= e($toDate) ?>">
    </div>
    <div class="col-md-4 d-flex gap-2">
      <button type="submit" class="btn btn-danger btn-sm flex-fill">Xem báo cáo</button>
      <a href="?from=<?= date('Y-m-01') ?>&to=<?= date('Y-m-d') ?>" class="btn btn-outline-secondary btn-sm">Tháng này</a>
    </div>
  </form>

  <!-- Nút xuất -->
  <div class="border-top mt-3 pt-3 d-flex gap-2 flex-wrap">
    <span class="small text-muted align-self-center me-1"><i class="bi bi-download"></i> Xuất báo cáo:</span>
    <a href="<?= BASE_URL ?>/admin/reports/export-excel?from=<?= e($fromDate) ?>&to=<?= e($toDate) ?>"
       class="btn btn-sm btn-success">
      <i class="bi bi-file-earmark-excel"></i> Excel (.csv)
    </a>
    <a href="<?= BASE_URL ?>/admin/reports/export-pdf?from=<?= e($fromDate) ?>&to=<?= e($toDate) ?>"
       target="_blank"
       class="btn btn-sm btn-outline-danger">
      <i class="bi bi-file-earmark-pdf"></i> PDF (In trang)
    </a>
  </div>
</div>

<!-- Tổng quan -->
<div class="row g-3 mb-4">
  <?php
  $summaryCards = [
    ['Doanh thu',     formatMoney((float)($summary['total_revenue'] ?? 0)), 'bi-cash-stack',     'danger'],
    ['Tổng đơn',      $summary['total_orders'] ?? 0,                        'bi-bag-check',      'primary'],
    ['Đơn đã TT',     $summary['paid_orders'] ?? 0,                         'bi-check-circle',   'success'],
    ['Đơn bị hủy',    $summary['cancelled_orders'] ?? 0,                    'bi-x-circle',       'warning'],
    ['Khách mua hàng',$summary['total_customers'] ?? 0,                     'bi-people',         'info'],
  ];
  foreach ($summaryCards as [$label, $value, $icon, $color]):
  ?>
  <div class="col-md col-6">
    <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-<?= $color ?> border-4 h-100">
      <p class="text-muted small mb-1"><i class="bi <?= $icon ?>"></i> <?= $label ?></p>
      <h5 class="fw-bold mb-0 text-<?= $color ?>"><?= $value ?></h5>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-4">
  <!-- Biểu đồ -->
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm rounded-4 p-4">
      <h6 class="fw-bold mb-3"><i class="bi bi-bar-chart text-danger"></i> Doanh thu theo ngày</h6>
      <?php if (empty($revenueByDay)): ?>
        <p class="text-muted text-center py-3">Không có dữ liệu trong khoảng thời gian này.</p>
      <?php else: ?>
        <canvas id="revenueChart" height="90"></canvas>
      <?php endif; ?>
    </div>
  </div>

  <!-- Top sản phẩm -->
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm rounded-4 p-4">
      <h6 class="fw-bold mb-3"><i class="bi bi-trophy text-warning"></i> Top 5 bán chạy</h6>
      <?php if (empty($topProducts)): ?>
        <p class="text-muted small text-center py-3">Không có dữ liệu.</p>
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

  <!-- Bảng chi tiết -->
  <?php if (!empty($revenueByDay)): ?>
  <div class="col-12">
    <div class="card border-0 shadow-sm rounded-4">
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th class="ps-3">Ngày</th>
              <th class="text-center">Số đơn</th>
              <th class="text-end pe-3">Doanh thu</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($revenueByDay as $row): ?>
            <tr>
              <td class="ps-3"><?= date('d/m/Y', strtotime($row['day'])) ?></td>
              <td class="text-center"><?= $row['orders'] ?></td>
              <td class="text-end pe-3 fw-semibold text-danger"><?= formatMoney((float)$row['revenue']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot class="table-light">
            <tr>
              <td class="ps-3 fw-bold">Tổng cộng</td>
              <td class="text-center fw-bold"><?= array_sum(array_column($revenueByDay, 'orders')) ?></td>
              <td class="text-end pe-3 fw-bold text-danger"><?= formatMoney(array_sum(array_column($revenueByDay, 'revenue'))) ?></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php if (!empty($revenueByDay)): ?>
<script>
new Chart(document.getElementById('revenueChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode(array_map(fn($r) => date('d/m', strtotime($r['day'])), $revenueByDay)) ?>,
    datasets: [{
      label: 'Doanh thu (₫)',
      data: <?= json_encode(array_map(fn($r) => (float)$r['revenue'], $revenueByDay)) ?>,
      borderColor: '#e63946', backgroundColor: 'rgba(230,57,70,.1)',
      tension: 0.4, fill: true, pointRadius: 4, pointBackgroundColor: '#e63946',
    }]
  },
  options: {
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { callback: v => (v/1000).toFixed(0)+'K' } } }
  }
});
</script>
<?php endif; ?>

<?php require ROOT_PATH . '/views/layouts/admin_footer.php'; ?>