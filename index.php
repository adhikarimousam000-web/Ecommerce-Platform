<?php
require_once __DIR__ . "/../config/db.php";

/* ---------------------------
  Admin Guard (role based)
---------------------------- */
if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "ADMIN") {
  header("Location: ../login.php");
  exit;
}

/* ---------------------------
  Dashboard Stats
---------------------------- */
$productsCount = 0;
$ordersCount   = 0;
$usersCount    = 0;
$totalRevenue  = 0;

$r = $conn->query("SELECT COUNT(*) AS c FROM products");
if ($r) $productsCount = (int)$r->fetch_assoc()["c"];

$r = $conn->query("SELECT COUNT(*) AS c FROM orders");
if ($r) $ordersCount = (int)$r->fetch_assoc()["c"];

$r = $conn->query("SELECT COUNT(*) AS c FROM users");
if ($r) $usersCount = (int)$r->fetch_assoc()["c"];

$r = $conn->query("SELECT IFNULL(SUM(total),0) AS s FROM orders");
if ($r) $totalRevenue = (float)$r->fetch_assoc()["s"];

/* ---------------------------
  Recent Products (latest 6)
---------------------------- */
$recentProducts = $conn->query("SELECT id, name, category, price, discount, stock
                                FROM products
                                ORDER BY id DESC
                                LIMIT 6");

/* ---------------------------
  Recent Orders (latest 6)
---------------------------- */
$recentOrders = $conn->query("SELECT o.id, o.total, o.status, o.created_at,
                                     u.name AS customer_name
                              FROM orders o
                              LEFT JOIN users u ON u.id = o.user_id
                              ORDER BY o.id DESC
                              LIMIT 6");

include __DIR__ . "/header.php";
?>

<style>
  .wrap{max-width:1100px;margin:auto;padding:16px;}
  .topbar{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
  .muted{color:#6b7280}
  .grid{display:grid;gap:14px}
  .stats{grid-template-columns:repeat(4,minmax(0,1fr));margin-top:14px}
  .card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:14px}
  .big{font-size:22px;font-weight:800}
  .actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px}
  .btn{display:inline-block;padding:10px 12px;border-radius:12px;text-decoration:none;border:1px solid #e5e7eb}
  .btn.dark{background:#111827;color:#fff;border-color:#111827}
  .btn.green{background:#22c55e;color:#0b1220;border-color:#22c55e;font-weight:800}
  table{width:100%;border-collapse:collapse}
  th,td{padding:10px;border-bottom:1px solid #eee;text-align:left;font-size:14px}
  th{color:#374151}
  .badge{display:inline-block;padding:4px 8px;border-radius:999px;border:1px solid #e5e7eb;font-size:12px}
  .two{grid-template-columns:1.2fr .8fr;margin-top:14px}
  a.link{color:#0b63ce;text-decoration:none;font-weight:700}
  a.link:hover{text-decoration:underline}
  @media(max-width:950px){.stats{grid-template-columns:repeat(2,1fr)}.two{grid-template-columns:1fr}}
  @media(max-width:520px){.stats{grid-template-columns:1fr}}
</style>

<div class="wrap">
  <div class="topbar">
    <div>
      <h2 style="margin:0;">Admin Dashboard</h2>
      <div class="muted">Welcome, <b><?= htmlspecialchars($_SESSION["name"] ?? "Admin") ?></b> 👋</div>
    </div>
    <div class="actions">
      <a class="btn green" href="products.php">Add Products</a>
      <a class="btn dark" href="orders.php">Manage Orders</a>
      <a class="btn" href="categories.php">Manage Categories</a>
      <a class="btn" href="../login.php">Logout</a>
    </div>
  </div>

  <div class="grid stats">
    <div class="card">
      <div class="muted">Products</div>
      <div class="big"><?= $productsCount ?></div>
    </div>
    <div class="card">
      <div class="muted">Orders</div>
      <div class="big"><?= $ordersCount ?></div>
    </div>
    <div class="card">
      <div class="muted">Users</div>
      <div class="big"><?= $usersCount ?></div>
    </div>
    <div class="card">
      <div class="muted">Revenue</div>
      <div class="big">Rs <?= number_format($totalRevenue, 0) ?></div>
    </div>
  </div>

  <div class="grid two">
    <!-- Recent Products -->
    <div class="card">
      <div style="display:flex;justify-content:space-between;align-items:center;">
        <h3 style="margin:0;">Recent Products</h3>
        <a class="link" href="products.php">Open Products →</a>
      </div>

      <?php if(!$recentProducts || $recentProducts->num_rows === 0): ?>
        <p class="muted">No products yet.</p>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Name</th>
              <th>Category</th>
              <th>Price</th>
              <th>Stock</th>
            </tr>
          </thead>
          <tbody>
            <?php while($p = $recentProducts->fetch_assoc()):
              $final = (float)$p["price"] - (float)$p["discount"];
              if($final < 0) $final = 0;
            ?>
              <tr>
                <td>
                  <a class="link" href="product.php?id=<?= (int)$p["id"] ?>">
                    <?= htmlspecialchars($p["name"]) ?>
                  </a>
                </td>
                <td><?= htmlspecialchars($p["category"]) ?></td>
                <td>Rs <?= number_format($final, 0) ?></td>
                <td><?= (int)$p["stock"] ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <!-- Recent Orders -->
    <div class="card">
      <div style="display:flex;justify-content:space-between;align-items:center;">
        <h3 style="margin:0;">Recent Orders</h3>
        <a class="link" href="orders.php">Open Orders →</a>
      </div>

      <?php if(!$recentOrders || $recentOrders->num_rows === 0): ?>
        <p class="muted">No orders yet.</p>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Order</th>
              <th>Customer</th>
              <th>Total</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php while($o = $recentOrders->fetch_assoc()): ?>
              <tr>
                <td>
                  <a class="link" href="order.php?id=<?= (int)$o["id"] ?>">
                    #<?= (int)$o["id"] ?>
                  </a>
                </td>
                <td><?= htmlspecialchars($o["customer_name"] ?? "Unknown") ?></td>
                <td>Rs <?= number_format((float)$o["total"], 0) ?></td>
                <td><span class="badge"><?= htmlspecialchars($o["status"]) ?></span></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>

</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>