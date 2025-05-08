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
    setInterval(() => {
      const resendBtn = document.getElementById('resendBtn');
      if (resendBtn && resendBtn.dataset.time < Date.now()) {
        resendBtn.disabled = false;
      }
    }, 1000);
  </script>
</head>
<body class="container mt-5">
  <div class="card mx-auto p-4" style="max-width: 400px;">
    <h3 class="text-center mb-3" id="roleLabel">Employee Login</h3>

    <div class="btn-group w-100 mb-3" role="group">
      <button type="button" class="btn btn-outline-primary" onclick="switchRole('employee')">Employee</button>
      <button type="button" class="btn btn-outline-danger" onclick="switchRole('admin')">Admin</button>
    </div>

    <!-- Login Form -->
    <?php if (!isset($_SESSION['awaiting_otp'])): ?>
    <form method="post">
      <input type="hidden" name="role" id="role" value="employee">
      <input type="email" name="email" placeholder="Email" class="form-control mb-2" required>
      <input type="password" name="password" placeholder="Password" class="form-control mb-2" required>
      <button name="login" class="btn btn-primary w-100">Login</button>
    </form>
    <?php else: ?>
    <!-- OTP Verification Form -->
    <form method="post">
      <input type="text" name="otp" placeholder="Enter OTP" class="form-control mb-2" required>
      <button name="verify_otp" class="btn btn-success w-100">Verify OTP</button>
    </form>
    <form method="post">
      <button name="resend_otp" class="btn btn-link p-0 mt-2" id="resendBtn" data-time="<?= $_SESSION['otp_time'] + 60 * 1000 ?>" disabled>Resend OTP</button>
    </form>
    <?php endif; ?>

    <!-- PHP Logic -->
    <?php
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    require 'vendor/autoload.php';

    // Login handler
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

            // Admin login
            if ($role === 'admin' && $email === 'admin@gmail.com' && $password === $user['password']) {
                $_SESSION['user_id'] = $user['employee_id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = 'admin';
                header("Location: admin_dashboard.php");
                exit;
            }

            // Employee login
            if ($user['status'] === 'active' && $password === $user['password']) {
                $otp = rand(100000, 999999);
                $_SESSION['awaiting_otp'] = $user['employee_id'];
                $_SESSION['otp_time'] = time();

                // Update OTP in DB
                $stmt = $conn->prepare("UPDATE Employees SET otp = ? WHERE employee_id = ?");
                $stmt->bind_param("si", $otp, $user['employee_id']);
                $stmt->execute();

                // Send OTP using PHPMailer
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'bsd4753@gmail.com';
                    $mail->Password = 'dgpi jhxy yjbp sfyi';
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = 587;

                    $mail->setFrom('bsd4753@gmail.com', 'Leave Portal');
                    $mail->addAddress($email);
                    $mail->Subject = 'Your OTP for Login';
                    $mail->Body = "Hello {$user['name']},\n\nYour OTP is: $otp\n\nIt is valid for 1 minute.";

                    $mail->send();
                    echo "<div class='alert alert-info mt-3'>OTP sent to your email. Please verify.</div>";
                } catch (Exception $e) {
                    echo "<div class='alert alert-danger mt-3'>Failed to send OTP: {$mail->ErrorInfo}</div>";
                }
            } else {
                echo "<div class='alert alert-danger mt-3'>Invalid credentials or account inactive.</div>";
            }
        } else {
            echo "<div class='alert alert-danger mt-3'>User not found.</div>";
        }
    }

    // OTP verification
    if (isset($_POST['verify_otp']) && isset($_SESSION['awaiting_otp'])) {
        $enteredOtp = $_POST['otp'];
        $empId = $_SESSION['awaiting_otp'];

        $stmt = $conn->prepare("SELECT otp FROM Employees WHERE employee_id = ?");
        $stmt->bind_param("i", $empId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if (time() - $_SESSION['otp_time'] > 60) {
            echo "<div class='alert alert-warning mt-3'>OTP expired. Please request a new one.</div>";
        } elseif ($enteredOtp === $row['otp']) {
            $stmt = $conn->prepare("SELECT * FROM Employees WHERE employee_id = ?");
            $stmt->bind_param("i", $empId);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            $_SESSION['user_id'] = $user['employee_id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = 'employee';
            unset($_SESSION['awaiting_otp'], $_SESSION['otp_time']);
            header("Location: user_dashboard.php");
            exit;
        } else {
            echo "<div class='alert alert-danger mt-3'>Incorrect OTP.</div>";
        }
    }

    // Resend OTP
    if (isset($_POST['resend_otp']) && isset($_SESSION['awaiting_otp'])) {
        if (time() - $_SESSION['otp_time'] < 60) {
            echo "<div class='alert alert-warning mt-3'>Please wait before requesting a new OTP.</div>";
        } else {
            $empId = $_SESSION['awaiting_otp'];
            $stmt = $conn->prepare("SELECT name, email FROM Employees WHERE employee_id = ?");
            $stmt->bind_param("i", $empId);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            $otp = rand(100000, 999999);
            $_SESSION['otp_time'] = time();

            $stmt = $conn->prepare("UPDATE Employees SET otp = ? WHERE employee_id = ?");
            $stmt->bind_param("si", $otp, $empId);
            $stmt->execute();

            // Send email again
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'bsd4753@gmail.com';
                $mail->Password = 'dgpi jhxy yjbp sfyi';
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('bsd4753@gmail.com', 'Leave Portal');
                $mail->addAddress($user['email']);
                $mail->Subject = 'Your Resent OTP for Login';
                $mail->Body = "Hello {$user['name']},\n\nYour new OTP is: $otp\n\nIt is valid for 1 minute.";

                $mail->send();
                echo "<div class='alert alert-info mt-3'>New OTP sent to your email.</div>";
            } catch (Exception $e) {
                echo "<div class='alert alert-danger mt-3'>Failed to resend OTP: {$mail->ErrorInfo}</div>";
            }
        }
    }
    ?>
  </div>
</body>
</html>
