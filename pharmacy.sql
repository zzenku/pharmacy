-- =============================================
-- Аптека по подписке — PharmaSub
-- Импортируйте этот файл в phpMyAdmin
-- =============================================

CREATE DATABASE IF NOT EXISTS `pharmasub`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `pharmasub`;

-- ----------------------------
-- Таблица пользователей
-- ----------------------------
CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('user','admin') NOT NULL DEFAULT 'user',
  `subscription_active` TINYINT(1) NOT NULL DEFAULT 0,
  `subscription_expires` DATE NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Категории товаров
-- ----------------------------
CREATE TABLE `categories` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `icon` VARCHAR(50) NOT NULL DEFAULT '💊'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Товары (препараты)
-- ----------------------------
CREATE TABLE `products` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(200) NOT NULL,
  `description` TEXT,
  `price_retail` DECIMAL(10,2) NOT NULL COMMENT 'Розничная цена',
  `price_wholesale` DECIMAL(10,2) NOT NULL COMMENT 'Оптовая цена (для подписчиков)',
  `stock` INT UNSIGNED NOT NULL DEFAULT 0,
  `unit` VARCHAR(30) NOT NULL DEFAULT 'уп.',
  `requires_prescription` TINYINT(1) NOT NULL DEFAULT 0,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Корзина
-- ----------------------------
CREATE TABLE `cart` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `added_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `user_product` (`user_id`, `product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Заказы
-- ----------------------------
CREATE TABLE `orders` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `total` DECIMAL(10,2) NOT NULL,
  `status` ENUM('pending','processing','completed','cancelled') NOT NULL DEFAULT 'pending',
  `delivery_address` TEXT,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Позиции заказа
-- ----------------------------
CREATE TABLE `order_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `quantity` INT UNSIGNED NOT NULL,
  `price_paid` DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Тестовые данные
-- =============================================

-- Администратор (пароль: admin123)
INSERT INTO `users` (`name`, `email`, `password`, `role`, `subscription_active`) VALUES
('Администратор', 'admin@pharmasub.ru', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1);

-- Тестовый пользователь с подпиской (пароль: user123)
INSERT INTO `users` (`name`, `email`, `password`, `role`, `subscription_active`, `subscription_expires`) VALUES
('Иван Петров', 'user@pharmasub.ru', '$2y$10$TKh8H1.PffSNQDm/QioBYuaxBdJ1a.IvqsVJWM30JCCZxD9R0cxB2', 'user', 1, DATE_ADD(CURDATE(), INTERVAL 30 DAY));

-- Тестовый пользователь без подписки (пароль: test123)
INSERT INTO `users` (`name`, `email`, `password`, `role`, `subscription_active`) VALUES
('Мария Иванова', 'test@pharmasub.ru', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 0);

-- Категории
INSERT INTO `categories` (`name`, `icon`) VALUES
('Сердечно-сосудистые', '❤️'),
('Антибиотики', '🧬'),
('Витамины и БАД', '🌿'),
('Обезболивающие', '💊'),
('ЖКТ', '🫁'),
('Противовирусные', '🛡️'),
('Дерматология', '🧴'),
('Офтальмология', '👁️');

-- Товары
INSERT INTO `products` (`category_id`, `name`, `description`, `price_retail`, `price_wholesale`, `stock`, `unit`, `requires_prescription`) VALUES
(1, 'Эналаприл 10 мг', 'Ингибитор АПФ для лечения артериальной гипертензии и сердечной недостаточности.', 320.00, 185.00, 150, 'уп. 30 таб.', 1),
(1, 'Лозартан 50 мг', 'Антагонист рецепторов ангиотензина II. Снижает артериальное давление.', 480.00, 275.00, 90, 'уп. 30 таб.', 1),
(1, 'Бисопролол 5 мг', 'Бета-блокатор. Применяется при гипертонии и стенокардии.', 390.00, 220.00, 120, 'уп. 30 таб.', 1),
(2, 'Амоксициллин 500 мг', 'Антибиотик широкого спектра действия группы пенициллинов.', 260.00, 140.00, 200, 'уп. 20 капс.', 1),
(2, 'Азитромицин 500 мг', 'Антибиотик-макролид. Эффективен при инфекциях дыхательных путей.', 380.00, 210.00, 180, 'уп. 3 таб.', 1),
(2, 'Ципрофлоксацин 500 мг', 'Фторхинолоновый антибиотик широкого спектра действия.', 310.00, 175.00, 160, 'уп. 10 таб.', 1),
(3, 'Витамин D3 2000 МЕ', 'Холекальциферол. Профилактика дефицита витамина D.', 590.00, 330.00, 300, 'уп. 60 капс.', 0),
(3, 'Омега-3 1000 мг', 'Полиненасыщенные жирные кислоты EPA и DHA. Поддержка сердца и сосудов.', 750.00, 420.00, 250, 'уп. 60 капс.', 0),
(3, 'Магний B6', 'Магний в сочетании с пиридоксином. Снимает стресс и спазмы.', 420.00, 235.00, 220, 'уп. 50 таб.', 0),
(4, 'Ибупрофен 400 мг', 'НПВС. Обезболивающее, противовоспалительное и жаропонижающее средство.', 180.00, 95.00, 400, 'уп. 20 таб.', 0),
(4, 'Кеторол 10 мг', 'Мощное обезболивающее при сильных болях.', 250.00, 135.00, 300, 'уп. 20 таб.', 1),
(4, 'Нурофен Экспресс', 'Быстродействующий ибупрофен в капсулах.', 320.00, 175.00, 350, 'уп. 12 капс.', 0),
(5, 'Омепразол 20 мг', 'Ингибитор протонной помпы. Лечение язвы и гастрита.', 280.00, 150.00, 200, 'уп. 30 капс.', 0),
(5, 'Линекс Форте', 'Пробиотик с лактобактериями. Нормализация микрофлоры кишечника.', 640.00, 355.00, 180, 'уп. 14 капс.', 0),
(5, 'Мотилиум 10 мг', 'Противорвотное средство. Устраняет тошноту и рвоту.', 450.00, 250.00, 140, 'уп. 30 таб.', 0),
(6, 'Арбидол 200 мг', 'Противовирусное и иммуностимулирующее средство.', 560.00, 310.00, 250, 'уп. 20 таб.', 0),
(6, 'Амиксин 125 мг', 'Индуктор интерферона. Лечение и профилактика гриппа.', 980.00, 540.00, 120, 'уп. 10 таб.', 0),
(6, 'Кагоцел 12 мг', 'Противовирусное. Применяется при ОРВИ и гриппе.', 460.00, 255.00, 200, 'уп. 18 таб.', 0),
(7, 'Пантенол спрей', 'Декспантенол 5%. Заживление ожогов, ран и сухой кожи.', 380.00, 210.00, 160, '130 мл', 0),
(7, 'Акридерм', 'Бетаметазон. Лечение воспалительных заболеваний кожи.', 290.00, 160.00, 100, '15 г', 1),
(8, 'Визин Классик', 'Сосудосуживающие глазные капли при покраснении глаз.', 340.00, 185.00, 200, '15 мл', 0),
(8, 'Тобрекс капли', 'Антибактериальные глазные капли (тобрамицин).', 420.00, 230.00, 130, '5 мл', 1);
