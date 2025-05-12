<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Forgot Password</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
  <div class="card p-4 mx-auto" style="max-width: 400px;">
    <h4 class="mb-3 text-center">Forgot Password</h4>

    <?php 
    include 'includes/db.php';
    include 'includes/email.php';

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    require 'vendor/autoload.php';

    if (isset($_POST['send_fp_otp'])) {
        $email = $_POST['email'];
        $stmt = $conn->prepare("SELECT * FROM Employees WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 1) {
            $user = $res->fetch_assoc();
            $otp = rand(100000, 999999);
            $_SESSION['fp_user'] = $user['employee_id'];
            $_SESSION['fp_otp_time'] = time();
            $_SESSION['fp_email'] = $email;

            $conn->query("UPDATE Employees SET otp = '$otp' WHERE employee_id = {$user['employee_id']}");

            $subject = "Your OTP for Password Reset";
            $body = "Hello {$user['name']},\n\nYour OTP is: $otp\n\nIt is valid for 1 minute.";
            sendEmail($email, $subject, $body);

            header("Location: forgot_password.php?step=verify_otp");
            exit();
        } else {
            echo "<div class='alert alert-danger mt-3'>Email not found.</div>";
        }
    }
    if (isset($_POST['resend_fp_otp'])) {
        if (isset($_SESSION['fp_user']) && time() - $_SESSION['fp_otp_time'] >= 60) {
            $emp_id = $_SESSION['fp_user'];
            $stmt = $conn->query("SELECT name, email FROM Employees WHERE employee_id = $emp_id");
            $user = $stmt->fetch_assoc();
    
            $otp = rand(100000, 999999);
            $_SESSION['fp_otp_time'] = time();
            $conn->query("UPDATE Employees SET otp = '$otp' WHERE employee_id = $emp_id");
    
            $subject = "Your OTP for Password Reset";
            $body = "Hello {$user['name']},\n\nYour OTP is: $otp\n\nIt is valid for 1 minute.";
            sendEmail($user['email'], $subject, $body);
    
            echo "<div class='alert alert-info'>OTP resent successfully.</div>";
        } else {
            echo "<div class='alert alert-warning'>Please wait before resending OTP.</div>";
        }
    }
    if (isset($_POST['verify_fp_otp'])) {
        $entered_otp = $_POST['otp'];
        $emp_id = $_SESSION['fp_user'];
        $result = $conn->query("SELECT otp FROM Employees WHERE employee_id = $emp_id");
        $db_otp = $result->fetch_assoc()['otp'];

        if (time() - $_SESSION['fp_otp_time'] > 60) {
            echo "<div class='alert alert-warning'>OTP expired. Please resend.</div>";
        } elseif ($entered_otp === $db_otp) {
            $_SESSION['fp_email_verified'] = true;
            header("Location: forgot_password.php?step=reset_password");
            exit();
        } else {
            echo "<div class='alert alert-danger'>Incorrect OTP.</div>";
        }
    }

    if (isset($_POST['reset_password']) && $_SESSION['fp_email_verified']) {
        $new_password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if ($new_password !== $confirm_password) {
            echo "<div class='alert alert-danger'>Passwords do not match.</div>";
        } else {
            $emp_id = $_SESSION['fp_user'];

            $conn->query("UPDATE Employees SET password = '$new_password' WHERE employee_id = $emp_id");

            session_destroy();
            header("Location: index.php?msg=password_updated");
            exit();
        }
    }
    ?>

    <?php if (!isset($_GET['step']) || $_GET['step'] == 'enter_email'): ?>
      <form method="post">
        <input type="email" name="email" placeholder="Enter your email" class="form-control mb-3" required>
        <button name="send_fp_otp" class="btn btn-primary w-100">Send OTP</button>
      </form>
    <?php elseif ($_GET['step'] == 'verify_otp'): ?>
      <form method="post">
        <input type="text" name="otp" placeholder="Enter OTP" class="form-control mb-2" required>
        <button name="verify_fp_otp" class="btn btn-success w-100">Verify OTP</button>
      </form>
      <form method="post">
  <button name="resend_fp_otp" 
          class="btn btn-link p-0 mt-2 text-muted" 
          id="resendBtn" 
          type="submit"
          disabled>
    Resend OTP <span id="countdown">(60)</span>
  </button>
</form>
    <?php elseif ($_GET['step'] == 'reset_password' && $_SESSION['fp_email_verified']): ?>
      <form method="post">
        <input type="password" name="password" placeholder="New Password" class="form-control mb-2" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" class="form-control mb-3" required>
        <button name="reset_password" class="btn btn-primary w-100">Reset Password</button>
      </form>
    <?php endif; ?>

  </div>

  <script>
  let countdownSeconds = 60;
  const resendBtn = document.getElementById("resendBtn");
  const countdownSpan = document.getElementById("countdown");

  if (resendBtn && countdownSpan) {
    const interval = setInterval(() => {
      if (countdownSeconds <= 1) {
        resendBtn.disabled = false;
        resendBtn.classList.remove("text-muted");
        resendBtn.classList.add("text-primary");
        countdownSpan.style.display = "none";
        clearInterval(interval);
      } else {
        countdownSeconds--;
        countdownSpan.textContent = `(${countdownSeconds})`;
      }
    }, 1000);
  }
</script>
</body>
</html>
