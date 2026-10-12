// public/js/admin.js
$(function () {

  function getCSRFToken() {
    return $('input[name="csrf_token"]').first().val() || '';
  }

  // Delete product
  $(document).on('click', '.btn-delete-product', function () {
    if (!confirm('Xóa sản phẩm này?')) return;
    const id = $(this).data('id');
    $.post(`${BASE_URL}/admin/products/${id}/delete`, { csrf_token: getCSRFToken() },
      () => location.reload()
    );
  });

  // Delete category
  $(document).on('click', '.btn-delete-cat', function () {
    if (!confirm('Xóa danh mục "' + $(this).data('name') + '"?')) return;
    $.post(`${BASE_URL}/admin/categories/${$(this).data('id')}/delete`,
      { csrf_token: getCSRFToken() }, () => location.reload()
    );
  });

  // Delete coupon
  $(document).on('click', '.btn-delete-coupon', function () {
    if (!confirm('Tắt mã giảm giá này?')) return;
    $.post(`${BASE_URL}/admin/coupons/${$(this).data('id')}/delete`,
      { csrf_token: getCSRFToken() }, () => location.reload()
    );
  });

  // User status
  $(document).on('click', '.btn-user-status', function (e) {
    e.preventDefault();
    const status = $(this).data('status');
    if (!confirm(`Thay đổi trạng thái thành "${status}"?`)) return;
    $.post(`${BASE_URL}/admin/users/${$(this).data('id')}/status`,
      { status, csrf_token: getCSRFToken() }, () => location.reload()
    );
  });
});
