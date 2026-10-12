// public/js/main.js

$(function () {
  // ── Cập nhật badge giỏ hàng ──────────────────────────────
  function updateCartBadge(count) {
    const badge = $('#cart-badge');
    if (count > 0) {
      badge.text(count).show();
    } else {
      badge.hide();
    }
  }

  // Lấy số lượng giỏ hàng khi load trang
  $.get(BASE_URL + '/cart/count', function (res) {
    if (res && res.count !== undefined) updateCartBadge(res.count);
  }).fail(function() { /* silent fail nếu chưa đăng nhập */ });

  // ── Thêm vào giỏ hàng ────────────────────────────────────
  $(document).on('click', '.btn-add-cart', function () {
    const btn       = $(this);
    const productId = btn.data('id');
    const useQty    = btn.data('use-qty');
    const qty       = useQty ? (parseInt($('#qty').val()) || 1) : 1;

    btn.prop('disabled', true);
    const orig = btn.html();
    btn.html('<span class="spinner-border spinner-border-sm"></span>');

    $.ajax({
      url    : BASE_URL + '/cart/add',
      method : 'POST',
      data   : {
        product_id  : productId,
        quantity    : qty,
        csrf_token  : $('meta[name="csrf-token"]').attr('content'),
      },
      success: function (res) {
        if (res.success) {
          updateCartBadge(res.cart_count);
          btn.html('<i class="bi bi-check-lg"></i> Đã thêm!').addClass('btn-success').removeClass('btn-danger');
          setTimeout(function () {
            btn.html(orig).removeClass('btn-success').addClass('btn-danger').prop('disabled', false);
          }, 1800);
        } else {
          alert(res.message || 'Không thể thêm vào giỏ.');
          btn.html(orig).prop('disabled', false);
        }
      },
      error  : function () {
        alert('Lỗi kết nối. Vui lòng thử lại.');
        btn.html(orig).prop('disabled', false);
      },
    });
  });

  // ── Giỏ hàng: nút +/- số lượng ────────────────────────────
  $(document).on('click', '.btn-qty', function () {
    const input  = $(this).closest('.input-group').find('.qty-input');
    const action = $(this).data('action');
    const max    = parseInt(input.attr('max')) || 999;
    let   val    = parseInt(input.val()) || 1;

    if (action === 'minus') val = Math.max(1, val - 1);
    if (action === 'plus')  val = Math.min(max, val + 1);

    input.val(val).trigger('change');
  });

  // FIX: lưu trạng thái mã giảm giá đang áp dụng để tính lại khi số lượng thay đổi
  let appliedCouponCode = $('#couponCode').val().trim() && $('#couponStatus .alert').length
    ? $('#couponCode').val().trim() : '';

  // Gọi API áp mã và cập nhật UI (dùng chung cho lúc bấm "Áp dụng" và lúc số lượng thay đổi)
  function recalcCoupon(code, subtotal, onDone) {
    $.ajax({
      url    : BASE_URL + '/cart/coupon',
      method : 'POST',
      data   : {
        code       : code,
        subtotal   : subtotal,
        csrf_token : $('meta[name="csrf-token"]').attr('content'),
      },
      success: function (res) {
        if (res.success) {
          appliedCouponCode = code;
          $('#discount-row').show();
          $('#cart-discount').text('-' + formatMoney(res.discount));
          $('#cart-total').text(formatMoney(res.total));
        } else {
          // Mã không còn hợp lệ với subtotal mới (vd: không đạt min_order) → bỏ áp dụng
          appliedCouponCode = '';
          $('#discount-row').hide();
          $('#cart-total').text(formatMoney(subtotal));
          $('#coupon-msg').html('<span class="text-danger">' + res.message + '</span>');
        }
        if (onDone) onDone(res);
      },
      error: function () { if (onDone) onDone(null); }
    });
  }

  // ── Giỏ hàng: cập nhật số lượng ────────────────────────
  $(document).on('change', '.qty-input', function () {
    const input     = $(this);
    const productId = input.data('id');
    const qty       = parseInt(input.val());

    $.ajax({
      url    : BASE_URL + '/cart/update',
      method : 'POST',
      data   : {
        product_id : productId,
        quantity   : qty,
        csrf_token : $('meta[name="csrf-token"]').attr('content'),
      },
      success: function (res) {
        if (res.success) {
          updateCartBadge(res.cart_count);
          // Cập nhật tổng tiền không cần reload
          if (res.subtotal !== undefined) {
            // FIX: cập nhật cả data-value (không chỉ text) để lần áp mã sau dùng đúng subtotal mới nhất
            $('#cart-subtotal').text(formatMoney(res.subtotal)).data('value', res.subtotal);
            if (appliedCouponCode) {
              // FIX: đang có mã giảm giá active → tính lại discount/total theo subtotal mới thay vì ghi đè bằng subtotal thô
              recalcCoupon(appliedCouponCode, res.subtotal);
            } else {
              $('#cart-total').text(formatMoney(res.subtotal));
            }
          }
          if (qty === 0) location.reload(); // xóa dòng
        } else {
          alert(res.message || 'Cập nhật thất bại.');
          location.reload();
        }
      },
      error: function() { location.reload(); }
    });
  });

  // ── Giỏ hàng: xóa sản phẩm ──────────────────────────────
  $(document).on('click', '.btn-remove-cart', function () {
    const productId = $(this).data('id');
    if (!confirm('Xóa sản phẩm khỏi giỏ hàng?')) return;

    $.ajax({
      url    : BASE_URL + '/cart/remove',
      method : 'POST',
      data   : {
        product_id : productId,
        csrf_token : $('meta[name="csrf-token"]').attr('content'),
      },
      success: function (res) {
        if (res.success) {
          updateCartBadge(res.cart_count);
          location.reload();
        }
      },
    });
  });

  // ── Áp dụng mã giảm giá ─────────────────────────────────
  $('#btnApplyCoupon').on('click', function () {
    const code     = $('#couponCode').val().trim();
    const subtotal = parseFloat($('#cart-subtotal').data('value') || 0);
    if (!code) { alert('Vui lòng nhập mã giảm giá.'); return; }

    $(this).prop('disabled', true).text('Đang kiểm tra...');

    recalcCoupon(code, subtotal, function (res) {
      $('#btnApplyCoupon').prop('disabled', false).text('Áp dụng');
      if (!res) { alert('Lỗi kết nối.'); return; }

      if (res.success) {
        $('#coupon-msg').html('<span class="text-success">' + res.message + '</span>');
        // FIX: chèn ngay banner "Đang dùng: MÃ [Bỏ mã]" thay vì phải reload trang mới thấy
        $('#couponStatus').html(
          '<div class="alert alert-success d-flex justify-content-between align-items-center py-2 mb-2">' +
            '<span><i class="bi bi-check-circle"></i> Đang dùng: <strong>' + $('<div>').text(code).html() + '</strong></span>' +
            '<button class="btn btn-sm btn-outline-danger" id="btnRemoveCoupon">Bỏ mã</button>' +
          '</div>'
        );
      } else {
        $('#coupon-msg').html('<span class="text-danger">' + res.message + '</span>');
      }
    });
  });
});

// Định dạng tiền không dùng server
function formatMoney(amount) {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
}