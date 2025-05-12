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

    <?php if (!isset($_SESSION['awaiting_otp'])): ?>
    <form method="post">
      <input type="hidden" name="role" id="role" value="employee">
      <input type="email" name="email" placeholder="Email" class="form-control mb-2" required>
      <input type="password" name="password" placeholder="Password" class="form-control mb-2" required>
      <a href="forgot_password.php">Forgot Password?</a>
      <button name="login" class="btn btn-primary w-100 mt-2">Login</button>
    </form>
    <p class="mt-3 text-center">
      New user? <a href="register.php">Register here</a>
    </p>
    <?php else: ?>
    <form method="post">
      <input type="text" name="otp" placeholder="Enter OTP" class="form-control mb-2" required>
      <button name="verify_otp" class="btn btn-success w-100">Verify OTP</button>
    </form>
    <form method="post">
    <button name="resend_otp" class="btn btn-link p-0 mt-2 text-secondary" id="resendBtn" data-expire="<?= $_SESSION['otp_time'] + 60 ?>" disabled>
  Resend OTP (<span id="countdown">60</span>s)
</button>
    </form>
    <?php endif; ?>

    <?php
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    require 'vendor/autoload.php';
    include 'includes/email.php';
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

            if ($password === $user['password']) {
              if ($role === 'admin'){
                  if( $email === 'admin@gmail.com') {
                    $_SESSION['user_id'] = $user['employee_id'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['role'] = 'admin';
                    $_SESSION['login_time']=time();
                    header("Location: admin_dashboard.php");
                    exit;
                  }
                  else{
                    echo "<div class='alert alert-danger mt-3'>Role mismatch or unauthorized access.</div>";
                  }
              }

              elseif($user['status'] === 'active' ) {
                if($user['email']!=='admin@gmail.com'){
                $otp = rand(100000, 999999);
                $_SESSION['awaiting_otp'] = $user['employee_id'];
                $_SESSION['otp_time'] = time();

                $stmt = $conn->prepare("UPDATE Employees SET otp = ? WHERE employee_id = ?");
                $stmt->bind_param("si", $otp, $user['employee_id']);
                $stmt->execute();

                $subject = "Your OTP for Login";
                $body = "
                    <p>Hello {$user['name']},</p>
                    <p>Your OTP is: <strong>$otp</strong></p>
                    <p>This OTP is valid for 1 minute.</p>
                ";
                if (sendEmail($email, $subject, $body)) {
                    $_SESSION['awaiting_otp'] = $user['employee_id'];
                    $_SESSION['otp_time'] = time();
                    header("Location: index.php");
                    exit;
                } else {
                    echo "<div class='alert alert-danger mt-3'>Failed to send OTP email.</div>";
                }
              }else{
                        echo "<div class='alert alert-danger mt-3'>Role mismatch</div>";
                      }
                    } else{
                        echo "<div class='alert alert-warning mt-3'>Account pending approval by manager.</div>";
                    }
                } else {
                  echo "<div class='alert alert-danger mt-3'>Invalid password.</div>";
                }
            } else {
                echo "<div class='alert alert-danger mt-3'>Invalid Credentials</div>";
            }
        } 

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
            $_SESSION['login_time']=time();

            header("Location: user_dashboard.php");
            exit;
        } else {
            echo "<div class='alert alert-danger mt-3'>Incorrect OTP.</div>";
        }
    }

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

            $subject = "Your Resent OTP for Login";
            $body = "
                <p>Hello {$user['name']},</p>
                <p>Your new OTP is: <strong>$otp</strong></p>
                <p>This OTP is valid for 1 minute.</p>
            ";
            if (sendEmail($user['email'], $subject, $body)) {
              echo "<script>window.location.href = 'index.php';</script>";
                exit;
            } else {
                echo "<div class='alert alert-danger mt-3'>Failed to resend OTP email.</div>";
            }

        }
    }
    ?>
  </div>
  <script>
document.addEventListener("DOMContentLoaded", function () {
  const resendBtn = document.getElementById("resendBtn");
  const countdownSpan = document.getElementById("countdown");

  if (resendBtn && countdownSpan) {
    const expireTime = parseInt(resendBtn.dataset.expire);
    const interval = setInterval(() => {
      const remaining = expireTime - Math.floor(Date.now() / 1000);
      if (remaining > 0) {
        countdownSpan.innerText = remaining;
      } else {
        clearInterval(interval);
        resendBtn.disabled = false;
        resendBtn.classList.remove('text-secondary');
        resendBtn.classList.add('text-primary');
        resendBtn.innerText = 'Resend OTP';
      }
    }, 1000);
  }
});
</script>

</body>
</html>
