<?php
$pageTitle = 'Каталог препаратов';
require_once __DIR__ . '/header.php';

$db = getDB();
$hasSub = hasSubscription();

// Фильтр по категории
$catId = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;

// Все категории
$categories = $db->query('SELECT * FROM categories ORDER BY name')->fetchAll();

// Товары
if ($catId > 0) {
    $st = $db->prepare('
        SELECT p.*, c.name AS cat_name, c.icon AS cat_icon
        FROM products p
        JOIN categories c ON c.id = p.category_id
        WHERE p.active = 1 AND p.category_id = ?
        ORDER BY p.name
    ');
    $st->execute([$catId]);
} else {
    $st = $db->query('
        SELECT p.*, c.name AS cat_name, c.icon AS cat_icon
        FROM products p
        JOIN categories c ON c.id = p.category_id
        WHERE p.active = 1
        ORDER BY c.name, p.name
    ');
}
$products = $st->fetchAll();

// Добавление в корзину
$cartMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
    if (!$hasSub) {
        $cartMsg = 'error';
    } else {
        $pid = (int)($_POST['product_id'] ?? 0);
        $qty = max(1, (int)($_POST['qty'] ?? 1));

        // Проверяем товар
        $ps = $db->prepare('SELECT id, stock FROM products WHERE id = ? AND active = 1');
        $ps->execute([$pid]);
        $prod = $ps->fetch();

        if ($prod && $prod['stock'] >= $qty) {
            $db->prepare('
                INSERT INTO cart (user_id, product_id, quantity)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE quantity = quantity + ?
            ')->execute([$_SESSION['user_id'], $pid, $qty, $qty]);
            flash('success', 'Товар добавлен в корзину!');
        }
        redirect('products.php' . ($catId ? '?cat=' . $catId : ''));
    }
}

$flashSuccess = flash('success');
?>

<div class="container">
  <div class="page-header">
    <h1>💊 Каталог препаратов</h1>
    <p>Оптовые цены доступны только для подписчиков</p>
  </div>

  <?php if ($flashSuccess): ?>
    <div class="alert alert-success">✅ <?= htmlspecialchars($flashSuccess) ?></div>
  <?php endif; ?>

  <?php if (!$hasSub): ?>
    <div class="sub-wall">
      <div class="sub-wall__icon">🔒</div>
      <div class="sub-wall__title">Для покупки необходима подписка</div>
      <div class="sub-wall__text">
        Вы можете просматривать каталог, но добавить товар в корзину можно только при наличии активной подписки.
        <?php if (!isLoggedIn()): ?>
          Войдите или зарегистрируйтесь.
        <?php endif; ?>
      </div>
      <?php if (isLoggedIn()): ?>
        <a href="subscription.php" class="btn btn-accent">Оформить подписку — <?= formatPrice(SUBSCRIPTION_PRICE) ?>/мес</a>
      <?php else: ?>
        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
          <a href="register.php" class="btn btn-accent">Зарегистрироваться</a>
          <a href="login.php" class="btn btn-outline">Войти</a>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- Фильтр категорий -->
  <div class="filter-bar mb-3">
    <a href="products.php" class="filter-chip <?= $catId === 0 ? 'active' : '' ?>">
      🗂 Все категории
    </a>
    <?php foreach ($categories as $cat): ?>
      <a href="products.php?cat=<?= $cat['id'] ?>"
         class="filter-chip <?= $catId === (int)$cat['id'] ? 'active' : '' ?>">
        <?= $cat['icon'] ?> <?= htmlspecialchars($cat['name']) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- Сетка товаров -->
  <?php if (empty($products)): ?>
    <div class="empty-state">
      <span class="empty-state__icon">🔍</span>
      <div class="empty-state__title">Товары не найдены</div>
      <a href="products.php" class="btn btn-outline mt-2">Показать все</a>
    </div>
  <?php else: ?>
    <div class="products-grid">
      <?php foreach ($products as $p):
        $save = round(($p['price_retail'] - $p['price_wholesale']) / $p['price_retail'] * 100);
      ?>
      <div class="product-card">
        <div class="product-card__header">
          <?= $p['cat_icon'] ?>
          <?php if ($p['requires_prescription']): ?>
            <span class="product-card__badge">По рецепту</span>
          <?php endif; ?>
        </div>

        <div class="product-card__body">
          <div class="product-card__category"><?= htmlspecialchars($p['cat_name']) ?></div>
          <div class="product-card__name"><?= htmlspecialchars($p['name']) ?></div>
          <div class="product-card__desc"><?= htmlspecialchars($p['description']) ?></div>

          <div class="product-card__prices">
            <span class="price-retail"><?= formatPrice($p['price_retail']) ?></span>
            <?php if ($hasSub): ?>
              <span class="price-sub"><?= formatPrice($p['price_wholesale']) ?></span>
              <span class="save-chip">−<?= $save ?>%</span>
            <?php else: ?>
              <span class="price-no-sub" title="Только для подписчиков">🔒 Подписка</span>
            <?php endif; ?>
          </div>

          <?php if ($p['requires_prescription']): ?>
            <span class="prescription-tag">📋 Требуется рецепт</span>
          <?php endif; ?>
        </div>

        <div class="product-card__foot">
          <?php if ($p['stock'] <= 0): ?>
            <span class="stock-label out">Нет в наличии</span>
          <?php elseif ($p['stock'] < 10): ?>
            <span class="stock-label low">⚠ Осталось: <?= $p['stock'] ?></span>
          <?php else: ?>
            <span class="stock-label">В наличии: <?= $p['stock'] ?> <?= htmlspecialchars($p['unit']) ?></span>
          <?php endif; ?>

          <?php if ($hasSub && $p['stock'] > 0): ?>
            <form method="post" style="display:flex;gap:6px;align-items:center;">
              <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
              <input type="number" name="qty" value="1" min="1" max="<?= $p['stock'] ?>"
                     style="width:56px;padding:6px;border:1.5px solid var(--border);border-radius:8px;font-size:.85rem;text-align:center;">
              <button type="submit" name="add_to_cart" class="btn btn-primary btn-sm">
                🛒 В корзину
              </button>
            </form>
          <?php elseif (!$hasSub): ?>
            <a href="subscription.php" class="btn btn-accent btn-sm">🔑 Подписка</a>
          <?php else: ?>
            <span class="btn btn-sm disabled">Нет в наличии</span>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
