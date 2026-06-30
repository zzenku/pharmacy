<?php
require_once __DIR__ . '/config.php';
$user    = currentUser();
$hasSub  = hasSubscription();
$cartQty = isLoggedIn() ? cartCount() : 0;
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$isAdminArea = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false);

// Вычисляем относительный путь до корня
$depth = substr_count(str_replace('\\', '/', $_SERVER['PHP_SELF']), '/') - 2;
$root  = str_repeat('../', $depth);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — ' . SITE_NAME : SITE_NAME; ?></title>
  <link rel="stylesheet" href="<?php echo $root; ?>css/style.css">
</head>
<body>

<header class="header">
  <div class="header__inner">
    <a href="<?php echo $root; ?>index.php" class="logo">
      <div class="logo__icon">&#128138;</div>
      Pharma<span>Sub</span>
    </a>

    <nav>
      <a href="<?php echo $root; ?>index.php"
         class="<?php echo ($currentPage === 'index') ? 'active' : ''; ?>">Главная</a>
      <a href="<?php echo $root; ?>products.php"
         class="<?php echo ($currentPage === 'products') ? 'active' : ''; ?>">Каталог</a>
      <?php if (isLoggedIn()): ?>
      <a href="<?php echo $root; ?>orders.php"
         class="<?php echo ($currentPage === 'orders') ? 'active' : ''; ?>">Мои заказы</a>
      <?php endif; ?>
      <?php if (isAdmin()): ?>
      <a href="<?php echo $root; ?>admin/index.php"
         class="<?php echo $isAdminArea ? 'active' : ''; ?>">&nbsp;&#9881; Админ</a>
      <?php endif; ?>
    </nav>

    <div class="header__right">
      <?php if (isLoggedIn()): ?>
        <?php if ($hasSub): ?>
          <div class="sub-chip">&#10003; Подписка</div>
        <?php else: ?>
          <a href="<?php echo $root; ?>subscription.php" class="sub-chip inactive">
            &#128274; Без подписки
          </a>
        <?php endif; ?>

        <?php if (!isAdmin()): ?>
        <a href="<?php echo $root; ?>cart.php" class="cart-btn">
          Корзина
          <?php if ($cartQty > 0): ?>
            <span class="badge"><?php echo $cartQty; ?></span>
          <?php endif; ?>
        </a>
        <?php endif; ?>

        <a href="<?php echo $root; ?>profile.php" class="cart-btn">
          <?php echo htmlspecialchars(isset($user['name']) ? $user['name'] : ''); ?>
        </a>
        <a href="<?php echo $root; ?>logout.php"
           class="cart-btn" style="background:rgba(255,255,255,.1);">Выйти</a>
      <?php else: ?>
        <a href="<?php echo $root; ?>login.php" class="cart-btn">Войти</a>
        <a href="<?php echo $root; ?>register.php" class="btn btn-accent btn-sm">Регистрация</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<main class="main">
