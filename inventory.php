<?php
session_start();

// 1. DATABASE CONNECTION
$conn = mysqli_connect("localhost", "root", "", "sdsc_db");
if (!$conn) { die("Connection failed: " . mysqli_connect_error()); }

// 2. SECURITY CHECK
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { 
    header('Location: index.php'); exit; 
}

// 3. LOGIC FOR ADDING AN ITEM
if (isset($_POST['add_item'])) {
    $name = mysqli_real_escape_string($conn, $_POST['item_name']);
    $cat = mysqli_real_escape_string($conn, $_POST['category']);
    $qty = (int)$_POST['quantity'];
    $status = 'Available'; 
    $insert_query = "INSERT INTO equipment (item_name, category, quantity, status) VALUES ('$name', '$cat', '$qty', '$status')";
    mysqli_query($conn, $insert_query);
    header("Location: inventory.php");
}

// 4. LOGIC FOR EDITING AN ITEM (NEW)
if (isset($_POST['edit_item'])) {
    $id = (int)$_POST['item_id'];
    $name = mysqli_real_escape_string($conn, $_POST['item_name']);
    $cat = mysqli_real_escape_string($conn, $_POST['category']);
    $qty = (int)$_POST['quantity'];
    
    $update_query = "UPDATE equipment SET item_name='$name', category='$cat', quantity='$qty' WHERE id=$id";
    mysqli_query($conn, $update_query);
    header("Location: inventory.php");
}

// 5. LOGIC FOR DELETING AN ITEM
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    mysqli_query($conn, "DELETE FROM equipment WHERE id = $id");
    header("Location: inventory.php");
}

$user_role = $_SESSION['role'] ?? 'user';
$back_link = 'dashboard.php';

// 6. QUERIES
$keys_result = mysqli_query($conn, "SELECT * FROM equipment WHERE category = 'Key' ORDER BY id DESC");
$tools_result = mysqli_query($conn, "SELECT * FROM equipment WHERE category = 'Tool' ORDER BY id DESC");
$others_result = mysqli_query($conn, "SELECT * FROM equipment WHERE category NOT IN ('Key', 'Tool') ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SDSC - Inventory System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary-green: #1b5e20; --dark-green: #0d3b11; --accent-yellow: #ffc107; --danger-red: #dc3545; --info-blue: #0dcaf0; }
        
        body { 
            font-family: 'Segoe UI', sans-serif; 
            margin: 0; padding: 20px;
            background-image: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('SDSC.SCHOOL.jpg');
            background-size: cover; background-repeat: no-repeat; background-position: center center; background-attachment: fixed;
            color: #333;
        }

        .header-top {
            background: var(--primary-green); color: white; padding: 20px; text-align: center;
            margin: -20px -20px 30px -20px; border-bottom: 5px solid var(--accent-yellow); box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }

        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; max-width: 1200px; margin-left: auto; margin-right: auto; }
        .btn { padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: bold; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.3s; box-shadow: 0 4px 15px rgba(0,0,0,0.3); }
        .btn-back { background: white; color: var(--primary-green); }
        .btn-add { background: var(--accent-yellow); color: #000; }
        .btn:hover { transform: scale(1.05); }

        .inventory-grid { display: flex; gap: 20px; flex-wrap: wrap; justify-content: center; max-width: 1300px; margin: 0 auto; }
        .column { flex: 1; min-width: 350px; background: rgba(255, 255, 255, 0.95); border-radius: 15px; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.4); }
        .column-header { background: var(--primary-green); color: white; padding: 18px; text-align: center; font-weight: bold; letter-spacing: 1px; border-bottom: 3px solid var(--accent-yellow); }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f1f1f1; color: #444; font-size: 0.8em; text-transform: uppercase; }

        .status-pill { padding: 4px 10px; border-radius: 6px; font-size: 0.75em; font-weight: bold; text-transform: uppercase; }
        .status-available { background: #d1e7dd; color: #198754; }
        .status-borrowed { background: #f8d7da; color: #dc3545; }

        .action-links { display: flex; gap: 15px; }
        .delete-link { color: var(--danger-red); font-size: 1.1em; transition: 0.2s; cursor: pointer; }
        .edit-link { color: #0d6efd; font-size: 1.1em; transition: 0.2s; cursor: pointer; border: none; background: none; padding: 0; }
        .delete-link:hover, .edit-link:hover { transform: scale(1.3); }

        .modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); }
        .modal-content { background: white; width: 380px; margin: 10% auto; padding: 30px; border-radius: 15px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; box-sizing: border-box; }
    </style>
</head>
<body>

    <div class="header-top">
        <h1 style="margin:0;">ST. DOMINIC SAVIO COLLEGE</h1>
        <small style="letter-spacing: 2px;">INVENTORY MANAGEMENT SYSTEM</small>
    </div>

    <div class="top-bar">
        <a href="dashboard.php" class="btn btn-back"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
        <button class="btn btn-add" onclick="openAddModal()"><i class="fa-solid fa-plus"></i> Add New Item</button>
    </div>

    <div id="itemModal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle" style="color:var(--primary-green); margin-top:0;">New Equipment</h2>
            <form method="POST" id="itemForm">
                <input type="hidden" name="item_id" id="edit_item_id">
                <div class="form-group"><label>Item Name</label><input type="text" name="item_name" id="edit_item_name" required></div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category" id="edit_category">
                        <option value="Key">Key</option>
                        <option value="Tool">Tool</option>
                        <option value="Others">Others</option>
                    </select>
                </div>
                <div class="form-group"><label>Quantity</label><input type="number" name="quantity" id="edit_quantity" value="1" min="0" required></div>
                
                <button type="submit" name="add_item" id="submitBtn" class="btn btn-add" style="width: 100%; justify-content: center;">Save Item</button>
                <button type="button" onclick="closeModal()" style="width: 100%; margin-top: 15px; border: none; background: none; cursor: pointer; color: #888;">Cancel</button>
            </form>
        </div>
    </div>

    <div class="inventory-grid">
        <?php 
        $sections = [
            ['title' => 'KEYS', 'icon' => 'fa-key', 'data' => $keys_result],
            ['title' => 'TOOLS', 'icon' => 'fa-screwdriver-wrench', 'data' => $tools_result],
            ['title' => 'OTHERS', 'icon' => 'fa-box-archive', 'data' => $others_result]
        ];

        foreach ($sections as $sec): ?>
        <div class="column">
            <div class="column-header"><i class="fa-solid <?php echo $sec['icon']; ?>"></i> <?php echo $sec['title']; ?></div>
            <table>
                <thead><tr><th>Name</th><th>Qty</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($sec['data'])): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                        <td><strong><?php echo $row['quantity']; ?></strong></td>
                        <td><span class="status-pill <?php echo (strtolower($row['status'])=='available')?'status-available':'status-borrowed'; ?>"><?php echo $row['status']; ?></span></td>
                        <td>
                            <div class="action-links">
                                <button class="edit-link" onclick='openEditModal(<?php echo json_encode($row); ?>)'>
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <a href="inventory.php?delete_id=<?php echo $row['id']; ?>" class="delete-link" onclick="return confirm('Are you sure you want to delete this?')">
                                    <i class="fa-solid fa-trash-can"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endforeach; ?>
    </div>

    <script>
        const modal = document.getElementById('itemModal');
        const form = document.getElementById('itemForm');
        const modalTitle = document.getElementById('modalTitle');
        const submitBtn = document.getElementById('submitBtn');

        function openAddModal() {
            modalTitle.innerText = "New Equipment";
            submitBtn.name = "add_item";
            submitBtn.innerText = "Save Item";
            form.reset();
            modal.style.display = 'block';
        }

        function openEditModal(item) {
            modalTitle.innerText = "Edit Equipment";
            submitBtn.name = "edit_item";
            submitBtn.innerText = "Update Item";
            
            document.getElementById('edit_item_id').value = item.id;
            document.getElementById('edit_item_name').value = item.item_name;
            document.getElementById('edit_category').value = item.category;
            document.getElementById('edit_quantity').value = item.quantity;
            
            modal.style.display = 'block';
        }

        function closeModal() { modal.style.display = 'none'; }
        window.onclick = function(event) { if (event.target == modal) { closeModal(); } }
    </script>
</body>
</html>