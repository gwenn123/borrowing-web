<?php
session_start();

// 1. Connection settings
$conn = mysqli_connect("localhost", "root", "", "sdsc_db");

// SECURITY CHECK: Dapat logged in at HINDI admin (Student/Faculty only)
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] === 'admin') {
    header('Location: index.php');
    exit;
}

$user_email = $_SESSION['user_email'];
$user_name = $_SESSION['user_name'] ?? 'User';

// --- LOGIC PARA SA PAG-SUBMIT NG BORROW REQUEST (STEP 3: UPDATED) ---
if (isset($_POST['request_borrow'])) {
    $item_id = intval($_POST['item_id']);
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    
    // Tinanggal ang contact_no gaya ng request mo
    $purpose = mysqli_real_escape_string($conn, $_POST['purpose']);
    
    // AUTO-TIMESTAMP: Automatic na nire-record ang petsa at oras
    $date_now = date("Y-m-d H:i:s"); 
    
    // Query without contact_no
    $req_sql = "INSERT INTO requests (borrower_name, student_id, purpose, item_id, status, request_date) 
                VALUES ('$user_name', '$student_id', '$purpose', '$item_id', 'Pending', '$date_now')";
    
    if (mysqli_query($conn, $req_sql)) {
        echo "<script>alert('Request submitted! Wait for Admin approval.'); window.location.href='dashboard_user.php';</script>";
    }
}

// Kunin ang listahan ng items na available
$items_query = mysqli_query($conn, "SELECT * FROM equipment WHERE quantity > 0 AND status = 'Available'");

// Kunin ang transaction history ng logged-in user
$history_query = mysqli_query($conn, "SELECT r.*, e.item_name FROM requests r 
                                     JOIN equipment e ON r.item_id = e.id 
                                     WHERE r.borrower_name = '$user_name' ORDER BY r.id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - SDSC System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .sidebar { height: 100vh; background: #1b5e20; color: white; padding: 20px; position: fixed; width: 250px; }
        .main-content { margin-left: 260px; padding: 30px; }
        .sidebar a { color: rgba(255,255,255,0.8); text-decoration: none; display: block; padding: 12px; border-radius: 8px; margin-bottom: 5px; }
        .sidebar a:hover, .active { background: #2e7d32; color: white; }
        .card { border: none; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .status-badge { font-size: 0.75rem; padding: 5px 10px; border-radius: 20px; }
    </style>
</head>
<body>

<div class="sidebar">
    <h4 class="fw-bold mb-4">SDSC PORTAL</h4>
    <p class="small text-uppercase opacity-50">Main Menu</p>
    <a href="#" class="active"><i class="fa-solid fa-house me-2"></i> Dashboard</a>
    <a href="#"><i class="fa-solid fa-clock-rotate-left me-2"></i> My History</a>
    <a href="#"><i class="fa-solid fa-user me-2"></i> Profile</a>
    <hr>
    <a href="logout.php" class="text-warning"><i class="fa-solid fa-right-from-bracket me-2"></i> Logout</a>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold">Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h2>
            <p class="text-muted">What would you like to borrow today?</p>
        </div>
        <div class="text-end">
            <span class="badge bg-success py-2 px-3">Role: <?php echo ucfirst($_SESSION['role']); ?></span>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card p-4">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-file-pen me-2 text-success"></i> Borrowing Request Form</h5>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Student/Faculty ID</label>
                        <input type="text" name="student_id" class="form-control" placeholder="e.g. 2026-0001" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Purpose / Room No.</label>
                        <input type="text" name="purpose" class="form-control" placeholder="e.g. Room 302 - IT Class" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Select Equipment/Key</label>
                        <select name="item_id" class="form-select" required>
                            <option value="">-- Choose available item --</option>
                            <?php while($item = mysqli_fetch_assoc($items_query)): ?>
                                <option value="<?php echo $item['id']; ?>">
                                    <?php echo htmlspecialchars($item['item_name']); ?> (Qty: <?php echo $item['quantity']; ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <button type="submit" name="request_borrow" class="btn btn-success w-100 py-2 fw-bold">
                        SUBMIT REQUEST <i class="fa-solid fa-paper-plane ms-2"></i>
                    </button>
                </form>
                <div class="mt-3 small text-muted bg-light p-2 rounded">
                    <i class="fa-solid fa-circle-info me-1"></i> Note: Auto-timestamp will be recorded upon submission.
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card p-4">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-list-check me-2 text-success"></i> My Recent Transactions</h5>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Item Name</th>
                                <th>Purpose</th>
                                <th>Date Requested</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($history_query) > 0): ?>
                                <?php while($hist = mysqli_fetch_assoc($history_query)): ?>
                                <tr>
                                    <td class="fw-bold"><?php echo htmlspecialchars($hist['item_name']); ?></td>
                                    <td class="small"><?php echo htmlspecialchars($hist['purpose'] ?? 'N/A'); ?></td>
                                    <td class="small text-muted">
                                        <?php echo isset($hist['request_date']) ? date('M d, g:i A', strtotime($hist['request_date'])) : 'Just now'; ?>
                                    </td>
                                    <td>
                                        <?php if($hist['status'] === 'Pending'): ?>
                                            <span class="badge bg-warning text-dark status-badge">Pending Approval</span>
                                        <?php elseif($hist['status'] === 'Approved'): ?>
                                            <span class="badge bg-success status-badge">Borrowed</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary status-badge"><?php echo $hist['status']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center text-muted py-4">No requests found yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>