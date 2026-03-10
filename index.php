<?php
session_start();

// 1. Database Connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "sdsc_db";

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$login_type = isset($_GET['type']) ? $_GET['type'] : 'user';
$error_msg = ""; // Dito natin ilalagay ang error message

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // 2. Security Check gamit ang Prepared Statement
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND password = ? LIMIT 1");
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        
        // I-set ang Session Data
        $_SESSION['logged_in'] = true;
        $_SESSION['user_email'] = $user_data['email'];
        $_SESSION['user_name'] = $user_data['full_name']; 
        $_SESSION['role'] = strtolower($user_data['role']); 

        // 3. SMART REDIRECTION
        if ($_SESSION['role'] === 'admin') {
            header('Location: dashboard.php'); 
        } else {
            header('Location: dashboard_user.php'); 
        }
        exit;
    } else {
        // Imbes na alert(), lilitaw ito sa loob ng login box
        $error_msg = "wrong password.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SDSC Login System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { 
            display: flex; justify-content: center; align-items: center; height: 100vh; 
            font-family: 'Segoe UI', sans-serif; margin: 0;
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('SDSC.SCHOOL.jpg');
            background-size: cover; background-position: center;
        }
        .login-box { 
            background: rgba(255, 255, 255, 0.98); padding: 40px; border-radius: 20px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.4); width: 100%; max-width: 400px;
            border-top: 8px solid <?php echo ($login_type === 'admin') ? '#0d6efd' : '#198754'; ?>;
        }
        /* Animation para sa error message */
        .shake { animation: shake 0.5s; }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
    </style>
</head>
<body>
    <div class="login-box text-center <?php echo !empty($error_msg) ? 'shake' : ''; ?>">
        <h2 class="fw-bold mb-1">SDSC LOGIN</h2>
        <p class="text-muted mb-4 small">
            Logging in as: <strong><?php echo ($login_type === 'admin') ? 'ADMINISTRATOR' : 'STUDENT / FACULTY / ADMIN'; ?></strong>
        </p>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger d-flex align-items-center small py-2 mb-3" role="alert">
                <i class="fa-solid fa-triangle-exclamation me-2"></i>
                <div><?php echo $error_msg; ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3 text-start">
                <label class="form-label small fw-bold text-uppercase">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-envelope text-muted"></i></span>
                    <input type="email" name="email" class="form-control" placeholder="name@test.com" 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
            </div>

            <div class="mb-4 text-start">
                <label class="form-label small fw-bold text-uppercase">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-lock text-muted"></i></span>
                    <input type="password" name="password" id="passwordField" class="form-control" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn <?php echo ($login_type === 'admin') ? 'btn-primary' : 'btn-success'; ?> w-100 py-2 fw-bold shadow-sm">
                SIGN IN <i class="fa-solid fa-right-to-bracket ms-2"></i>
            </button>
            
            <div class="mt-4">
                <a href="home.php" class="text-decoration-none text-muted small"><i class="fa-solid fa-arrow-left"></i> Back to Homepage</a>
            </div>
        </form>
    </div>
</body>
</html>