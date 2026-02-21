<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . "/auth.php";          // admin guard
require_once __DIR__ . "/../config/db.php";  // db + session
// include __DIR__ . "/../includes/header.php"; // your layout
include __DIR__ . "/header.php";
$msg = "";

/* -----------------------------
   Fetch Categories from DB
------------------------------ */
$categories = [];
$catRes = $conn->query("SELECT name FROM categories ORDER BY name ASC");
if ($catRes) {
  while ($c = $catRes->fetch_assoc()) $categories[] = $c["name"];
}
if (count($categories) === 0) {
  // fallback if table empty
  $categories = ["Shoes", "Jerseys", "Kits", "Socks", "Accessories"];
}

/* -----------------------------
   Helpers
------------------------------ */
function safe_filename($name) {
  $name = basename($name);
  return preg_replace("/[^a-zA-Z0-9._-]/", "_", $name);
}

function get_sizes_csv_from_post(): string {
  $arr = $_POST["sizes_arr"] ?? [];
  if (!is_array($arr)) $arr = [];
  $arr = array_values(array_unique(array_map("trim", $arr)));
  $arr = array_filter($arr, fn($v) => $v !== "");
  return implode(",", $arr);
}

/* -----------------------------
   DELETE PRODUCT
------------------------------ */
if (isset($_GET["delete"])) {
  $id = (int)$_GET["delete"];

  // get old image
  $stmt = $conn->prepare("SELECT image FROM products WHERE id=? LIMIT 1");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();

  if ($row) {
    $img = $row["image"] ?? "";
    if ($img && $img !== "no-image.png") {
      $path = __DIR__ . "/../uploads/" . $img;
      if (file_exists($path)) @unlink($path);
    }
  }

  $stmt = $conn->prepare("DELETE FROM products WHERE id=?");
  $stmt->bind_param("i", $id);
  $stmt->execute();

  header("Location: products.php?msg=deleted");
  exit;
}

/* -----------------------------
   ADD PRODUCT
------------------------------ */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "add") {
  $name = trim($_POST["name"] ?? "");
  $category = trim($_POST["category"] ?? "");
  $price = (float)($_POST["price"] ?? 0);
  $discount = (float)($_POST["discount"] ?? 0);
  $stock = (int)($_POST["stock"] ?? 0);
  $description = trim($_POST["description"] ?? "");
  $sizes = get_sizes_csv_from_post();

  if ($name === "" || $category === "" || $price <= 0 || $stock < 0 || $sizes === "") {
    $msg = "<div class='alert error'>Please fill name, category, price, stock and select sizes.</div>";
  } else {
    $imageName = "no-image.png";

    // upload image (optional)
    if (!empty($_FILES["image"]["name"])) {
      $ext = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
      $allowed = ["jpg", "jpeg", "png", "webp"];

      if (!in_array($ext, $allowed)) {
        $msg = "<div class='alert error'>Only JPG, PNG, WEBP images allowed.</div>";
      } else {
        if (!is_dir(__DIR__ . "/../uploads")) mkdir(__DIR__ . "/../uploads", 0777, true);

        $clean = safe_filename($_FILES["image"]["name"]);
        $imageName = time() . "_" . $clean;

        move_uploaded_file($_FILES["image"]["tmp_name"], __DIR__ . "/../uploads/" . $imageName);
      }
    }

    if ($msg === "") {
      $stmt = $conn->prepare("INSERT INTO products(name, category, price, discount, stock, sizes, description, image)
                              VALUES(?,?,?,?,?,?,?,?)");
      $stmt->bind_param("ssddisss", $name, $category, $price, $discount, $stock, $sizes, $description, $imageName);

      if ($stmt->execute()) {
        header("Location: products.php?msg=added");
        exit;
      } else {
        $msg = "<div class='alert error'>DB Error: " . htmlspecialchars($conn->error) . "</div>";
      }
    }
  }
}

/* -----------------------------
   EDIT PRODUCT
------------------------------ */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "edit") {
  $id = (int)($_POST["id"] ?? 0);
  $name = trim($_POST["name"] ?? "");
  $category = trim($_POST["category"] ?? "");
  $price = (float)($_POST["price"] ?? 0);
  $discount = (float)($_POST["discount"] ?? 0);
  $stock = (int)($_POST["stock"] ?? 0);
  $description = trim($_POST["description"] ?? "");
  $sizes = get_sizes_csv_from_post();

  if ($id <= 0 || $name === "" || $category === "" || $price <= 0 || $stock < 0 || $sizes === "") {
    $msg = "<div class='alert error'>Please fill all fields and select sizes.</div>";
  } else {
    // old image
    $oldImg = "no-image.png";
    $stmt = $conn->prepare("SELECT image FROM products WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) $oldImg = $row["image"] ?? "no-image.png";

    $imageName = $oldImg;

    // replace image if new uploaded
    if (!empty($_FILES["image"]["name"])) {
      $ext = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
      $allowed = ["jpg", "jpeg", "png", "webp"];

      if (!in_array($ext, $allowed)) {
        $msg = "<div class='alert error'>Only JPG, PNG, WEBP images allowed.</div>";
      } else {
        if (!is_dir(__DIR__ . "/../uploads")) mkdir(__DIR__ . "/../uploads", 0777, true);

        $clean = safe_filename($_FILES["image"]["name"]);
        $imageName = time() . "_" . $clean;

        move_uploaded_file($_FILES["image"]["tmp_name"], __DIR__ . "/../uploads/" . $imageName);

        // delete old file
        if ($oldImg && $oldImg !== "no-image.png") {
          $path = __DIR__ . "/../uploads/" . $oldImg;
          if (file_exists($path)) @unlink($path);
        }
      }
    }

    if ($msg === "") {
      $stmt = $conn->prepare("UPDATE products
                              SET name=?, category=?, price=?, discount=?, stock=?, sizes=?, description=?, image=?
                              WHERE id=?");
      $stmt->bind_param("ssddisssi", $name, $category, $price, $discount, $stock, $sizes, $description, $imageName, $id);

      if ($stmt->execute()) {
        header("Location: products.php?msg=updated");
        exit;
      } else {
        $msg = "<div class='alert error'>DB Error: " . htmlspecialchars($conn->error) . "</div>";
      }
    }
  }
}

/* -----------------------------
   Messages
------------------------------ */
if (isset($_GET["msg"])) {
  if ($_GET["msg"] === "added")   $msg = "<div class='alert ok'>Product added ✅</div>";
  if ($_GET["msg"] === "updated") $msg = "<div class='alert ok'>Product updated ✅</div>";
  if ($_GET["msg"] === "deleted") $msg = "<div class='alert ok'>Product deleted ✅</div>";
}

/* -----------------------------
   Fetch Products
------------------------------ */
$products = $conn->query("SELECT * FROM products ORDER BY id DESC");
?>

<!-- Font Awesome (safe to include here even if already in header) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
  .wrap{max-width:1100px;margin:auto;padding:16px;}
  .topbar{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:12px;}
  .title h2{margin:0;}
  .muted{color:#6b7280;font-size:13px;margin-top:4px;}
  .btn{
    display:inline-flex;align-items:center;gap:8px;
    padding:10px 12px;border-radius:12px;
    border:1px solid #e5e7eb;background:#111827;color:#fff;
    cursor:pointer;text-decoration:none;
  }
  .btn:hover{opacity:.92}
  .alert{padding:10px 12px;border-radius:12px;border:1px solid #e5e7eb;margin:12px 0;}
  .alert.ok{background:#ecfdf5;border-color:#bbf7d0;color:#065f46;}
  .alert.error{background:#fff1f2;border-color:#fecdd3;color:#9f1239;}

  .tableWrap{background:#fff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;}
  table{width:100%;border-collapse:collapse;}
  th,td{padding:12px;border-bottom:1px solid #f1f5f9;text-align:left;font-size:14px;vertical-align:middle;}
  th{background:#f8fafc;color:#334155;}
  tr:hover td{background:#fafafa;}

  .thumb{width:52px;height:52px;border-radius:10px;border:1px solid #eee;object-fit:cover;}
  .pill{display:inline-block;padding:4px 10px;border-radius:999px;border:1px solid #e5e7eb;background:#fff;font-size:12px;}
  .actions{display:flex;gap:10px;align-items:center;}
  .iconBtn{
    border:none;background:transparent;cursor:pointer;
    width:34px;height:34px;border-radius:10px;
    display:inline-flex;align-items:center;justify-content:center;
  }
  .iconBtn:hover{background:#f1f5f9;}
  .iconBtn.edit{color:#2563eb;}
  .iconBtn.delete{color:#dc2626;}

  /* modal */
  .back{position:fixed;inset:0;background:rgba(0,0,0,.55);display:none;align-items:center;justify-content:center;padding:20px;z-index:9999;}
  .modal{width:560px;max-width:100%;background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:16px;}
  .modalTop{display:flex;align-items:center;justify-content:space-between;gap:10px;}
  .modalTop h3{margin:0;}
  .closeBtn{
    border:none;background:#f1f5f9;border-radius:12px;
    padding:10px 12px;cursor:pointer;
  }

  .row{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px;}
  .input{width:100%;padding:11px;border:1px solid #e5e7eb;border-radius:12px;box-sizing:border-box;}
  textarea.input{min-height:90px;resize:vertical;}
  .saveBtn{
    width:100%;margin-top:12px;padding:11px;border:none;border-radius:12px;
    background:#22c55e;color:#0b1220;font-weight:900;cursor:pointer;
  }
  .saveBtn:hover{opacity:.92}
  .cancelBtn{
    width:100%;margin-top:10px;padding:11px;border:1px solid #e5e7eb;border-radius:12px;
    background:#fff;cursor:pointer;
  }

  .sizesBox{display:flex;flex-wrap:wrap;gap:10px;margin-top:8px;}
  .sizeOpt{
    display:inline-flex;align-items:center;gap:8px;
    border:1px solid #e5e7eb;border-radius:999px;padding:7px 10px;cursor:pointer;
    user-select:none;background:#fff;
  }
  .sizeOpt input{accent-color:#111827;}
  .previewRow{display:flex;align-items:center;gap:12px;margin-top:12px;}
  .previewImg{width:72px;height:72px;border-radius:14px;border:1px solid #eee;object-fit:cover;background:#f8fafc;}
  @media(max-width:650px){.row{grid-template-columns:1fr;}}
</style>

<div class="wrap">
  <div class="topbar">
    <div class="title">
      <h2>Manage Products</h2>
      <div class="muted">Add, edit, delete products (modal forms). Sizes are multi-select.</div>
    </div>
    <button class="btn" onclick="openAddModal()"><i class="fa-solid fa-plus"></i> Add Product</button>
  </div>

  <?= $msg ?>

  <div class="tableWrap">
    <table>
      <thead>
        <tr>
          <th style="width:70px;">Image</th>
          <th>Name</th>
          <th>Category</th>
          <th>Price</th>
          <th>Stock</th>
          <th>Sizes</th>
          <th style="width:120px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($products && $products->num_rows > 0): ?>
          <?php while($p = $products->fetch_assoc()): ?>
            <?php
              $final = (float)$p["price"] - (float)$p["discount"];
              if ($final < 0) $final = 0;
              $img = ($p["image"] ?? "no-image.png");
              $data = htmlspecialchars(json_encode([
                "id" => (int)$p["id"],
                "name" => $p["name"],
                "category" => $p["category"],
                "price" => (float)$p["price"],
                "discount" => (float)$p["discount"],
                "stock" => (int)$p["stock"],
                "sizes" => $p["sizes"] ?? "",
                "description" => $p["description"] ?? "",
                "image" => $img
              ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
            ?>
            <tr>
              <td>
                <?php if ($img && $img !== "no-image.png"): ?>
                  <img class="thumb" src="../uploads/<?= htmlspecialchars($img) ?>" alt="">
                <?php else: ?>
                  <div class="thumb" style="display:flex;align-items:center;justify-content:center;color:#94a3b8;">—</div>
                <?php endif; ?>
              </td>
              <td>
                <b><?= htmlspecialchars($p["name"]) ?></b><br>
                <span class="muted">ID: <?= (int)$p["id"] ?></span>
              </td>
              <td><span class="pill"><?= htmlspecialchars($p["category"]) ?></span></td>
              <td>
                Rs <?= number_format($final, 0) ?>
                <?php if ((float)$p["discount"] > 0): ?>
                  <div class="muted">Discount: Rs <?= number_format((float)$p["discount"], 0) ?></div>
                <?php endif; ?>
              </td>
              <td><?= (int)$p["stock"] ?></td>
              <td class="muted"><?= htmlspecialchars($p["sizes"] ?? "") ?></td>
              <td>
                <div class="actions">
                  <button class="iconBtn edit" title="Edit" data-product="<?= $data ?>" onclick="openEditFromBtn(this)">
                    <i class="fa-solid fa-pen-to-square"></i>
                  </button>
                  <a class="iconBtn delete" title="Delete"
                     href="?delete=<?= (int)$p["id"] ?>"
                     onclick="return confirm('Delete this product?')"
                     style="text-decoration:none;">
                    <i class="fa-solid fa-trash"></i>
                  </a>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="7" class="muted">No products yet. Click “Add Product”.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ADD MODAL -->
<div class="back" id="addBack">
  <div class="modal">
    <div class="modalTop">
      <h3><i class="fa-solid fa-plus"></i> Add Product</h3>
      <button class="closeBtn" onclick="closeAddModal()"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <form method="POST" enctype="multipart/form-data" style="margin-top:12px;">
      <input type="hidden" name="action" value="add">

      <div class="row">
        <div>
          <label class="muted">Name</label>
          <input class="input" name="name" required>
        </div>
        <div>
          <label class="muted">Category</label>
          <select class="input" name="category" id="categoryAdd" onchange="renderSizesAdd()" required>
            <?php foreach($categories as $cat): ?>
              <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="row">
        <div>
          <label class="muted">Price</label>
          <input class="input" type="number" name="price" min="1" required>
        </div>
        <div>
          <label class="muted">Discount</label>
          <input class="input" type="number" name="discount" min="0" value="0">
        </div>
      </div>

      <div class="row">
        <div>
          <label class="muted">Stock</label>
          <input class="input" type="number" name="stock" min="0" required>
        </div>
        <div>
          <label class="muted">Sizes (select multiple)</label>
          <div class="sizesBox" id="sizesBoxAdd"></div>
        </div>
      </div>

      <div style="margin-top:12px;">
        <label class="muted">Description</label>
        <textarea class="input" name="description"></textarea>
      </div>

      <div style="margin-top:12px;">
        <label class="muted">Image</label>
        <input class="input" type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
      </div>

      <button class="saveBtn" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Product</button>
      <button class="cancelBtn" type="button" onclick="closeAddModal()">
        <i class="fa-solid fa-ban"></i> Cancel
      </button>
    </form>
  </div>
</div>

<!-- EDIT MODAL -->
<div class="back" id="editBack">
  <div class="modal">
    <div class="modalTop">
      <h3><i class="fa-solid fa-pen-to-square"></i> Edit Product</h3>
      <button class="closeBtn" onclick="closeEditModal()"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <form method="POST" enctype="multipart/form-data" style="margin-top:12px;">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="e_id">

      <div class="row">
        <div>
          <label class="muted">Name</label>
          <input class="input" name="name" id="e_name" required>
        </div>
        <div>
          <label class="muted">Category</label>
          <select class="input" name="category" id="categoryEdit" onchange="renderSizesEdit()" required>
            <?php foreach($categories as $cat): ?>
              <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="row">
        <div>
          <label class="muted">Price</label>
          <input class="input" type="number" name="price" id="e_price" min="1" required>
        </div>
        <div>
          <label class="muted">Discount</label>
          <input class="input" type="number" name="discount" id="e_discount" min="0">
        </div>
      </div>

      <div class="row">
        <div>
          <label class="muted">Stock</label>
          <input class="input" type="number" name="stock" id="e_stock" min="0" required>
        </div>
        <div>
          <label class="muted">Sizes (select multiple)</label>
          <div class="sizesBox" id="sizesBoxEdit"></div>
        </div>
      </div>

      <div style="margin-top:12px;">
        <label class="muted">Description</label>
        <textarea class="input" name="description" id="e_description"></textarea>
      </div>

      <div class="previewRow">
        <img id="e_preview" class="previewImg" alt="">
        <div style="flex:1;">
          <label class="muted">Replace Image (optional)</label>
          <input class="input" type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
        </div>
      </div>

      <button class="saveBtn" type="submit"><i class="fa-solid fa-floppy-disk"></i> Update Product</button>
      <button class="cancelBtn" type="button" onclick="closeEditModal()">
        <i class="fa-solid fa-ban"></i> Cancel
      </button>
    </form>
  </div>
</div>

<script>
  // Presets (you requested)
  const SIZE_PRESETS = {
    "Shoes":  ["39","40","41","42","43"],
    "Jerseys":["S","M","XL","XXL"],
    "Socks":   ["Long","Short"],
    "Accessories": ["small", "big"] // example, replace with real sizes or leave empty
  };

  const addBack  = document.getElementById("addBack");
  const editBack = document.getElementById("editBack");

  function presetFor(category){
    return SIZE_PRESETS[category] || [];
  }

  function makeSizePills(containerId, sizes, checkedList){
    const box = document.getElementById(containerId);
    box.innerHTML = "";

    if (sizes.length === 0) {
      box.innerHTML = "<span class='muted'>No preset sizes for this category.</span>";
      return;
    }

    sizes.forEach(sz => {
      const isChecked = checkedList.includes(sz);
      const label = document.createElement("label");
      label.className = "sizeOpt";
      label.innerHTML = `
        <input type="checkbox" name="sizes_arr[]" value="${sz}" ${isChecked ? "checked" : ""}>
        <span>${sz}</span>
      `;
      box.appendChild(label);
    });
  }

  // ADD modal sizes
  function renderSizesAdd(){
    const cat = document.getElementById("categoryAdd").value;
    makeSizePills("sizesBoxAdd", presetFor(cat), []);
  }

  // EDIT modal sizes
  function renderSizesEdit(selectedCsv){
    const cat = document.getElementById("categoryEdit").value;

    // read existing checked sizes (if not passed)
    let selected = [];
    if (typeof selectedCsv === "string") {
      selected = selectedCsv.split(",").map(s => s.trim()).filter(Boolean);
    } else {
      // keep current selection if changing category
      selected = Array.from(document.querySelectorAll("#sizesBoxEdit input[type=checkbox]:checked")).map(x => x.value);
    }

    makeSizePills("sizesBoxEdit", presetFor(cat), selected);
  }

  function openAddModal(){
    addBack.style.display = "flex";
    renderSizesAdd();
  }
  function closeAddModal(){
    addBack.style.display = "none";
  }

  function openEditFromBtn(btn){
    const data = btn.getAttribute("data-product");
    const p = JSON.parse(data);

    document.getElementById("e_id").value = p.id;
    document.getElementById("e_name").value = p.name;
    document.getElementById("categoryEdit").value = p.category;
    document.getElementById("e_price").value = p.price;
    document.getElementById("e_discount").value = p.discount;
    document.getElementById("e_stock").value = p.stock;
    document.getElementById("e_description").value = p.description || "";

    // preview
    const prev = document.getElementById("e_preview");
    if (p.image && p.image !== "no-image.png") {
      prev.src = "../uploads/" + p.image;
    } else {
      prev.src = "";
    }

    // sizes (use product sizes CSV)
    renderSizesEdit(p.sizes || "");

    editBack.style.display = "flex";
  }

  function closeEditModal(){
    editBack.style.display = "none";
  }

  // Close when clicking outside
  addBack.addEventListener("click", (e) => { if (e.target === addBack) closeAddModal(); });
  editBack.addEventListener("click", (e) => { if (e.target === editBack) closeEditModal(); });
</script>

<?php include __DIR__ . "/../includes/footer.php"; ?>