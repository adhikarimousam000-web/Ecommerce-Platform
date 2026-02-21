<?php
require_once "config/db.php";
require_once "includes/user_auth.php";
include "includes/header.php";

$uid = $_SESSION["user_id"];
$msg = "";

// Remove item
if(isset($_GET["remove"])){
  $cid = (int)$_GET["remove"];
  $stmt = $conn->prepare("DELETE FROM cart WHERE id=? AND user_id=?");
  $stmt->bind_param("ii",$cid,$uid);
  $stmt->execute();
  header("Location: cart.php");
  exit;
}

// Update quantities
if($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_cart"])){
  foreach($_POST["qty"] as $cartId => $qty){
    $cartId = (int)$cartId;
    $qty = max(1, (int)$qty);

    $stmt = $conn->prepare("UPDATE cart SET qty=? WHERE id=? AND user_id=?");
    $stmt->bind_param("iii",$qty,$cartId,$uid);
    $stmt->execute();
  }
  $msg = "<div class='success-message'>✓ Cart updated successfully</div>";
}

$sql = "
SELECT c.id as cart_id, c.qty, c.size,
       p.id as product_id, p.name, p.image,
       (p.price - p.discount) as price
FROM cart c
JOIN products p ON p.id = c.product_id
WHERE c.user_id=?
ORDER BY c.id DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$uid);
$stmt->execute();
$items = $stmt->get_result();

$subtotal = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #f5f5f5;
            color: #1a1a1a;
            line-height: 1.6;
        }

        .cart-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }

        .cart-header h2 {
            font-size: 28px;
            font-weight: 500;
            color: #000;
        }

        .cart-header h2 i {
            margin-right: 10px;
            opacity: 0.7;
        }

        .continue-shopping {
            color: #000;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 1px solid #000;
            padding-bottom: 4px;
        }

        .continue-shopping:hover {
            opacity: 0.7;
        }

        .success-message {
            background: #f0f0f0;
            border: 1px solid #d0d0d0;
            color: #000;
            padding: 12px 20px;
            margin-bottom: 25px;
            font-size: 14px;
        }

        /* Cart Table */
        .cart-table {
            width: 100%;
            background: white;
            border: 1px solid #e0e0e0;
            margin-bottom: 30px;
        }

        .cart-table th {
            text-align: left;
            padding: 16px 20px;
            background: #fafafa;
            border-bottom: 2px solid #e0e0e0;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #333;
        }

        .cart-table td {
            padding: 20px;
            border-bottom: 1px solid #eaeaea;
            vertical-align: middle;
        }

        .cart-table tr:last-child td {
            border-bottom: none;
        }

        /* Product Image */
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border: 1px solid #eaeaea;
            background: #fafafa;
        }

        .no-image {
            width: 80px;
            height: 80px;
            border: 1px solid #eaeaea;
            background: #fafafa;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 24px;
        }

        /* Product Info */
        .product-name {
            font-weight: 600;
            color: #000;
            text-decoration: none;
            font-size: 16px;
            margin-bottom: 5px;
            display: block;
        }

        .product-name:hover {
            text-decoration: underline;
        }

        .product-meta {
            font-size: 13px;
            color: #666;
        }

        .product-meta span {
            display: inline-block;
            margin-right: 15px;
        }

        .product-meta i {
            margin-right: 5px;
            opacity: 0.6;
        }

        /* Quantity Input */
        .quantity-input {
            width: 80px;
            padding: 8px 10px;
            border: 1px solid #d0d0d0;
            background: white;
            font-size: 14px;
            text-align: center;
        }

        .quantity-input:focus {
            outline: none;
            border-color: #000;
        }

        /* Price & Total */
        .price, .line-total {
            font-weight: 500;
            color: #000;
        }

        .line-total {
            font-size: 16px;
        }

        /* Remove Button */
        .remove-btn {
            color: #999;
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            padding: 5px;
            transition: color 0.2s;
        }

        .remove-btn:hover {
            color: #ff0000;
        }

        /* Update Cart Button */
        .update-cart {
            background: #000;
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 14px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            border: 1px solid #000;
            transition: all 0.2s;
        }

        .update-cart:hover {
            background: white;
            color: #000;
        }

        /* Cart Summary */
        .cart-summary {
            margin-top: 40px;
            border-top: 2px solid #e0e0e0;
            padding-top: 30px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 30px;
        }

        .summary-card {
            background: white;
            border: 1px solid #e0e0e0;
            padding: 25px;
        }

        .summary-card h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eaeaea;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 15px;
            color: #333;
        }

        .summary-row.total {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #eaeaea;
            font-weight: 700;
            font-size: 18px;
            color: #000;
        }

        .checkout-btn {
            display: block;
            width: 100%;
            background: #000;
            color: white;
            text-align: center;
            padding: 15px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: 1px solid #000;
            transition: all 0.2s;
            margin-top: 20px;
        }

        .checkout-btn:hover {
            background: white;
            color: #000;
        }

        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border: 1px solid #e0e0e0;
        }

        .empty-cart i {
            font-size: 64px;
            color: #ccc;
            margin-bottom: 20px;
        }

        .empty-cart p {
            color: #666;
            margin-bottom: 25px;
            font-size: 16px;
        }

        .shop-now-btn {
            display: inline-block;
            background: #000;
            color: white;
            text-decoration: none;
            padding: 12px 30px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: 1px solid #000;
            transition: all 0.2s;
        }

        .shop-now-btn:hover {
            background: white;
            color: #000;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .cart-table {
                display: block;
                overflow-x: auto;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .cart-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }

        /* Font Awesome Icons (using system fonts as fallback) */
        .fas, .far {
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
        }
    </style>
    <!-- Add Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="cart-container">
        <div class="cart-header">
            <h2>
                <i class="fas fa-shopping-cart"></i> Shopping Cart
            </h2>
            <a href="products.php" class="continue-shopping">
                <i class="fas fa-arrow-left"></i> Continue Shopping
            </a>
        </div>

        <?= $msg ?>

        <?php if($items->num_rows == 0): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-bag"></i>
                <p>Your cart is currently empty.</p>
                <a href="products.php" class="shop-now-btn">Shop Now</a>
            </div>
        <?php else: ?>

        <form method="POST">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Size</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($it = $items->fetch_assoc()): 
                        $line = $it["price"] * $it["qty"];
                        $subtotal += $line;
                    ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <?php if(!empty($it["image"]) && $it["image"] !== "no-image.png"): ?>
                                        <img src="uploads/<?= htmlspecialchars($it["image"]) ?>" 
                                             alt="<?= htmlspecialchars($it["name"]) ?>"
                                             class="product-image">
                                    <?php else: ?>
                                        <div class="no-image">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <a href="product.php?id=<?= $it["product_id"] ?>" class="product-name">
                                            <?= htmlspecialchars($it["name"]) ?>
                                        </a>
                                        <div class="product-meta">
                                            <span>
                                                <i class="fas fa-box"></i> 
                                                SKU: SP-<?= str_pad($it["product_id"], 4, '0', STR_PAD_LEFT) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span style="font-weight: 500; padding: 4px 8px; background: #f0f0f0; border: 1px solid #d0d0d0;">
                                    <?= htmlspecialchars($it["size"]) ?>
                                </span>
                            </td>
                            <td class="price">Rs <?= number_format($it["price"], 0) ?></td>
                            <td>
                                <input class="quantity-input" 
                                       type="number" 
                                       name="qty[<?= $it["cart_id"] ?>]"
                                       value="<?= (int)$it["qty"] ?>" 
                                       min="1"
                                       required>
                            </td>
                            <td class="line-total">Rs <?= number_format($line, 0) ?></td>
                            <td>
                                <a href="cart.php?remove=<?= $it["cart_id"] ?>" 
                                   class="remove-btn"
                                   onclick="return confirm('Remove this item from your cart?')"
                                   title="Remove item">
                                    <i class="fas fa-times"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <div style="text-align: right;">
                <button type="submit" name="update_cart" class="update-cart">
                    <i class="fas fa-sync-alt"></i> Update Cart
                </button>
            </div>
        </form>

        <?php
        $delivery = 100;
        $total = $subtotal + $delivery;
        ?>

        <div class="cart-summary">
            <div class="summary-grid">
                <div class="summary-card">
                    <h3>Order Summary</h3>
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>Rs <?= number_format($subtotal, 0) ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Delivery</span>
                        <span>Rs <?= number_format($delivery, 0) ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Total</span>
                        <span>Rs <?= number_format($total, 0) ?></span>
                    </div>
                </div>

                <div class="summary-card">
                    <h3>Checkout</h3>
                    <a href="checkout.php" class="checkout-btn">
                        <i class="fas fa-lock"></i> Proceed to Checkout
                    </a>
                    <p style="text-align: center; margin-top: 15px; color: #666; font-size: 13px;">
                        <i class="fas fa-truck"></i> Cash on Delivery available
                    </p>
                </div>
            </div>
        </div>

        <?php endif; ?>
    </div>

    <?php include "includes/footer.php"; ?>
</body>
</html>