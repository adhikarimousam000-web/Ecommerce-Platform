<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . "/auth.php";          // admin guard
require_once __DIR__ . "/../config/db.php";  // db + session
include __DIR__ . "/header.php";

$msg = "";

/* -----------------------------
   HANDLE ADD CATEGORY
------------------------------ */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    
    // ADD CATEGORY
    if ($_POST["action"] === "add") {
        $name = trim($_POST["name"] ?? "");
        
        if ($name === "") {
            $msg = "<div class='alert error'><i class='fas fa-exclamation-circle'></i> Category name is required.</div>";
        } else {
            // Check if category already exists
            $check = $conn->prepare("SELECT id FROM categories WHERE name = ?");
            $check->bind_param("s", $name);
            $check->execute();
            $check->store_result();
            
            if ($check->num_rows > 0) {
                $msg = "<div class='alert error'><i class='fas fa-times-circle'></i> Category '{$name}' already exists!</div>";
            } else {
                $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
                $stmt->bind_param("s", $name);
                
                if ($stmt->execute()) {
                    $msg = "<div class='alert ok'><i class='fas fa-check-circle'></i> Category added successfully!</div>";
                } else {
                    $msg = "<div class='alert error'><i class='fas fa-exclamation-triangle'></i> DB Error: " . htmlspecialchars($conn->error) . "</div>";
                }
            }
        }
    }
    
    // EDIT CATEGORY
    elseif ($_POST["action"] === "edit") {
        $id = (int)($_POST["id"] ?? 0);
        $name = trim($_POST["name"] ?? "");
        
        if ($id <= 0 || $name === "") {
            $msg = "<div class='alert error'><i class='fas fa-exclamation-circle'></i> Invalid data.</div>";
        } else {
            // Check if another category with same name exists (excluding current)
            $check = $conn->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
            $check->bind_param("si", $name, $id);
            $check->execute();
            $check->store_result();
            
            if ($check->num_rows > 0) {
                $msg = "<div class='alert error'><i class='fas fa-times-circle'></i> Category '{$name}' already exists!</div>";
            } else {
                $stmt = $conn->prepare("UPDATE categories SET name = ? WHERE id = ?");
                $stmt->bind_param("si", $name, $id);
                
                if ($stmt->execute()) {
                    $msg = "<div class='alert ok'><i class='fas fa-check-circle'></i> Category updated successfully!</div>";
                } else {
                    $msg = "<div class='alert error'><i class='fas fa-exclamation-triangle'></i> DB Error: " . htmlspecialchars($conn->error) . "</div>";
                }
            }
        }
    }
}

/* -----------------------------
   HANDLE DELETE CATEGORY
------------------------------ */
if (isset($_GET["delete"])) {
    $id = (int)$_GET["delete"];
    
    // Check if category is used in any products
    $check = $conn->prepare("SELECT id FROM products WHERE category = (SELECT name FROM categories WHERE id = ?) LIMIT 1");
    $check->bind_param("i", $id);
    $check->execute();
    $check->store_result();
    
    if ($check->num_rows > 0) {
        $msg = "<div class='alert error'><i class='fas fa-ban'></i> Cannot delete - category is used in products!</div>";
    } else {
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $msg = "<div class='alert ok'><i class='fas fa-trash-alt'></i> Category deleted successfully!</div>";
        } else {
            $msg = "<div class='alert error'><i class='fas fa-exclamation-triangle'></i> DB Error: " . htmlspecialchars($conn->error) . "</div>";
        }
    }
}

/* -----------------------------
   FETCH ALL CATEGORIES
------------------------------ */
$categories = $conn->query("SELECT * FROM categories ORDER BY id DESC");
?>

<!-- Font Awesome (already in header but ensuring icons work) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
    .wrap {
        max-width: 1000px;
        margin: 30px auto;
        padding: 0 20px;
    }

    .topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 25px;
    }

    .title h2 {
        margin: 0;
        font-size: 28px;
        color: #1a1a1a;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .title h2 i {
        color: #667eea;
    }

    .muted {
        color: #6b7280;
        font-size: 13px;
        margin-top: 4px;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        background: #111827;
        color: #fff;
        cursor: pointer;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .btn:hover {
        background: #1f2937;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .btn i {
        font-size: 14px;
    }

    .alert {
        padding: 15px 20px;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        margin: 20px 0;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .alert.ok {
        background: #ecfdf5;
        border-color: #bbf7d0;
        color: #065f46;
    }

    .alert.error {
        background: #fff1f2;
        border-color: #fecdd3;
        color: #9f1239;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        border: 1px solid #eaeaea;
        box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.05);
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
        opacity: 0.3;
    }

    .tableWrap {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.02);
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th, td {
        padding: 16px;
        border-bottom: 1px solid #f1f5f9;
        text-align: left;
        font-size: 14px;
        vertical-align: middle;
    }

    th {
        background: #f8fafc;
        color: #334155;
        font-weight: 600;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    th i {
        margin-right: 6px;
        color: #667eea;
    }

    tr:hover td {
        background: #fafafa;
    }

    .category-name {
        font-weight: 600;
        color: #1a1a1a;
        font-size: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .category-name i {
        color: #667eea;
        font-size: 16px;
    }

    .product-count {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        background: #f1f5f9;
        border-radius: 30px;
        font-size: 12px;
        font-weight: 500;
        color: #475569;
    }

    .product-count i {
        color: #667eea;
    }

    .actions {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .iconBtn {
        border: none;
        background: transparent;
        cursor: pointer;
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        text-decoration: none;
    }

    .iconBtn:hover {
        background: #f1f5f9;
        transform: scale(1.1);
    }

    .iconBtn.edit {
        color: #2563eb;
    }

    .iconBtn.delete {
        color: #dc2626;
    }

    .iconBtn.view {
        color: #6b7280;
    }

    .iconBtn.disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }

    .iconBtn.disabled:hover {
        background: transparent;
        transform: none;
    }

    /* Modal Styles */
    .back {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.55);
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
        z-index: 9999;
        backdrop-filter: blur(4px);
    }

    .modal {
        width: 450px;
        max-width: 100%;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        animation: modalSlide 0.3s ease;
    }

    @keyframes modalSlide {
        from {
            transform: translateY(-30px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .modalTop {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eaeaea;
    }

    .modalTop h3 {
        margin: 0;
        font-size: 20px;
        color: #1a1a1a;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .modalTop h3 i {
        color: #667eea;
    }

    .closeBtn {
        border: none;
        background: #f1f5f9;
        border-radius: 10px;
        padding: 8px 12px;
        cursor: pointer;
        transition: all 0.2s ease;
        color: #4a5568;
    }

    .closeBtn:hover {
        background: #e2e8f0;
        transform: rotate(90deg);
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #4a5568;
        font-size: 13px;
        font-weight: 500;
    }

    .form-group label i {
        margin-right: 6px;
        color: #667eea;
    }

    .input {
        width: 100%;
        padding: 12px 14px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        font-size: 14px;
        transition: all 0.2s ease;
        box-sizing: border-box;
    }

    .input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
    }

    .input i {
        margin-right: 8px;
        color: #94a3b8;
    }

    .saveBtn {
        width: 100%;
        padding: 14px;
        border: none;
        border-radius: 12px;
        background: #22c55e;
        color: #0b1220;
        font-weight: 600;
        font-size: 15px;
        cursor: pointer;
        transition: all 0.2s ease;
        margin-top: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .saveBtn:hover {
        background: #16a34a;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(34,197,94,0.3);
    }

    .cancelBtn {
        width: 100%;
        padding: 14px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #fff;
        color: #4a5568;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s ease;
        margin-top: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .cancelBtn:hover {
        background: #f8fafc;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6b7280;
    }

    .empty-state i {
        font-size: 60px;
        color: #e2e8f0;
        margin-bottom: 20px;
    }

    .empty-state p {
        font-size: 16px;
        margin-bottom: 20px;
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        table {
            display: block;
            overflow-x: auto;
        }
        
        .category-name {
            min-width: 150px;
        }
    }
</style>

<div class="wrap">
    <div class="topbar">
        <div class="title">
            <h2><i class="fas fa-tags"></i> Manage Categories</h2>
            <div class="muted"><i class="fas fa-info-circle"></i> Add, edit, and delete product categories</div>
        </div>
        <button class="btn" onclick="openAddModal()">
            <i class="fas fa-plus-circle"></i> Add Category
        </button>
    </div>

    <?= $msg ?>

    <?php
    // Get statistics
    $total_cats = $conn->query("SELECT COUNT(*) as count FROM categories")->fetch_assoc()['count'];
    $total_products = $conn->query("SELECT COUNT(DISTINCT category) as count FROM products")->fetch_assoc()['count'];
    $most_used = $conn->query("SELECT category, COUNT(*) as count FROM products GROUP BY category ORDER BY count DESC LIMIT 1")->fetch_assoc();
    ?>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-folder"></i></div>
            <div class="stat-label"><i class="fas fa-layer-group"></i> Total Categories</div>
            <div class="stat-value"><?= $total_cats ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-cubes"></i></div>
            <div class="stat-label"><i class="fas fa-box"></i> Categories with Products</div>
            <div class="stat-value"><?= $total_products ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-star"></i></div>
            <div class="stat-label"><i class="fas fa-fire"></i> Most Used</div>
            <div class="stat-value"><?= $most_used ? htmlspecialchars($most_used['category']) : '—' ?></div>
        </div>
    </div>

    <div class="tableWrap">
        <table>
            <thead>
                <tr>
                    <th><i class="fas fa-hashtag"></i> ID</th>
                    <th><i class="fas fa-tag"></i> Category Name</th>
                    <th><i class="fas fa-cube"></i> Products</th>
                    <th><i class="fas fa-calendar"></i> Created</th>
                    <th><i class="fas fa-cog"></i> Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($categories && $categories->num_rows > 0): ?>
                    <?php while($cat = $categories->fetch_assoc()): 
                        // Count products in this category
                        $count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE category = ?");
                        $count_stmt->bind_param("s", $cat['name']);
                        $count_stmt->execute();
                        $product_count = $count_stmt->get_result()->fetch_assoc()['count'];
                    ?>
                    <tr>
                        <td><span class="category-name"><i class="fas fa-hashtag" style="opacity:0.5;"></i> <?= $cat['id'] ?></span></td>
                        <td>
                            <span class="category-name">
                                <i class="fas fa-tag"></i> 
                                <?= htmlspecialchars($cat['name']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="product-count">
                                <i class="fas fa-<?= $product_count > 0 ? 'box-open' : 'box' ?>"></i>
                                <?= $product_count ?> <?= $product_count == 1 ? 'product' : 'products' ?>
                            </span>
                        </td>
                        <td class="muted">
                            <i class="fas fa-clock"></i>
                            <?= date('M d, Y', strtotime($cat['created_at'] ?? 'now')) ?>
                        </td>
                        <td>
                            <div class="actions">
                                <button class="iconBtn edit" title="Edit Category" 
                                        onclick="openEditModal(<?= $cat['id'] ?>, '<?= htmlspecialchars(addslashes($cat['name'])) ?>')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                
                                <?php if ($product_count == 0): ?>
                                    <a class="iconBtn delete" title="Delete Category"
                                       href="?delete=<?= $cat['id'] ?>"
                                       onclick="return confirm('Delete category \'<?= htmlspecialchars($cat['name']) ?>\'? This action cannot be undone.')">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="iconBtn delete disabled" title="Cannot delete - has products">
                                        <i class="fas fa-trash-alt"></i>
                                    </span>
                                <?php endif; ?>
                                
                                <a class="iconBtn view" title="View Products" href="products.php?cat=<?= urlencode($cat['name']) ?>">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <i class="fas fa-folder-open"></i>
                                <p>No categories yet. Create your first category!</p>
                                <button class="btn" onclick="openAddModal()" style="display: inline-flex;">
                                    <i class="fas fa-plus-circle"></i> Add Category
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ADD MODAL -->
<div class="back" id="addBack">
    <div class="modal">
        <div class="modalTop">
            <h3><i class="fas fa-plus-circle"></i> Add New Category</h3>
            <button class="closeBtn" onclick="closeAddModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label><i class="fas fa-tag"></i> Category Name *</label>
                <div style="position: relative;">
                    <i class="fas fa-folder" style="position: absolute; left: 12px; top: 14px; color: #94a3b8;"></i>
                    <input class="input" type="text" name="name" placeholder="e.g., Shoes, Jerseys, Accessories" 
                           style="padding-left: 35px;" required>
                </div>
            </div>
            
            <button type="submit" class="saveBtn">
                <i class="fas fa-save"></i> Create Category
            </button>
            <button type="button" class="cancelBtn" onclick="closeAddModal()">
                <i class="fas fa-times"></i> Cancel
            </button>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="back" id="editBack">
    <div class="modal">
        <div class="modalTop">
            <h3><i class="fas fa-edit"></i> Edit Category</h3>
            <button class="closeBtn" onclick="closeEditModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="form-group">
                <label><i class="fas fa-tag"></i> Category Name *</label>
                <div style="position: relative;">
                    <i class="fas fa-folder" style="position: absolute; left: 12px; top: 14px; color: #94a3b8;"></i>
                    <input class="input" type="text" name="name" id="edit_name" 
                           style="padding-left: 35px;" required>
                </div>
            </div>
            
            <button type="submit" class="saveBtn">
                <i class="fas fa-save"></i> Update Category
            </button>
            <button type="button" class="cancelBtn" onclick="closeEditModal()">
                <i class="fas fa-times"></i> Cancel
            </button>
        </form>
    </div>
</div>

<script>
    const addBack = document.getElementById("addBack");
    const editBack = document.getElementById("editBack");

    function openAddModal() {
        addBack.style.display = "flex";
    }

    function closeAddModal() {
        addBack.style.display = "none";
    }

    function openEditModal(id, name) {
        document.getElementById("edit_id").value = id;
        document.getElementById("edit_name").value = name;
        editBack.style.display = "flex";
    }

    function closeEditModal() {
        editBack.style.display = "none";
    }

    // Close modals when clicking outside
    addBack.addEventListener("click", (e) => {
        if (e.target === addBack) closeAddModal();
    });

    editBack.addEventListener("click", (e) => {
        if (e.target === editBack) closeEditModal();
    });

    // Close with Escape key
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
            closeAddModal();
            closeEditModal();
        }
    });
</script>

<?php include __DIR__ . "/../includes/footer.php"; ?>