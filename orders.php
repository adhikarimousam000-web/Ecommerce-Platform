<?php
require_once __DIR__ . "/auth.php";          // admin guard
require_once __DIR__ . "/../config/db.php";  // db + session
include __DIR__ . "/header.php";
// Handle status update
if(isset($_POST['update_status']) && isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = $conn->real_escape_string($_POST['status']);
    
    $update_stmt = $conn->prepare("UPDATE orders SET status=? WHERE id=?");
    $update_stmt->bind_param("si", $status, $order_id);
    
    if($update_stmt->execute()) {
        $success_msg = "Order #$order_id status updated to $status successfully!";
    } else {
        $error_msg = "Error updating order status.";
    }
}

// Get all orders with user details
$orders = $conn->query("
    SELECT o.*, u.name as user_name, u.email as user_email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.id DESC
");
?>

<style>
    .orders-container {
        max-width: 1200px;
        margin: 30px auto;
        padding: 0 20px;
    }

    h2 {
        font-size: 28px;
        margin-bottom: 25px;
        color: #1a1a1a;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    h2 i {
        color: #667eea;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        border: 1px solid #eaeaea;
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }

    .stat-card .stat-label {
        font-size: 14px;
        color: #6c757d;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-card .stat-value {
        font-size: 28px;
        font-weight: 700;
        color: #1a1a1a;
    }

    .stat-card .stat-icon {
        float: right;
        font-size: 30px;
        color: #667eea;
        opacity: 0.5;
    }

    .filter-section {
        background: white;
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 25px;
        border: 1px solid #eaeaea;
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        align-items: center;
    }

    .filter-section select, .filter-section input {
        padding: 10px 15px;
        border: 1px solid #eaeaea;
        border-radius: 10px;
        font-size: 14px;
        min-width: 200px;
    }

    .filter-section button {
        padding: 10px 25px;
        background: #1a1a1a;
        color: white;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .filter-section button:hover {
        background: #333;
    }

    .alert {
        padding: 15px 20px;
        border-radius: 12px;
        margin-bottom: 20px;
        font-weight: 500;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .orders-table {
        background: white;
        border-radius: 20px;
        border: 1px solid #eaeaea;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th {
        background: #f8f9fa;
        padding: 16px;
        text-align: left;
        font-weight: 600;
        font-size: 14px;
        color: #1a1a1a;
        border-bottom: 2px solid #eaeaea;
    }

    td {
        padding: 16px;
        border-bottom: 1px solid #eaeaea;
        color: #4a4a4a;
        font-size: 14px;
    }

    tr:hover td {
        background: #f8f9fa;
    }

    .user-info {
        display: flex;
        flex-direction: column;
    }

    .user-name {
        font-weight: 600;
        color: #1a1a1a;
    }

    .user-email {
        font-size: 12px;
        color: #6c757d;
    }

    .status-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 30px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-pending { background: #fff3cd; color: #856404; }
    .status-processing { background: #cce5ff; color: #004085; }
    .status-shipped { background: #d1ecf1; color: #0c5460; }
    .status-delivered { background: #d4edda; color: #155724; }
    .status-cancelled { background: #f8d7da; color: #721c24; }

    .status-select {
        padding: 8px 12px;
        border: 1px solid #eaeaea;
        border-radius: 8px;
        font-size: 13px;
        background: white;
        cursor: pointer;
    }

    .update-btn {
        padding: 8px 15px;
        background: #1a1a1a;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-left: 8px;
    }

    .update-btn:hover {
        background: #333;
    }

    .amount {
        font-weight: 600;
        color: #1a1a1a;
    }

    .payment-method {
        display: inline-block;
        padding: 4px 8px;
        background: #f0f0f0;
        border-radius: 6px;
        font-size: 12px;
        color: #666;
    }

    .no-orders {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 20px;
        border: 1px solid #eaeaea;
    }

    .no-orders i {
        font-size: 60px;
        color: #ddd;
        margin-bottom: 20px;
    }

    .no-orders p {
        color: #6c757d;
        font-size: 16px;
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        table {
            display: block;
            overflow-x: auto;
        }
        
        .filter-section {
            flex-direction: column;
            align-items: stretch;
        }
        
        .filter-section select, .filter-section input {
            width: 100%;
        }
    }
</style>

<div class="orders-container">
    <h2>
        <i class="fas fa-shopping-bag"></i>
        Manage Orders
    </h2>

    <?php if(isset($success_msg)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?= $success_msg ?>
        </div>
    <?php endif; ?>

    <?php if(isset($error_msg)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?= $error_msg ?>
        </div>
    <?php endif; ?>

    <?php
    // Calculate stats
    $stats = $conn->query("
        SELECT 
            COUNT(*) as total_orders,
            SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending_orders,
            SUM(CASE WHEN status='delivered' THEN 1 ELSE 0 END) as delivered_orders,
            SUM(total) as total_revenue
        FROM orders
    ")->fetch_assoc();
    ?>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
            <div class="stat-label">Total Orders</div>
            <div class="stat-value"><?= $stats['total_orders'] ?? 0 ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-label">Pending</div>
            <div class="stat-value"><?= $stats['pending_orders'] ?? 0 ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-label">Delivered</div>
            <div class="stat-value"><?= $stats['delivered_orders'] ?? 0 ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-rupee-sign"></i></div>
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value">Rs <?= number_format($stats['total_revenue'] ?? 0) ?></div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <select id="statusFilter" onchange="filterOrders()">
            <option value="">All Status</option>
            <option value="pending">Pending</option>
            <option value="processing">Processing</option>
            <option value="shipped">Shipped</option>
            <option value="delivered">Delivered</option>
            <option value="cancelled">Cancelled</option>
        </select>
        
        <input type="text" id="searchInput" placeholder="Search by order ID or customer..." onkeyup="filterOrders()">
        
        <button onclick="filterOrders()">
            <i class="fas fa-filter"></i> Apply Filters
        </button>
        
        <button onclick="resetFilters()" style="background: #6c757d;">
            <i class="fas fa-undo"></i> Reset
        </button>
    </div>

    <?php if($orders->num_rows == 0): ?>
        <div class="no-orders">
            <i class="fas fa-box-open"></i>
            <p>No orders found.</p>
        </div>
    <?php else: ?>
        <div class="orders-table">
            <table id="ordersTable">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Total Amount</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($o = $orders->fetch_assoc()): 
                        $status_class = '';
                        switch(strtolower($o['status'])) {
                            case 'pending': $status_class = 'status-pending'; break;
                            case 'processing': $status_class = 'status-processing'; break;
                            case 'shipped': $status_class = 'status-shipped'; break;
                            case 'delivered': $status_class = 'status-delivered'; break;
                            case 'cancelled': $status_class = 'status-cancelled'; break;
                        }
                    ?>
                    <tr class="order-row" data-status="<?= strtolower($o['status']) ?>" data-search="<?= strtolower($o['id'] . ' ' . $o['user_name'] . ' ' . $o['user_email']) ?>">
                        <td><strong>#<?= $o['id'] ?></strong></td>
                        <td>
                            <div class="user-info">
                                <span class="user-name"><?= htmlspecialchars($o['user_name']) ?></span>
                                <span class="user-email"><?= htmlspecialchars($o['user_email']) ?></span>
                            </div>
                        </td>
                        <td class="amount">Rs <?= number_format($o['total'], 2) ?></td>
                        <td>
                            <span class="payment-method">
                                <?= ucfirst($o['payment_method']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge <?= $status_class ?>">
                                <?= ucfirst($o['status']) ?>
                            </span>
                        </td>
                        <td><?= date('M d, Y H:i', strtotime($o['created_at'])) ?></td>
                        <td>
                            <form method="POST" style="display: flex; gap: 8px; align-items: center;">
                                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                <select name="status" class="status-select" required>
                                    <option value="pending" <?= $o['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="processing" <?= $o['status'] == 'processing' ? 'selected' : '' ?>>Processing</option>
                                    <option value="shipped" <?= $o['status'] == 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                    <option value="delivered" <?= $o['status'] == 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                    <option value="cancelled" <?= $o['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                                <button type="submit" name="update_status" class="update-btn">
                                    <i class="fas fa-save"></i> Update
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
function filterOrders() {
    const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
    const searchInput = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('.order-row');
    
    rows.forEach(row => {
        const status = row.getAttribute('data-status');
        const searchText = row.getAttribute('data-search');
        
        const statusMatch = !statusFilter || status === statusFilter;
        const searchMatch = !searchInput || searchText.includes(searchInput);
        
        if (statusMatch && searchMatch) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function resetFilters() {
    document.getElementById('statusFilter').value = '';
    document.getElementById('searchInput').value = '';
    filterOrders();
}

// Auto-refresh every 30 seconds (optional)
setInterval(() => {
    location.reload();
}, 30000);
</script>

<?php include "includes/footer.php"; ?>