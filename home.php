<?php
// Database Connection
$conn = mysqli_connect("localhost", "root", "", "sdsc_db");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Kunin ang data para sa Real-time Status
$query = "SELECT item_name, category, status FROM equipment";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - SDSC Borrowing System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --sdsc-green: #1b5e20; --sdsc-light: #e8f5e9; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; }
        .hero { background: linear-gradient(rgba(27,94,32,0.85), rgba(27,94,32,0.85)), url('SDSC.SCHOOL.jpg'); 
                background-size: cover; background-position: center; color: white; padding: 60px 0; text-align: center; }
        .section-title { color: var(--sdsc-green); border-left: 5px solid var(--sdsc-green); padding-left: 15px; margin-bottom: 25px; font-weight: bold; }
        
        .status-available { color: #2e7d32; font-weight: bold; }
        .status-borrowed { color: #c0392b; font-weight: bold; }
        
        /* Interactive Cards */
        .login-card { border: none; transition: 0.3s; border-radius: 15px; cursor: pointer; background: white; text-decoration: none !important; }
        .login-card:hover { transform: translateY(-5px); box-shadow: 0 12px 20px rgba(0,0,0,0.1) !important; }
        
        .icon-box { width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; border-radius: 12px; }
    </style>
</head>
<body>

<div class="hero">
    <div class="container">
        <h1 class="display-4 fw-bold">St. Dominic Savio College</h1>
        <p class="lead">Digital Borrowing and Return Management System</p>
        <hr class="my-4" style="border-color: rgba(255,255,255,0.3); width: 100px; margin: auto;">
    </div>
</div>

<div class="container my-5">
    <div class="row g-4">
        
        <div class="col-lg-8">
            <h3 class="section-title">Resource Availability</h3>
            <div class="card shadow-sm border-0" style="border-radius: 15px; overflow: hidden;">
                <div class="table-responsive">
                    <table class="table table-hover m-0">
                        <thead class="table-dark" style="background-color: var(--sdsc-green);">
                            <tr>
                                <th>Item / Room</th>
                                <th>Category</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($result) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td class="py-3">
                                        <i class="fa-solid <?php echo ($row['category'] == 'Key') ? 'fa-key' : 'fa-toolbox'; ?> me-2 text-muted"></i>
                                        <?php echo htmlspecialchars($row['item_name']); ?>
                                    </td>
                                    <td class="py-3"><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($row['category']); ?></span></td>
                                    <td class="py-3">
                                        <span class="<?php echo ($row['status'] == 'Available') ? 'status-available' : 'status-borrowed'; ?>">
                                            ● <?php echo htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center py-4">No equipment found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <h3 class="section-title">Access Portals</h3>
            
            <a href="index.php?type=user" class="login-card card p-3 mb-3 border-start border-success border-5 shadow-sm">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-success text-white shadow-sm">
                        <i class="fa-solid fa-users fa-xl"></i>
                    </div>
                    <div class="ms-3 flex-grow-1">
                        <h5 class="mb-0 text-dark fw-bold">User Portal</h5>
                        <small class="text-muted">Students & Faculty</small>
                    </div>
                    <i class="fa-solid fa-chevron-right text-muted"></i>
                </div>
            </a>

            <a href="index.php?type=admin" class="login-card card p-3 border-start border-primary border-5 shadow-sm">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-primary text-white shadow-sm">
                        <i class="fa-solid fa-user-shield fa-xl"></i>
                    </div>
                    <div class="ms-3 flex-grow-1">
                        <h5 class="mb-0 text-dark fw-bold">Admin Portal</h5>
                        <small class="text-muted">System Administrator</small>
                    </div>
                    <i class="fa-solid fa-chevron-right text-muted"></i>
                </div>
            </a>

            <div class="alert alert-info mt-4 border-0 shadow-sm" style="border-radius: 12px;">
                <small><i class="fa-solid fa-info-circle me-1"></i> <strong>Note:</strong> Borrowers must present their ID upon claiming the equipment/key.</small>
            </div>
        </div>

    </div>
</div>

<footer class="text-center py-4 text-muted border-top bg-white mt-5">
    &copy; 2026 St. Dominic Savio College - Digital Management System
</footer>

</body>
</html>