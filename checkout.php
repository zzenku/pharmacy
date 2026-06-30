<?php
$pageTitle = 'Оформление заказа';
require_once __DIR__ . '/header.php';

if (!isLoggedIn()) redirect('login.php');
if (!hasSubscription()) redirect('subscription.php');

$db  = getDB();
$uid = $_SESSION['user_id'];

// Проверяем корзину
$items = $db->prepare('
    SELECT c.quantity AS qty, p.name, p.price_wholesale, p.price_retail, p.unit, p.id AS pid, p.stock
    FROM cart c
    JOIN products p ON p.id = c.product_id
    WHERE c.user_id = ?
');
$items->execute([$uid]);
$items = $items->fetchAll();

if (empty($items)) redirect('cart.php');

$subtotal = 0;
foreach ($items as $_i) { $subtotal += $_i['price_wholesale'] * $_i['qty']; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = trim($_POST['address'] ?? '');
    if (!$address) {
        $error = 'Укажите адрес доставки.';
    } else {
        // Создаём заказ
        $db->prepare('INSERT INTO orders (user_id, total, delivery_address) VALUES (?,?,?)')
           ->execute([$uid, $subtotal, $address]);
        $orderId = $db->lastInsertId();

        // Добавляем позиции и уменьшаем склад
        foreach ($items as $item) {
            $db->prepare('INSERT INTO order_items (order_id, product_id, quantity, price_paid) VALUES (?,?,?,?)')
               ->execute([$orderId, $item['pid'], $item['qty'], $item['price_wholesale']]);
            $db->prepare('UPDATE products SET stock = stock - ? WHERE id = ?')
               ->execute([$item['qty'], $item['pid']]);
        }

        // Очищаем корзину
        $db->prepare('DELETE FROM cart WHERE user_id = ?')->execute([$uid]);

        flash('success', 'Заказ №' . $orderId . ' успешно оформлен!');
        redirect('orders.php');
    }
}
?>

<div class="container" style="max-width:860px;">
  <div class="page-header">
    <h1>📦 Оформление заказа</h1>
    <p>Проверьте состав заказа и укажите адрес доставки</p>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="two-col">
    <div>
      <!-- Состав заказа -->
      <div class="card mb-3">
        <div class="card-header">💊 Ваш заказ</div>
        <div class="card-body">
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Препарат</th>
                  <th>Кол-во</th>
                  <th style="text-align:right">Цена/ед.</th>
                  <th style="text-align:right">Итого</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                  <td>
                    <div style="font-weight:600;font-size:.9rem;"><?= htmlspecialchars($item['name']) ?></div>
                    <div style="font-size:.78rem;color:var(--text-muted);"><?= htmlspecialchars($item['unit']) ?></div>
                  </td>
                  <td><?= $item['qty'] ?></td>
                  <td style="text-align:right;"><?= formatPrice($item['price_wholesale']) ?></td>
                  <td style="text-align:right;font-weight:700;color:var(--primary);">
                    <?= formatPrice($item['price_wholesale'] * $item['qty']) ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Адрес доставки -->
      <div class="card">
        <div class="card-header">🚚 Доставка</div>
        <div class="card-body">
          <form method="post">
            <div class="form-group">
              <label class="form-label">Адрес доставки *</label>
              <textarea name="address" class="form-control" rows="3"
                        placeholder="г. Москва, ул. Примерная, д. 1, кв. 1"
                        required><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
              <div class="form-text">Укажите полный адрес: город, улица, дом, квартира</div>
            </div>
            <button type="submit" class="btn btn-primary btn-lg btn-block">
              ✅ Подтвердить заказ на <?= formatPrice($subtotal) ?>
            </button>
          </form>
        </div>
      </div>
    </div>

    <div>
      <!-- Итоговая сумма -->
      <div class="card">
        <div class="card-header">💰 Сумма заказа</div>
        <div class="card-body">
          <div class="cart-summary">
            <?php
              $totalRetail = 0;
              foreach ($items as $_i2) { $totalRetail += $_i2['price_retail'] * $_i2['qty']; }
              $saved = $totalRetail - $subtotal;
            ?>
            <div class="cart-summary__row">
              <span>Розничная цена:</span>
              <span style="text-decoration:line-through;color:var(--text-muted);"><?= formatPrice($totalRetail) ?></span>
            </div>
            <div class="cart-summary__row" style="color:var(--success);font-weight:600;">
              <span>Скидка по подписке:</span>
              <span>−<?= formatPrice($saved) ?></span>
            </div>
            <div class="cart-summary__row total">
              <span>К оплате:</span>
              <span><?= formatPrice($subtotal) ?></span>
            </div>
          </div>
          <div class="alert alert-success mt-2" style="font-size:.82rem;">
            🎉 Вы экономите <strong><?= formatPrice($saved) ?></strong> по подписке!
          </div>
        </div>
      </div>

      <div class="card mt-2">
        <div class="card-body" style="font-size:.84rem;color:var(--text-muted);">
          <p>✅ Заказ будет обработан в течение 1–2 рабочих дней</p>
          <p class="mt-1">📞 При необходимости уточнения наш менеджер свяжется с вами</p>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
