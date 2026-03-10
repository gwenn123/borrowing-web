<?php
session_start();

// 1. DATABASE CONNECTION
$conn = mysqli_connect("localhost", "root", "", "sdsc_db");

// 2. SECURITY CHECK
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

$user_name = $_SESSION['user_name'] ?? 'System Administrator';

// --- LOGIC FOR APPROVING REQUESTS ---
if (isset($_GET['approve_id']) && isset($_GET['item_id'])) {
    $req_id = intval($_GET['approve_id']);
    $item_id = intval($_GET['item_id']);

    // Record the time of approval as the borrowed date
    $update_inv = "UPDATE equipment SET 
                    quantity = quantity - 1, 
                    status = 'Borrowed', 
                    date_borrowed = NOW(), 
                    date_returned = NULL 
                   WHERE id = $item_id AND quantity > 0";
    
    if (mysqli_query($conn, $update_inv)) {
        mysqli_query($conn, "UPDATE requests SET status = 'Approved', request_date = NOW() WHERE id = $req_id");
        header("Location: dashboard.php");
        exit;
    }
}

// --- LOGIC FOR MANUAL BORROW ---
if (isset($_GET['borrow_id'])) {
    $id = intval($_GET['borrow_id']);
    mysqli_query($conn, "UPDATE equipment SET 
                            status = 'Borrowed', 
                            quantity = quantity - 1, 
                            date_borrowed = NOW(), 
                            date_returned = NULL 
                         WHERE id = $id AND quantity > 0");
    header("Location: dashboard.php");
    exit;
}

// --- LOGIC FOR RETURN (STEP 4 UPDATE: ACCOUNTABILITY) ---
if (isset($_GET['return_id'])) {
    $id = intval($_GET['return_id']);
    
    $check_req = mysqli_query($conn, "SELECT item_id FROM requests WHERE id = $id AND status = 'Approved'");
    if(mysqli_num_rows($check_req) > 0) {
        $req_data = mysqli_fetch_assoc($check_req);
        $eq_id = $req_data['item_id'];
        // Update ang requests table (Step 5: Monitoring - nilalagyan ng return date)
        mysqli_query($conn, "UPDATE requests SET status = 'Returned', return_date = NOW() WHERE id = $id");
        mysqli_query($conn, "UPDATE equipment SET status = 'Available', quantity = quantity + 1, date_returned = NOW() WHERE id = $eq_id");
    } else {
        mysqli_query($conn, "UPDATE equipment SET 
                                status = 'Available', 
                                quantity = quantity + 1, 
                                date_returned = NOW() 
                             WHERE id = $id");
    }
    header("Location: dashboard.php");
    exit;
}

// Search and Filter logic
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? 'all';
$query = "SELECT * FROM equipment WHERE 1=1 AND item_name != 'sdsc_db'"; 
if (!empty($search)) { $query .= " AND item_name LIKE '%$search%'"; }
if ($filter !== 'all') { $query .= " AND category = '$filter'"; }
$result = mysqli_query($conn, $query);

// --- QUERY FOR TRACKING ACTIVE BORROWERS ---
$active_borrow_query = mysqli_query($conn, "SELECT r.*, e.item_name FROM requests r JOIN equipment e ON r.item_id = e.id WHERE r.status = 'Approved'");

// --- STEP 5: QUERY FOR AUDIT LOGS (HISTORY) ---
$history_query = mysqli_query($conn, "SELECT r.*, e.item_name FROM requests r 
                                      JOIN equipment e ON r.item_id = e.id 
                                      WHERE r.status = 'Returned' 
                                      ORDER BY r.return_date DESC LIMIT 10");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SDSC - Dashboard & Monitoring</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { 
            --sdsc-green: #1b5e20; 
            --sdsc-dark: #0d3b11;
            --sdsc-accent: #ffc107;
        }

        body { 
            font-family: 'Segoe UI', sans-serif; 
            margin: 0; 
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('SDSC.SCHOOL.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: #333; 
        }
        
        .header { 
            background: var(--sdsc-green); 
            color: white; 
            padding: 20px; 
            text-align: center; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            border-bottom: 5px solid var(--sdsc-accent);
        }
        .header h1 { margin: 0; letter-spacing: 2px; font-size: 2.2em; }
        
        .container { display: flex; min-height: 100vh; }
        
        .sidebar { 
            width: 250px; 
            background: rgba(255, 255, 255, 0.9); 
            padding: 20px; 
            backdrop-filter: blur(10px);
        }
        .sidebar h3 { color: var(--sdsc-green); border-bottom: 2px solid var(--sdsc-green); padding-bottom: 10px; }
        .sidebar a { text-decoration: none; color: #444; display: block; padding: 12px; margin-bottom: 10px; border-radius: 8px; transition: 0.3s; font-weight: bold; }
        .sidebar a:hover, .active { background: var(--sdsc-green); color: white !important; }
        
        .main { flex: 1; padding: 30px; }
        
        .stats { display: flex; gap: 20px; margin-bottom: 30px; }
        .stat-card { 
            background: white; 
            padding: 25px; flex: 1; text-align: center; 
            border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); 
        }
        .stat-card p { font-size: 32px; font-weight: bold; color: var(--sdsc-green); margin: 5px 0; }

        .content-box {
            background: rgba(255, 255, 255, 0.98);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            margin-bottom: 30px;
        }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: var(--sdsc-green); color: white; font-size: 14px; }
        
        .live-clock { background: var(--sdsc-accent); padding: 10px 25px; border-radius: 50px; font-weight: bold; display: inline-block; margin-bottom: 20px; }

        .btn { padding: 8px 15px; text-decoration: none; border-radius: 5px; font-size: 12px; color: white; font-weight: bold; border: none; cursor: pointer; display: inline-block; }
        .btn-approve { background: #28a745; }
        .btn-return { background: #fd7e14; }
        .btn-borrow { background: var(--sdsc-green); }
        .btn-logout { background: #dc3545 !important; margin-top: 50px; text-align: center; }

        .status-borrowed { color: #dc3545; font-weight: bold; }
        .status-available { color: #28a745; font-weight: bold; }
        .date-text { font-size: 11px; color: #666; font-style: italic; }
        .active-badge { background: #dc3545; color: white; padding: 2px 8px; border-radius: 10px; font-size: 10px; }
        .history-badge { background: #6c757d; color: white; padding: 2px 8px; border-radius: 10px; font-size: 10px; }
    </style>
    <script>
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            document.getElementById('clock').innerHTML = now.toLocaleDateString('en-US', options);
        }
        setInterval(updateClock, 1000);
    </script>
</head>
<body onload="updateClock()">

    <div class="header">
        <h1>ST. DOMINIC SAVIO COLLEGE</h1>
        <div style="font-size: 14px; margin-top: 5px; opacity: 0.9;">Digital Equipment Borrowing System</div>
    </div>

    <div class="container">
        <div class="sidebar">
            <h3>NAVIGATION</h3>
            <a href="dashboard.php" class="active"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
            <a href="inventory.php"><i class="fa-solid fa-warehouse"></i> Inventory View</a>
            <a href="logout.php" class="btn-logout"><i class="fa-solid fa-power-off"></i> Sign Out</a>
        </div>

        <div class="main">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="color: white; text-shadow: 2px 2px 5px rgba(0,0,0,0.5);">Welcome, <?php echo htmlspecialchars($user_name); ?></h2>
                <div class="live-clock"><i class="fa-regular fa-clock"></i> <span id="clock">Loading...</span></div>
            </div>
            
            <div class="stats">
                <div class="stat-card"><h4>TOTAL ASSETS</h4><p><?php echo mysqli_num_rows($result); ?></p></div>
                <div class="stat-card"><h4>ACTIVE BORROWERS</h4><p style="color: #dc3545;"><?php echo mysqli_num_rows($active_borrow_query); ?></p></div>
                <div class="stat-card"><h4>SYSTEM STATUS</h4><p style="color: #28a745;">ONLINE</p></div>
            </div>

            <div class="content-box" style="border-left: 5px solid #dc3545;">
                <h3 style="color: #dc3545;"><i class="fa-solid fa-user-clock"></i> Active Borrowers Tracking</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Borrower</th><th>Item Borrowed</th><th>Status</th><th>Operation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($active_borrow_query) == 0): ?>
                            <tr><td colspan="4" style="text-align:center; color: #888; padding: 20px;">No active borrowers at the moment.</td></tr>
                        <?php else: 
                            while($active = mysqli_fetch_assoc($active_borrow_query)): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($active['borrower_name']); ?></strong><br><small><?php echo $active['student_id']; ?></small></td>
                                <td><?php echo htmlspecialchars($active['item_name']); ?></td>
                                <td><span class="active-badge">BORROWED</span></td>
                                <td><a href="dashboard.php?return_id=<?php echo $active['id']; ?>" class="btn btn-return">Confirm Return</a></td>
                            </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="content-box">
                <h3 style="color: var(--sdsc-green);"><i class="fa-solid fa-hourglass-half"></i> Pending Requests</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Borrower Name</th><th>ID Number</th><th>Item</th><th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $req_query = mysqli_query($conn, "SELECT * FROM requests WHERE status = 'Pending'");
                        if(mysqli_num_rows($req_query) == 0): ?>
                            <tr><td colspan="4" style="text-align:center; color: #888; padding: 20px;">No pending requests.</td></tr>
                        <?php else: 
                            while($req = mysqli_fetch_assoc($req_query)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($req['borrower_name']); ?></td>
                                <td><?php echo htmlspecialchars($req['student_id']); ?></td>
                                <td>ID: #<?php echo $req['item_id']; ?></td>
                                <td><a href="dashboard.php?approve_id=<?php echo $req['id']; ?>&item_id=<?php echo $req['item_id']; ?>" class="btn btn-approve">Approve</a></td>
                            </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="content-box">
                <h3 style="color: var(--sdsc-green);"><i class="fa-solid fa-list-check"></i> Inventory Quick Actions</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Asset Name</th><th>Status</th><th>Borrowed Date</th><th>Operation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php mysqli_data_seek($result, 0); 
                        while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['item_name']); ?></strong><br><small>ID: #<?php echo $row['id']; ?></small></td>
                            <td><span class="<?php echo ($row['status'] === 'Borrowed') ? 'status-borrowed' : 'status-available'; ?>"><?php echo strtoupper($row['status']); ?></span></td>
                            <td class="date-text"><?php echo ($row['date_borrowed']) ? date('M d, Y h:i A', strtotime($row['date_borrowed'])) : '---'; ?></td>
                            <td>
                                <?php if ($row['status'] === 'Borrowed'): ?>
                                    <a href="dashboard.php?return_id=<?php echo $row['id']; ?>" class="btn btn-return">Return Asset</a>
                                <?php elseif ($row['quantity'] > 0): ?>
                                    <a href="dashboard.php?borrow_id=<?php echo $row['id']; ?>" class="btn btn-borrow">Assign Item</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="content-box" style="border-top: 5px solid var(--sdsc-green);">
                <h3 style="color: var(--sdsc-green);"><i class="fa-solid fa-clock-rotate-left"></i> History Logs & Audit</h3>
                <table>
                    <thead style="background: #f4f4f4;">
                        <tr>
                            <th>Borrower</th><th>Asset</th><th>Date Borrowed</th><th>Date Returned</th><th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($history_query) == 0): ?>
                            <tr><td colspan="5" style="text-align:center; color: #888; padding: 20px;">No transaction history yet.</td></tr>
                        <?php else: 
                            while($hist = mysqli_fetch_assoc($history_query)): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($hist['borrower_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($hist['item_name']); ?></td>
                                <td class="date-text"><?php echo date('M d, g:i A', strtotime($hist['request_date'])); ?></td>
                                <td class="date-text"><?php echo date('M d, g:i A', strtotime($hist['return_date'])); ?></td>
                                <td><span class="history-badge">COMPLETED</span></td>
                            </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
                <div style="margin-top: 15px; text-align: right;">
                    <small>*Showing last 10 completed transactions.</small>
                </div>
            </div>

        </div>
    </div>
</body>
</html>