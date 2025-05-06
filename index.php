<?php session_start(); include 'includes/db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - Leave Portal</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <script>
    function switchRole(role) {
      document.getElementById('role').value = role;
      document.getElementById('roleLabel').innerText = role.charAt(0).toUpperCase() + role.slice(1) + " Login";
    }
  </script>
</head>
<body class="container mt-5">
  <div class="card mx-auto p-4" style="max-width: 400px;">
    <h3 class="text-center mb-3" id="roleLabel">Employee Login</h3>

    <!-- Toggle Buttons -->
    <div class="btn-group w-100 mb-3" role="group">
      <button type="button" class="btn btn-outline-primary" onclick="switchRole('employee')">Employee</button>
      <button type="button" class="btn btn-outline-danger" onclick="switchRole('admin')">Admin</button>
    </div>

    <!-- Login Form -->
    <form method="post">
      <input type="hidden" name="role" id="role" value="employee">
      <input type="email" name="email" placeholder="Email" class="form-control mb-2" required>
      <input type="password" name="password" placeholder="Password" class="form-control mb-2" required>
      <button name="login" class="btn btn-primary w-100">Login</button>
    </form>

    <!-- Register link -->
    <p class="mt-3 text-center">
      New user? <a href="register.php">Register here</a>
    </p>

    <!-- PHP Login Logic -->
    <?php
    if (isset($_POST['login'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];
        $role = $_POST['role'];
        $stmt = $conn->prepare("SELECT * FROM Employees WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Password check
            if ($password === $user['password']) { // Replace with password_verify($password, $user['password']) if you use hashed passwords
                // Admin login
                if ($role === 'admin' && $email === 'admin@gmail.com') {
                    $_SESSION['user_id'] = $user['employee_id'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['role'] = 'admin';
                    header("Location: admin_dashboard.php");
                    exit;

                // Employee login
                } else
                    if ($user['status'] === 'active') {
                        $_SESSION['user_id'] = $user['employee_id'];
                        $_SESSION['name'] = $user['name'];
                        $_SESSION['role'] = 'employee';
                        header("Location: user_dashboard.php");
                        exit;
                    } elseif(($user['status'] !== 'active')) {
                        echo "<div class='alert alert-warning mt-3'>Account pending approval by manager.</div>";
                    }
                } else {
                    echo "<div class='alert alert-danger mt-3'>Role mismatch or unauthorized access.</div>";
                }
            } else {
                echo "<div class='alert alert-danger mt-3'>Invalid password.</div>";
            }
        } else {
            echo "<div class='alert alert-danger mt-3'>User not found.</div>";
        }
    
    ?>
  </div>
  <footer class="text-center mt-5 py-3 text-muted small">
  &copy; <?= date("Y") ?> Employee Leave Portal
</footer>
</body>
</html>
