-- ============================================================
-- Website Bán Hoa Tươi – Database Schema
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
    id             INT AUTO_INCREMENT PRIMARY KEY,
    code           VARCHAR(50)   NOT NULL UNIQUE,
    discount_type  ENUM('percent','fixed') NOT NULL DEFAULT 'fixed',
    discount_value DECIMAL(10,2) NOT NULL,
    min_order      DECIMAL(12,2) NOT NULL DEFAULT 0,
    max_uses       INT           DEFAULT NULL,
    used_count     INT           NOT NULL DEFAULT 0,
    expires_at     DATETIME      DEFAULT NULL,
    status         ENUM('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. ORDERS
CREATE TABLE orders (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT            NOT NULL,
    coupon_id        INT            DEFAULT NULL,
    shipper_id       INT            DEFAULT NULL,
    receiver_name    VARCHAR(100)   NOT NULL,
    receiver_phone   VARCHAR(15)    NOT NULL,
    delivery_address TEXT           NOT NULL,
    note             TEXT           DEFAULT NULL,
    total_amount     DECIMAL(12,2)  NOT NULL,
    discount_amount  DECIMAL(12,2)  NOT NULL DEFAULT 0,
    status           ENUM('pending','confirmed','shipping','delivered','cancelled') NOT NULL DEFAULT 'pending',
    pay_method       ENUM('cod','vnpay','momo') NOT NULL,
    pay_status       ENUM('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid',
    delivery_status  ENUM('pending','assigned','shipping','delivered','failed') NOT NULL DEFAULT 'pending',
    created_at       DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
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
    id           INT AUTO_INCREMENT PRIMARY KEY,
    order_id     INT           NOT NULL UNIQUE,
    method       ENUM('cod','vnpay','momo') NOT NULL,
    amount       DECIMAL(12,2) NOT NULL,
    status       ENUM('pending','success','failed') NOT NULL DEFAULT 'pending',
    txn_ref      VARCHAR(100)  DEFAULT NULL,
    paid_at      DATETIME      DEFAULT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SEED DATA
-- ============================================================
INSERT INTO users (full_name, email, phone, password_hash, role) VALUES
('Admin', 'admin@flowershop.vn', '0900000001', '$2y$10$kQDzZPtd/79XEGU7haPjSuE/vB1bPQ68LM47RmmYpS5rmv7sThw5O', 'admin'),
('Shipper A', 'shipper@flowershop.vn', '0900000002', '$2y$10$examplehashedpassword2', 'shipper');

INSERT INTO categories (name, slug) VALUES
('Hoa Hồng', 'hoa-hong'),
('Hoa Cúc', 'hoa-cuc'),
('Hoa Lan', 'hoa-lan'),
('Hoa Ly', 'hoa-ly'),
('Hoa Bó', 'hoa-bo');
