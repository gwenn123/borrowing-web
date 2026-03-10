<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "sdsc_db");

// SECURITY: Check kung logged in at kung hindi ba admin (dahil admin ay sa dashboard.php)
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] == 'admin') {
    header('Location: index.php');
    exit;
}

$role = $_SESSION['role']; // 'student' or 'faculty'
$user_name = $_SESSION['user_name'] ?? 'User';
$msg = "";

// LOGIC PARA SA PAG-SUBMIT NG REQUEST
if (isset($_POST['submit_request'])) {
    $borrower_name = mysqli_real_escape_string($conn, $_POST['borrower_name']);
    $id_number = mysqli_real_escape_string($conn, $_POST['id_number']); // Student ID o Faculty ID
    $item_id = intval($_POST['item_id']);

    $insert = "INSERT INTO requests (borrower_name, student_id, item_id, status) 
               VALUES ('$borrower_name', '$id_number', $item_id, 'Pending')";
    
    if (mysqli_query($conn, $insert)) {
        $msg = "<div class='alert alert-success shadow-sm'><strong>Success!</strong> Your request for Item #$item_id has been sent to Admin for approval.</div>";
    }
}

// Kunin ang mga available items (Keys at Equipment)
$items_query = mysqli_query($conn, "SELECT id, item_name, category FROM equipment WHERE quantity > 0 AND item_name != 'sdsc_db'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucfirst($role); ?> Portal - SDSC</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --sdsc-green: #1b5e20; }
        body { background-color: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .navbar { background: var(--sdsc-green); box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .portal-header { background: white; padding: 20px; border-radius: 0 0 20px 20px; margin-bottom: 30px; border-bottom: 4px solid var(--sdsc-green); }
        .card { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .btn-sdsc { background: var(--sdsc-green); color: white; border-radius: 10px; padding: 12px; font-weight: bold; }
        .btn-sdsc:hover { background: #0d3b10; color: white; }
        .role-badge { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; padding: 5px 12px; border-radius: 50px; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark">
    <div class="container d-flex justify-content-between">
        <span class="navbar-brand fw-bold">SDSC Digital Portal</span>
        <a href="logout.php" class="btn btn-sm btn-outline-light rounded-pill px-3">Logout <i class="fa-solid fa-sign-out-alt ms-1"></i></a>
    </div>
</nav>

<div class="container">
    <div class="portal-header shadow-sm">
        <div class="d-flex align-items-center">
            <div class="flex-shrink-0 bg-light p-3 rounded-circle border">
                <i class="fa-solid <?php echo ($role == 'faculty') ? 'fa-chalkboard-user' : 'fa-user-graduate'; ?> fa-2xl text-success"></i>
            </div>
            <div class="ms-3">
                <h4 class="mb-0 fw-bold text-dark">Welcome, <?php echo htmlspecialchars($user_name); ?>!</h4>
                <span class="badge role-badge <?php echo ($role == 'faculty') ? 'bg-success' : 'bg-info'; ?>">
                    <?php echo $role; ?> Account
                </span>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <?php echo $msg; ?>
            <div class="card p-4">
                <h5 class="fw-bold mb-4" style="color: var(--sdsc-green);">
                    <i class="fa-solid fa-file-signature me-2"></i>New Borrowing Request
                </h5>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Full Name</label>
                        <input type="text" name="borrower_name" class="form-control bg-light" value="<?php echo htmlspecialchars($user_name); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">
                            <?php echo ($role == 'faculty') ? 'Faculty ID Number' : 'Student ID Number'; ?>
                        </label>
                        <input type="text" name="id_number" class="form-control bg-light" placeholder="Enter your ID number" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Select Item / Key</label>
                        <select name="item_id" class="form-select border-2" required>
                            <option value="">-- View Available Resources --</option>
                            <?php while($item = mysqli_fetch_assoc($items_query)): ?>
                                <option value="<?php echo $item['id']; ?>">
                                    [<?php echo $item['category']; ?>] <?php echo htmlspecialchars($item['item_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="d-grid mt-4">
                        <button type="submit" name="submit_request" class="btn btn-sdsc shadow-sm">
                            Send Request <i class="fa-solid fa-paper-plane ms-2"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card p-4 h-100">
                <h5 class="fw-bold mb-4" style="color: var(--sdsc-green);">
                    <i class="fa-solid fa-clock-rotate-left me-2"></i>My Transaction History
                </h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr class="small text-muted text-uppercase">
                                <th>Item</th>
                                <th>Status</th>
                                <th>Date Requested</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Kunin ang transactions ng specific borrower na ito (pwedeng i-filter sa student_id)
                            $history = mysqli_query($conn, "SELECT r.*, e.item_name FROM requests r JOIN equipment e ON r.item_id = e.id ORDER BY r.id DESC LIMIT 6");
                            if(mysqli_num_rows($history) == 0): ?>
                                <tr><td colspan="3" class="text-center text-muted py-4">No records found.</td></tr>
                            <?php else: 
                                while($h = mysqli_fetch_assoc($history)): 
                                    $status_badge = "bg-warning";
                                    if($h['status'] == 'Approved') $status_badge = "bg-success";
                                    if($h['status'] == 'Returned') $status_badge = "bg-secondary";
                            ?>
                            <tr>
                                <td class="fw-bold text-dark"><?php echo htmlspecialchars($h['item_name']); ?></td>
                                <td><span class="badge <?php echo $status_badge; ?>"><?php echo $h['status']; ?></span></td>
                                <td class="small text-muted"><?php echo date('M d, Y h:i A', strtotime($h['request_date'])); ?></td>
                            </tr>
                            <?php endwhile; endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-auto">
                    <div class="alert alert-info py-2 small mb-0 shadow-sm border-0">
                        <i class="fa-solid fa-info-circle me-1"></i> Please visit the Admin Office for item pickup after approval.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="text-center py-4 text-muted mt-5">
    &copy; 2024 St. Dominic Savio College - Unified User Portal
</footer>

</body>
</html>