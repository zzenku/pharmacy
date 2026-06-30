<?php
$pageTitle = 'Корзина';
require_once __DIR__ . '/header.php';

if (!isLoggedIn()) redirect('login.php');
if (!hasSubscription()) redirect('subscription.php');

$db  = getDB();
$uid = $_SESSION['user_id'];

// Удаление
if (isset($_GET['remove'])) {
    $db->prepare('DELETE FROM cart WHERE user_id=? AND product_id=?')
       ->execute([$uid, (int)$_GET['remove']]);
    redirect('cart.php');
}

// Обновление количества
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    foreach ($_POST['qty'] as $pid => $qty) {
        $qty = max(1, (int)$qty);
        $db->prepare('UPDATE cart SET quantity=? WHERE user_id=? AND product_id=?')
           ->execute([$qty, $uid, (int)$pid]);
    }
    redirect('cart.php');
}

// Получаем корзину
$items = $db->prepare('
    SELECT c.*, c.quantity AS qty,
           p.name, p.price_wholesale, p.price_retail, p.unit, p.stock,
           cat.icon AS cat_icon
    FROM cart c
    JOIN products p ON p.id = c.product_id
    JOIN categories cat ON cat.id = p.category_id
    WHERE c.user_id = ?
    ORDER BY c.added_at
');
$items->execute([$uid]);
$items = $items->fetchAll();

$subtotal = 0;
$saved    = 0;
foreach ($items as $_i) {
    $subtotal += $_i['price_wholesale'] * $_i['qty'];
    $saved    += ($_i['price_retail'] - $_i['price_wholesale']) * $_i['qty'];
}
?>

<div class="container">
  <div class="page-header">
    <h1>🛒 Корзина</h1>
    <p>Цены указаны по подписке (оптовые)</p>
  </div>

  <?php if (empty($items)): ?>
    <div class="empty-state">
      <span class="empty-state__icon">🛒</span>
      <div class="empty-state__title">Корзина пуста</div>
      <p class="text-muted mt-1">Добавьте препараты из каталога</p>
      <a href="products.php" class="btn btn-primary mt-3">Перейти в каталог</a>
    </div>
  <?php else: ?>
    <div class="two-col">
      <div>
        <div class="card">
          <div class="card-header">💊 Товары (<?= count($items) ?>)</div>
          <div class="card-body">
            <form method="post">
              <?php foreach ($items as $item): ?>
                <div class="cart-item">
                  <div class="cart-item__icon"><?= $item['cat_icon'] ?></div>
                  <div class="cart-item__info">
                    <div class="cart-item__name"><?= htmlspecialchars($item['name']) ?></div>
                    <div class="cart-item__unit"><?= htmlspecialchars($item['unit']) ?></div>
                  </div>
                  <div class="cart-item__qty">
                    <input type="number" name="qty[<?= $item['product_id'] ?>]"
                           value="<?= $item['qty'] ?>" min="1" max="<?= $item['stock'] ?>"
                           style="width:64px;padding:7px;border:1.5px solid var(--border);
                                  border-radius:8px;font-size:.88rem;text-align:center;">
                  </div>
                  <div class="cart-item__price">
                    <?= formatPrice($item['price_wholesale'] * $item['qty']) ?>
                  </div>
                  <a href="cart.php?remove=<?= $item['product_id'] ?>"
                     onclick="return confirm('Удалить из корзины?')"
                     style="color:var(--danger);font-size:1.2rem;text-decoration:none;" title="Удалить">✕</a>
                </div>
              <?php endforeach; ?>

              <div class="mt-3">
                <button type="submit" name="update" class="btn btn-outline btn-sm">
                  🔄 Обновить количество
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div>
        <div class="card">
          <div class="card-header">💰 Итого</div>
          <div class="card-body">
            <div class="cart-summary">
              <div class="cart-summary__row">
                <span>Товаров:</span>
                <span><?= count($items) ?> поз.</span>
              </div>
              <div class="cart-summary__row">
                <span>Розничная стоимость:</span>
                <span style="text-decoration:line-through;color:var(--text-muted);">
                  <?= formatPrice($subtotal + $saved) ?>
                </span>
              </div>
              <div class="cart-summary__row" style="color:var(--success);font-weight:600;">
                <span>Ваша экономия:</span>
                <span>−<?= formatPrice($saved) ?></span>
              </div>
              <div class="cart-summary__row total">
                <span>К оплате:</span>
                <span><?= formatPrice($subtotal) ?></span>
              </div>
            </div>

            <a href="checkout.php" class="btn btn-primary btn-block btn-lg mt-3">
              Оформить заказ →
            </a>
            <a href="products.php" class="btn btn-outline btn-block mt-2">
              ← Продолжить покупки
            </a>

            <div class="alert alert-info mt-3" style="font-size:.82rem;">
              🏷 Вы экономите <strong><?= formatPrice($saved) ?></strong> по подписке!
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
