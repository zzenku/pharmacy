<?php
$pageTitle = 'Главная';
require_once __DIR__ . '/header.php';

$db = getDB();

// Последние добавленные товары
$featured = $db->query('
    SELECT p.*, c.name AS cat_name, c.icon AS cat_icon
    FROM products p
    JOIN categories c ON c.id = p.category_id
    WHERE p.active = 1
    ORDER BY p.id DESC
    LIMIT 6
')->fetchAll();

// Кол-во категорий и товаров
$stats = $db->query('SELECT
    (SELECT COUNT(*) FROM products WHERE active=1) AS total_products,
    (SELECT COUNT(*) FROM categories) AS total_cats,
    (SELECT COUNT(*) FROM users WHERE subscription_active=1) AS total_subs
')->fetch();
?>

<section class="hero">
  <div class="container">
    <div class="hero__title">💊 Аптека по подписке</div>
    <p class="hero__subtitle">
      Оформите подписку и покупайте препараты по оптовым ценам —
      экономия до 40% на каждой покупке
    </p>
    <div class="hero__actions">
      <?php if (!isLoggedIn()): ?>
        <a href="register.php" class="btn btn-accent btn-lg">Оформить подписку</a>
        <a href="products.php" class="btn btn-outline btn-lg" style="border-color:rgba(255,255,255,.6);color:#fff;">Смотреть каталог</a>
      <?php elseif (!hasSubscription()): ?>
        <a href="subscription.php" class="btn btn-accent btn-lg">🔑 Оформить подписку</a>
        <a href="products.php" class="btn btn-outline btn-lg" style="border-color:rgba(255,255,255,.6);color:#fff;">Смотреть каталог</a>
      <?php else: ?>
        <a href="products.php" class="btn btn-accent btn-lg">🛒 В каталог</a>
        <a href="orders.php" class="btn btn-outline btn-lg" style="border-color:rgba(255,255,255,.6);color:#fff;">Мои заказы</a>
      <?php endif; ?>
    </div>
  </div>
</section>

<div class="container">

  <!-- Статистика -->
  <div class="stat-cards mt-4">
    <div class="stat-card">
      <div class="stat-card__icon">💊</div>
      <div class="stat-card__label">Препаратов в каталоге</div>
      <div class="stat-card__value"><?= $stats['total_products'] ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-card__icon">📂</div>
      <div class="stat-card__label">Категорий</div>
      <div class="stat-card__value"><?= $stats['total_cats'] ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-card__icon">✅</div>
      <div class="stat-card__label">Активных подписчиков</div>
      <div class="stat-card__value"><?= $stats['total_subs'] ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-card__icon">💰</div>
      <div class="stat-card__label">Скидка по подписке</div>
      <div class="stat-card__value">до 45%</div>
    </div>
  </div>

  <!-- Как это работает -->
  <div class="card mt-4">
    <div class="card-header">⚡ Как работает PharmaSub</div>
    <div class="card-body">
      <div class="features-grid">
        <div class="feature-item">
          <span class="feature-item__icon">📝</span>
          <div class="feature-item__title">Регистрация</div>
          <div class="feature-item__desc">Создайте аккаунт — это бесплатно и занимает 1 минуту</div>
        </div>
        <div class="feature-item">
          <span class="feature-item__icon">💳</span>
          <div class="feature-item__title">Подписка <?= formatPrice(SUBSCRIPTION_PRICE) ?>/мес</div>
          <div class="feature-item__desc">Оформите подписку и получите доступ к оптовым ценам</div>
        </div>
        <div class="feature-item">
          <span class="feature-item__icon">🛒</span>
          <div class="feature-item__title">Покупайте</div>
          <div class="feature-item__desc">Заказывайте препараты по ценам ниже аптечных на 30–45%</div>
        </div>
        <div class="feature-item">
          <span class="feature-item__icon">🚚</span>
          <div class="feature-item__title">Получайте</div>
          <div class="feature-item__desc">Быстрая доставка или самовывоз из пункта выдачи</div>
        </div>
      </div>

      <div class="alert alert-warning">
        🔒 <strong>Важно:</strong> Покупка препаратов доступна <strong>только для подписчиков</strong>.
        Без активной подписки добавить товар в корзину невозможно.
      </div>
    </div>
  </div>

  <!-- Новинки каталога -->
  <?php if (!empty($featured)): ?>
  <div class="page-header mt-4">
    <h1>Новинки каталога</h1>
    <p>Последние поступления в нашу аптеку</p>
  </div>

  <div class="products-grid">
    <?php foreach ($featured as $p):
      $save = round(($p['price_retail'] - $p['price_wholesale']) / $p['price_retail'] * 100);
    ?>
    <div class="product-card">
      <div class="product-card__header">
        <?= $p['cat_icon'] ?>
        <?php if ($p['requires_prescription']): ?>
          <span class="product-card__badge">Рецепт</span>
        <?php endif; ?>
      </div>
      <div class="product-card__body">
        <div class="product-card__category"><?= htmlspecialchars($p['cat_name']) ?></div>
        <div class="product-card__name"><?= htmlspecialchars($p['name']) ?></div>
        <div class="product-card__desc"><?= htmlspecialchars($p['description']) ?></div>
        <div class="product-card__prices">
          <span class="price-retail"><?= formatPrice($p['price_retail']) ?></span>
          <?php if (hasSubscription()): ?>
            <span class="price-sub"><?= formatPrice($p['price_wholesale']) ?></span>
            <span class="save-chip">−<?= $save ?>%</span>
          <?php else: ?>
            <span class="price-no-sub">🔒 Только для подписчиков</span>
          <?php endif; ?>
        </div>
      </div>
      <div class="product-card__foot">
        <span class="stock-label <?= $p['stock'] < 10 ? 'low' : '' ?>">
          <?= $p['stock'] > 0 ? 'В наличии: ' . $p['stock'] . ' ' . htmlspecialchars($p['unit']) : '' ?>
        </span>
        <a href="products.php" class="btn btn-outline btn-sm">Подробнее</a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="text-center mt-3 mb-3">
    <a href="products.php" class="btn btn-primary">Весь каталог →</a>
  </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
