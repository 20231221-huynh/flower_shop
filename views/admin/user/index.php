<?php require ROOT_PATH . '/views/layouts/admin_header.php'; ?>

<!-- Tìm kiếm + lọc -->
<div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
  <form class="d-flex gap-2" method="GET">
    <input type="text" name="q" class="form-control form-control-sm" style="width:200px"
           placeholder="Tên, email, SĐT..." value="<?= e($keyword) ?>">
    <select name="role" class="form-select form-select-sm" style="width:140px">
      <option value="">Tất cả vai trò</option>
      <option value="customer" <?= $role === 'customer' ? 'selected' : '' ?>>Khách hàng</option>
      <option value="shipper"  <?= $role === 'shipper'  ? 'selected' : '' ?>>Shipper</option>
      <option value="admin"    <?= $role === 'admin'    ? 'selected' : '' ?>>Admin</option>
    </select>
    <button class="btn btn-sm btn-danger">Lọc</button>
    <a href="<?= BASE_URL ?>/admin/users" class="btn btn-sm btn-outline-secondary">Xóa lọc</a>
  </form>
  <span class="text-muted small ms-auto">Tổng: <strong><?= $result['total'] ?></strong> người dùng</span>
</div>

<div class="card border-0 shadow-sm rounded-4">
  <div class="table-responsive">
    <table class="table mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th class="ps-3">ID</th>
          <th>Họ tên</th>
          <th>Email</th>
          <th>Điện thoại</th>
          <th>Vai trò</th>
          <th>Trạng thái</th>
          <th>Ngày tạo</th>
          <th class="text-end pe-3">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($result['data'] as $u): ?>
        <tr>
          <td class="ps-3 text-muted small">#<?= $u['id'] ?></td>
          <td class="fw-semibold"><?= e($u['full_name']) ?></td>
          <td class="text-muted small"><?= e($u['email']) ?></td>
          <td class="text-muted small"><?= e($u['phone'] ?? '–') ?></td>
          <td>
            <?php $roleMap = ['admin' => ['bg-danger', 'Admin'], 'shipper' => ['bg-primary', 'Shipper'], 'customer' => ['bg-secondary', 'Khách hàng']]; ?>
            <span class="badge <?= $roleMap[$u['role']][0] ?? 'bg-secondary' ?>"><?= $roleMap[$u['role']][1] ?? $u['role'] ?></span>
          </td>
          <td>
            <?php if ($u['status'] === 'active'): ?>
              <span class="badge bg-success">Hoạt động</span>
            <?php elseif ($u['status'] === 'inactive'): ?>
              <span class="badge bg-warning text-dark">Vô hiệu</span>
            <?php else: ?>
              <span class="badge bg-danger">Bị khóa</span>
            <?php endif; ?>
          </td>
          <td class="text-muted small"><?= isset($u['created_at']) ? date('d/m/Y', strtotime($u['created_at'])) : '–' ?></td>
          <td class="text-end pe-3">
            <?php if ($u['id'] != $_SESSION['user_id'] && $u['role'] !== 'admin'): ?>
            <div class="dropdown">
              <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">Trạng thái</button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item btn-user-status" href="#" data-id="<?= $u['id'] ?>" data-status="active">✅ Kích hoạt</a></li>
                <li><a class="dropdown-item btn-user-status" href="#" data-id="<?= $u['id'] ?>" data-status="inactive">⏸ Vô hiệu</a></li>
                <li><a class="dropdown-item text-danger btn-user-status" href="#" data-id="<?= $u['id'] ?>" data-status="banned">🚫 Khóa TK</a></li>
              </ul>
            </div>
            <?php elseif ($u['id'] == $_SESSION['user_id']): ?>
              <span class="text-muted small fst-italic">Tài khoản của bạn</span>
            <?php else: ?>
              <span class="text-muted small">–</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($result['data'])): ?>
        <tr><td colspan="8" class="text-center py-5 text-muted">Không tìm thấy người dùng nào.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($result['last_page'] > 1): ?>
<nav class="mt-3"><ul class="pagination justify-content-center pagination-sm">
  <?php for ($i = 1; $i <= $result['last_page']; $i++): ?>
    <li class="page-item <?= $i == $result['current_page'] ? 'active' : '' ?>">
      <a class="page-link" href="?q=<?= urlencode($keyword) ?>&role=<?= urlencode($role ?? '') ?>&page=<?= $i ?>"><?= $i ?></a>
    </li>
  <?php endfor; ?>
</ul></nav>
<?php endif; ?>

<script>
$(document).on('click', '.btn-user-status', function(e) {
  e.preventDefault();
  const id     = $(this).data('id');
  const status = $(this).data('status');
  const label  = { active: 'kích hoạt', inactive: 'vô hiệu hoá', banned: 'khóa' }[status];
  if (!confirm(`Xác nhận ${label} tài khoản này?`)) return;
  $.post(`${BASE_URL}/admin/users/${id}/status`,
    { status, csrf_token: $('meta[name="csrf-token"]').attr('content') })
    .done(res => { if (res.success) location.reload(); else alert(res.message); })
    .fail(() => alert('Lỗi kết nối.'));
});
</script>

<?php require ROOT_PATH . '/views/layouts/admin_footer.php'; ?>
