<?php
$pageTitle = 'Мои заказы';
require_once __DIR__ . '/header.php';

if (!isLoggedIn()) redirect('login.php');

$db  = getDB();
$uid = $_SESSION['user_id'];

$flashSuccess = flash('success');

$orders = $db->prepare('
    SELECT o.*,
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS items_count
    FROM orders o
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
');
$orders->execute([$uid]);
$orders = $orders->fetchAll();

$statusMap = [
    'pending'    => ['label' => 'Ожидает', 'css' => 'status-pending'],
    'processing' => ['label' => 'В обработке', 'css' => 'status-processing'],
    'completed'  => ['label' => 'Выполнен', 'css' => 'status-completed'],
    'cancelled'  => ['label' => 'Отменён', 'css' => 'status-cancelled'],
];
?>

<div class="container">
  <div class="page-header">
    <h1>📦 Мои заказы</h1>
    <p>История всех ваших покупок в PharmaSub</p>
  </div>

  <?php if ($flashSuccess): ?>
    <div class="alert alert-success">✅ <?= htmlspecialchars($flashSuccess) ?></div>
  <?php endif; ?>

  <?php if (empty($orders)): ?>
    <div class="empty-state">
      <span class="empty-state__icon">📦</span>
      <div class="empty-state__title">У вас пока нет заказов</div>
      <p class="text-muted mt-1">После первой покупки заказы появятся здесь</p>
      <a href="products.php" class="btn btn-primary mt-3">Перейти в каталог</a>
    </div>
  <?php else: ?>
    <div class="card">
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>№ заказа</th>
              <th>Дата</th>
              <th>Товаров</th>
              <th>Сумма</th>
              <th>Адрес</th>
              <th>Статус</th>
              <th>Детали</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($orders as $order):
              $st = $statusMap[$order['status']] ?? ['label' => $order['status'], 'css' => ''];
            ?>
            <tr>
              <td><strong>#<?= $order['id'] ?></strong></td>
              <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
              <td><?= $order['items_count'] ?> поз.</td>
              <td><strong><?= formatPrice($order['total']) ?></strong></td>
              <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                <?= htmlspecialchars($order['delivery_address'] ?? '—') ?>
              </td>
              <td>
                <span class="status-badge <?= $st['css'] ?>"><?= $st['label'] ?></span>
              </td>
              <td>
                <a href="order_detail.php?id=<?= $order['id'] ?>" class="btn btn-outline btn-sm">Детали</a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
