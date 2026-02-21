<?php
require_once "config/db.php";
require_once "includes/user_auth.php";
// include "includes/header.php";

$uid = $_SESSION["user_id"];
$msg = "";

// Load cart
$sql = "
SELECT c.id as cart_id, c.qty, c.size,
       p.id as product_id, p.name,
       (p.price - p.discount) as price,
       p.stock
FROM cart c
JOIN products p ON p.id = c.product_id
WHERE c.user_id=?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$uid);
$stmt->execute();
$cartItems = $stmt->get_result();

if($cartItems->num_rows == 0){
  echo "<p>Your cart is empty.</p>";
  include "includes/footer.php";
  exit;
}

$items = [];
$subtotal = 0;
while($row = $cartItems->fetch_assoc()){
  $items[] = $row;
  $subtotal += $row["price"] * $row["qty"];
}

$delivery = 100;
$total = $subtotal + $delivery;

if($_SERVER["REQUEST_METHOD"] === "POST"){
  $phone = trim($_POST["phone"]);
  $address = trim($_POST["address"]);

  if($phone=="" || $address==""){
    $msg = "<p style='color:red;'>Phone and address required</p>";
  } else {

    // Start transaction
    $conn->begin_transaction();

    try {
      // Update user info
      $up = $conn->prepare("UPDATE users SET phone=?, address=? WHERE id=?");
      $up->bind_param("ssi",$phone,$address,$uid);
      $up->execute();

      // Check stock
      foreach($items as $it){
        if($it["qty"] > $it["stock"]){
          throw new Exception("Not enough stock for: " . $it["name"]);
        }
      }

      // Create order
      $order = $conn->prepare("INSERT INTO orders(user_id,total,delivery_charge,payment_method,status)
                               VALUES(?,?,?,'COD','Pending')");
      $order->bind_param("idd",$uid,$total,$delivery);
      $order->execute();
      $order_id = $conn->insert_id;

      // Insert order items + reduce stock
      foreach($items as $it){
        $oi = $conn->prepare("INSERT INTO order_items(order_id,product_id,qty,price,size)
                              VALUES(?,?,?,?,?)");
        $oi->bind_param("iiids",$order_id,$it["product_id"],$it["qty"],$it["price"],$it["size"]);
        $oi->execute();

        $newStock = $it["stock"] - $it["qty"];
        $st = $conn->prepare("UPDATE products SET stock=? WHERE id=?");
        $st->bind_param("ii",$newStock,$it["product_id"]);
        $st->execute();
      }

      // Clear cart
      $clr = $conn->prepare("DELETE FROM cart WHERE user_id=?");
      $clr->bind_param("i",$uid);
      $clr->execute();

      $conn->commit();

      header("Location: orders.php?success=1");
      exit;

    } catch (Exception $e) {
      $conn->rollback();
      $msg = "<p style='color:red;'>Checkout failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
  }
}
?>

<h2>Checkout (Cash on Delivery)</h2>
<?= $msg ?>

<div class="card">
  <h3>Order Summary</h3>
  <p>Subtotal: Rs <?= $subtotal ?></p>
  <p>Delivery: Rs <?= $delivery ?></p>
  <p><b>Total: Rs <?= $total ?></b></p>
</div>

<div class="card">
  <h3>Shipping Details</h3>
  <form method="POST">
    <input class="input" name="phone" placeholder="Phone" required><br><br>
    <textarea class="input" name="address" placeholder="Full Address" style="height:90px;" required></textarea><br><br>
    <button class="btn" type="submit">Place Order (COD)</button>
  </form>
</div>

<?php include "includes/footer.php"; ?>
