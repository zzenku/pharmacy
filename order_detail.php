<?php
$pageTitle = 'Детали заказа';
require_once __DIR__ . '/header.php';

if (!isLoggedIn()) redirect('login.php');

$db  = getDB();
$uid = $_SESSION['user_id'];
$oid = (int)($_GET['id'] ?? 0);

$order = $db->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');
$order->execute([$oid, $uid]);
$order = $order->fetch();

if (!$order) {
    echo '<div class="container"><div class="alert alert-error">Заказ не найден.</div></div>';
    require_once __DIR__ . '/footer.php';
    exit;
}

$orderItems = $db->prepare('
    SELECT oi.*, p.name, p.unit, cat.icon AS cat_icon
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    JOIN categories cat ON cat.id = p.category_id
    WHERE oi.order_id = ?
');
$orderItems->execute([$oid]);
$orderItems = $orderItems->fetchAll();

$statusMap = [
    'pending'    => ['label' => 'Ожидает обработки', 'css' => 'status-pending', 'icon' => '⏳'],
    'processing' => ['label' => 'В обработке',        'css' => 'status-processing', 'icon' => '🔄'],
    'completed'  => ['label' => 'Выполнен',            'css' => 'status-completed', 'icon' => '✅'],
    'cancelled'  => ['label' => 'Отменён',             'css' => 'status-cancelled', 'icon' => '❌'],
];
$st = $statusMap[$order['status']] ?? ['label' => $order['status'], 'css' => '', 'icon' => ''];
?>

<div class="container" style="max-width:860px;">
  <div class="page-header">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
      <div>
        <h1>📦 Заказ #<?= $order['id'] ?></h1>
        <p>Оформлен: <?= date('d.m.Y в H:i', strtotime($order['created_at'])) ?></p>
      </div>
      <span class="status-badge <?= $st['css'] ?>" style="font-size:.95rem;padding:8px 16px;">
        <?= $st['icon'] ?> <?= $st['label'] ?>
      </span>
    </div>
  </div>

  <div class="two-col">
    <div>
      <div class="card">
        <div class="card-header">💊 Состав заказа</div>
        <div class="card-body" style="padding:0;">
          <table style="width:100%;border-collapse:collapse;">
            <thead>
              <tr>
                <th style="padding:12px 16px;background:var(--primary);color:#fff;text-align:left;">Препарат</th>
                <th style="padding:12px 16px;background:var(--primary);color:#fff;text-align:center;">Кол-во</th>
                <th style="padding:12px 16px;background:var(--primary);color:#fff;text-align:right;">Цена</th>
                <th style="padding:12px 16px;background:var(--primary);color:#fff;text-align:right;">Сумма</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($orderItems as $item): ?>
              <tr style="border-bottom:1px solid var(--border);">
                <td style="padding:12px 16px;">
                  <div style="display:flex;align-items:center;gap:10px;">
                    <span style="font-size:1.4rem;"><?= $item['cat_icon'] ?></span>
                    <div>
                      <div style="font-weight:600;font-size:.9rem;"><?= htmlspecialchars($item['name']) ?></div>
                      <div style="font-size:.77rem;color:var(--text-muted);"><?= htmlspecialchars($item['unit']) ?></div>
                    </div>
                  </div>
                </td>
                <td style="padding:12px 16px;text-align:center;"><?= $item['quantity'] ?></td>
                <td style="padding:12px 16px;text-align:right;"><?= formatPrice($item['price_paid']) ?></td>
                <td style="padding:12px 16px;text-align:right;font-weight:700;color:var(--primary);">
                  <?= formatPrice($item['price_paid'] * $item['quantity']) ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr style="background:#f0f7f3;">
                <td colspan="3" style="padding:14px 16px;font-weight:700;text-align:right;">Итого:</td>
                <td style="padding:14px 16px;text-align:right;font-weight:700;font-size:1.1rem;color:var(--primary);">
                  <?= formatPrice($order['total']) ?>
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <div>
      <div class="card">
        <div class="card-header">📋 Информация о заказе</div>
        <div class="card-body">
          <div style="display:flex;flex-direction:column;gap:14px;font-size:.9rem;">
            <div>
              <div style="font-weight:600;color:var(--text-muted);font-size:.78rem;text-transform:uppercase;margin-bottom:4px;">Номер заказа</div>
              <div class="fw-bold">#<?= $order['id'] ?></div>
            </div>
            <div>
              <div style="font-weight:600;color:var(--text-muted);font-size:.78rem;text-transform:uppercase;margin-bottom:4px;">Дата оформления</div>
              <div><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></div>
            </div>
            <div>
              <div style="font-weight:600;color:var(--text-muted);font-size:.78rem;text-transform:uppercase;margin-bottom:4px;">Статус</div>
              <span class="status-badge <?= $st['css'] ?>"><?= $st['icon'] ?> <?= $st['label'] ?></span>
            </div>
            <div>
              <div style="font-weight:600;color:var(--text-muted);font-size:.78rem;text-transform:uppercase;margin-bottom:4px;">Адрес доставки</div>
              <div><?= nl2br(htmlspecialchars($order['delivery_address'] ?? '—')) ?></div>
            </div>
            <div>
              <div style="font-weight:600;color:var(--text-muted);font-size:.78sm;text-transform:uppercase;margin-bottom:4px;">Сумма заказа</div>
              <div class="fw-bold" style="font-size:1.2rem;color:var(--primary);"><?= formatPrice($order['total']) ?></div>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-2">
        <a href="orders.php" class="btn btn-outline btn-block">← Все заказы</a>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
