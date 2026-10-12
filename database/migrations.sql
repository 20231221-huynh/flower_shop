-- ============================================================
-- Website Bán Hoa Tươi
-- Charset: utf8mb4 | Engine: InnoDB
-- ============================================================

CREATE DATABASE IF NOT EXISTS flower_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE flower_shop;

-- 1. USERS
CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(100)  NOT NULL,
    email         VARCHAR(150)  NOT NULL UNIQUE,
    phone         VARCHAR(15)   DEFAULT NULL,
    password_hash VARCHAR(255)  NOT NULL,
    role          ENUM('customer','admin','shipper') NOT NULL DEFAULT 'customer',
    status        ENUM('active','inactive','banned')  NOT NULL DEFAULT 'active',
    created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. CATEGORIES
CREATE TABLE categories (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. PRODUCTS
CREATE TABLE products (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT            NOT NULL,
    name        VARCHAR(200)   NOT NULL,
    description TEXT           DEFAULT NULL,
    price       DECIMAL(12,2)  NOT NULL,
    sale_price  DECIMAL(12,2)  DEFAULT NULL,
    stock       INT            NOT NULL DEFAULT 0,
    status      ENUM('active','inactive','deleted') NOT NULL DEFAULT 'active',
    created_at  DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. PRODUCT_IMAGES
CREATE TABLE product_images (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT          NOT NULL,
    image_url  VARCHAR(255) NOT NULL,
    is_primary BOOLEAN      NOT NULL DEFAULT FALSE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. CARTS
CREATE TABLE carts (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. CART_ITEMS
CREATE TABLE cart_items (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    cart_id    INT NOT NULL,
    product_id INT NOT NULL,
    quantity   INT NOT NULL DEFAULT 1,
    FOREIGN KEY (cart_id)    REFERENCES carts(id)    ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. COUPONS
CREATE TABLE coupons (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    code             VARCHAR(50)   NOT NULL UNIQUE,
    discount_type    ENUM('percent','fixed') NOT NULL DEFAULT 'fixed',
    discount_value   DECIMAL(10,2) NOT NULL,
    min_order        DECIMAL(12,2) NOT NULL DEFAULT 0,
    max_uses         INT           DEFAULT NULL,
    used_count       INT           NOT NULL DEFAULT 0,
    expires_at       DATETIME      DEFAULT NULL,
    status           ENUM('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. ORDERS
CREATE TABLE orders (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    user_id            INT            NOT NULL,
    coupon_id          INT            DEFAULT NULL,
    shipper_id         INT            DEFAULT NULL,
    receiver_name      VARCHAR(100)   NOT NULL,
    receiver_phone     VARCHAR(15)    NOT NULL,
    delivery_address   TEXT           NOT NULL,
    note               TEXT           DEFAULT NULL,
    total_amount       DECIMAL(12,2)  NOT NULL,
    discount_amount    DECIMAL(12,2)  NOT NULL DEFAULT 0,
    status             ENUM('pending','confirmed','shipping','delivered','cancelled') NOT NULL DEFAULT 'pending',
    pay_method         ENUM('cod','vnpay','momo') NOT NULL,
    pay_status         ENUM('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid',
    delivery_status    ENUM('pending','assigned','shipping','delivered','failed') NOT NULL DEFAULT 'pending',
    created_at         DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)    REFERENCES users(id),
    FOREIGN KEY (coupon_id)  REFERENCES coupons(id),
    FOREIGN KEY (shipper_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. ORDER_ITEMS
CREATE TABLE order_items (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    order_id   INT           NOT NULL,
    product_id INT           NOT NULL,
    quantity   INT           NOT NULL,
    price      DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. REVIEWS
CREATE TABLE reviews (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT  NOT NULL,
    product_id INT  NOT NULL,
    rating     TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment    TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)    REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. PAYMENTS
CREATE TABLE payments (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    order_id   INT           NOT NULL UNIQUE,
    method     ENUM('cod','vnpay','momo') NOT NULL,
    amount     DECIMAL(12,2) NOT NULL,
    status     ENUM('pending','success','failed') NOT NULL DEFAULT 'pending',
    txn_ref    VARCHAR(100)  DEFAULT NULL,
    paid_at    DATETIME      DEFAULT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SEED DATA (DỮ LIỆU MẪU PHONG PHÚ)
-- ============================================================

-- 1. Users (Nhiều Admin, Shipper và Khách hàng)
INSERT INTO users (full_name, email, phone, password_hash, role, status) VALUES
('Quản Trị Viên', 'admin@flowershop.vn', '0900000001', '$2y$10$kQDzZPtd/79XEGU7haPjSuE/vB1bPQ68LM47RmmYpS5rmv7sThw5O', 'admin', 'active'),
('Quản Lý Kho', 'manager@flowershop.vn', '0900000099', '$2y$10$kQDzZPtd/79XEGU7haPjSuE/vB1bPQ68LM47RmmYpS5rmv7sThw5O', 'admin', 'active'),
('Shipper Nguyễn Văn A', 'shipper1@flowershop.vn', '0900000002', '$2y$10$examplehashedpassword2', 'shipper', 'active'),
('Shipper Trần Văn B', 'shipper2@flowershop.vn', '0900000003', '$2y$10$examplehashedpassword2', 'shipper', 'active'),
('Nguyễn Văn Khách', 'khachhang1@gmail.com', '0912345678', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'active'),
('Trần Thị Thảo', 'thao.tran@gmail.com', '0988776655', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'active'),
('Lê Hoàng Nam', 'nam.le@gmail.com', '0933445566', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'active'),
('Phạm Quỳnh Nga', 'nga.pham@gmail.com', '0977112233', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'active');

-- 2. Categories
INSERT INTO categories (name, slug) VALUES
('Hoa Hồng', 'hoa-hong'),
('Hoa Cúc', 'hoa-cuc'),
('Hoa Lan', 'hoa-lan'),
('Hoa Ly', 'hoa-ly'),
('Hoa Bó', 'hoa-bo'),
('Hoa Khai Trương', 'hoa-khai-truong'),
('Hoa Chia Buồn', 'hoa-chia-buon');

-- 3. Products (Nhiều sản phẩm đa dạng)
INSERT INTO products (category_id, name, description, price, sale_price, stock, status) VALUES
(1, 'Bó Hoa Hồng Đỏ Ecuador', 'Hoa hồng Ecuador nhập khẩu tươi thắm, biểu tượng của tình yêu chân thành.', 550000.00, 500000.00, 20, 'active'),
(1, 'Hộp Hoa Hồng Phấn Ngọt Ngào', 'Sự kết hợp tinh tế giữa hoa hồng phấn và hoa baby trắng.', 420000.00, NULL, 15, 'active'),
(1, 'Bó Hoa Hồng Kem Vintage', 'Phong cách cổ điển, sang trọng thích hợp tặng sinh nhật, kỷ niệm.', 480000.00, 450000.00, 10, 'active'),
(2, 'Bó Hoa Cúc Tana Nhỏ Xinh', 'Cúc tana mộc mạc, đáng yêu mang lại cảm giác bình yên.', 280000.00, 250000.00, 30, 'active'),
(3, 'Chậu Lan Hồ Điệp Tím 5 Cành', 'Lan hồ điệp sang trọng phù hợp biếu tặng đối tác, khai trương.', 1200000.00, 1100000.00, 8, 'active'),
(3, 'Chậu Lan Hồ Điệp Trắng Đại Phát', 'Chậu lan 10 cành hoành tráng, tài lộc thịnh vượng.', 2500000.00, 2300000.00, 5, 'active'),
(4, 'Bó Hoa Ly Vàng Thơm Ngát', 'Hoa ly vàng tươi rực rỡ, hương thơm dịu nhẹ quyến rũ.', 380000.00, 350000.00, 18, 'active'),
(5, 'Bó Hoa Hướng Dương Nắng Mai', 'Hướng dương tươi sáng, mang lại năng lượng tích cực cho ngày mới.', 350000.00, 320000.00, 25, 'active'),
(6, 'Kệ Hoa Khai Trương Hồng Phát', 'Kệ hoa lớn chúc mừng tài lộc, phát tài phát lộc cho doanh nghiệp.', 1500000.00, 1400000.00, 6, 'active'),
(7, 'Lẵng Hoa Kính Viếng Thành Kính', 'Lẵng hoa trang nghiêm gửi gắm lời chia buồn sâu sắc.', 850000.00, NULL, 12, 'active');

-- 4. Carts (Cho các user từ id = 5 đến 8)
INSERT INTO carts (user_id) VALUES (5), (6), (7), (8);

-- 5. Cart Items
INSERT INTO cart_items (cart_id, product_id, quantity) VALUES
(1, 1, 1),
(1, 8, 2),
(2, 2, 1),
(3, 5, 1),
(4, 4, 3);

-- 6. Coupons
INSERT INTO coupons (code, discount_type, discount_value, min_order, max_uses, used_count, expires_at, status) VALUES
('SUMMER2026', 'percent', 15.00, 200000.00, 100, 5, '2026-12-31 23:59:59', 'active'),
('GIAM50K', 'fixed', 50000.00, 300000.00, 50, 12, '2026-08-30 23:59:59', 'active'),
('FLOWERVIP', 'percent', 25.00, 1000000.00, 20, 2, '2026-11-30 23:59:59', 'active');

-- 7. Orders (Nhiều đơn hàng với trạng thái khác nhau)
INSERT INTO orders (user_id, coupon_id, shipper_id, receiver_name, receiver_phone, delivery_address, note, total_amount, discount_amount, status, pay_method, pay_status, delivery_status) VALUES
(5, 1, 3, 'Nguyễn Văn Khách', '0912345678', '123 Đường Cầu Giấy, Hà Nội', 'Giao giờ hành chính giúp mình', 820000.00, 50000.00, 'shipping', 'vnpay', 'paid', 'shipping'),
(6, 2, 4, 'Trần Thị Thảo', '0988776655', '45 Nguyễn Trãi, Thanh Xuân, Hà Nội', 'Gọi trước khi giao', 420000.00, 50000.00, 'delivered', 'cod', 'paid', 'delivered'),
(7, NULL, NULL, 'Lê Hoàng Nam', '0933445566', '12 Lê Duẩn, Đống Đa, Hà Nội', 'Tặng kèm thiệp chúc mừng sinh nhật', 1100000.00, 0.00, 'pending', 'momo', 'unpaid', 'pending'),
(8, 3, 3, 'Phạm Quỳnh Nga', '0977112233', '88 Hoàn Kiếm, Hà Nội', 'Cẩn thận hoa giúp em', 2300000.00, 200000.00, 'confirmed', 'vnpay', 'paid', 'assigned');

-- 8. Order Items
INSERT INTO order_items (order_id, product_id, quantity, price) VALUES
(1, 1, 1, 500000.00),
(1, 8, 1, 320000.00),
(2, 2, 1, 420000.00),
(3, 5, 1, 1100000.00),
(4, 6, 1, 2300000.00);

-- 9. Reviews (Nhiều đánh giá sản phẩm từ khách hàng)
INSERT INTO reviews (user_id, product_id, rating, comment) VALUES
(5, 1, 5, 'Hoa rất tươi, đóng gói cẩn thận, người nhận rất thích!'),
(5, 8, 4, 'Hướng dương sáng rực rỡ, giao hàng nhanh.'),
(6, 2, 5, 'Hộp hoa hồng xinh xắn và rất thơm.'),
(7, 5, 5, 'Lan hồ điệp đẹp, sang trọng, chậu chắc chắn.'),
(8, 6, 5, 'Chất lượng tuyệt vời, hoa tươi lâu hơn mong đợi.');

-- 10. Payments
INSERT INTO payments (order_id, method, amount, status, txn_ref, paid_at) VALUES
(1, 'vnpay', 820000.00, 'success', 'TXN2026101298765', '2026-10-12 11:00:00'),
(2, 'cod', 420000.00, 'success', 'TXN2026101012345', '2026-10-10 14:30:00'),
(4, 'vnpay', 2300000.00, 'success', 'TXN2026101155443', '2026-10-11 09:15:00');