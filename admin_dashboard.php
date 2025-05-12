<?php
session_start();
include 'includes/db.php';
include 'includes/header.php';
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php"); exit;
}


$resTotal = $conn->query("SELECT COUNT(*) AS total FROM Employees WHERE status='active'");
$totalEmp = $resTotal->fetch_assoc()['total'];

$resEmpAppr = $conn->query("SELECT COUNT(*) AS cnt FROM Employees WHERE status='inactive'");
$pendingEmp = $resEmpAppr->fetch_assoc()['cnt'];

$resLeaveAppr = $conn->query("SELECT COUNT(*) AS cnt FROM Leave_Requests WHERE status='pending'");
$pendingLeave = $resLeaveAppr->fetch_assoc()['cnt'];

$resDept = $conn->query("SELECT COUNT(*) AS cnt FROM Departments");
$totalDept = $resDept->fetch_assoc()['cnt'];

$resHoli = $conn->query("SELECT COUNT(*) AS cnt FROM Holidays");
$totalHoli = $resHoli->fetch_assoc()['cnt'];
?>
<main class="flex-grow-1 container py-4">
  <h2 class="mb-4">Admin Dashboard</h2>
  <div class="row g-3">
    <div class="col-md-4">
      <a href="departments.php" class="text-decoration-none">
        <div class="card text-center shadow-sm border-dark h-100">
          <div class="card-body">
            <h5 class="card-title text-dark">Departments</h5>
            <p class="display-4 text-dark"><?= $totalDept ?></p>
            <p class="card-text">Total</p>
          </div>
        </div>
      </a>
    </div>
    <div class="col-md-4">
      <a href="approve_employee.php" class="text-decoration-none">
        <div class="card text-center shadow-sm border-dark h-100">
          <div class="card-body">
            <h5 class="card-title">Total Employees</h5>
            <p class="display-4"><?= $totalEmp ?></p>
            <p class="card-text">Active</p>
          </div>
        </div>
      </a>
    </div>
    <div class="col-md-4">
      <a href="approve_employee.php" class="text-decoration-none">
        <div class="card text-center shadow-sm border-primary h-100">
          <div class="card-body">
            <h5 class="card-title text-primary">Employee Approvals</h5>
            <p class="display-4 text-primary"><?= $pendingEmp ?></p>
            <p class="card-text">Pending</p>
          </div>
        </div>
      </a>
    </div>
    <div class="col-md-4">
      <a href="approve_leave.php" class="text-decoration-none">
        <div class="card text-center shadow-sm border-primary h-100">
          <div class="card-body">
            <h5 class="card-title text-primary">Leave Approvals</h5>
            <p class="display-4 text-primary"><?= $pendingLeave ?></p>
            <p class="card-text">Pending</p>
          </div>
        </div>
      </a>
    </div>
    <div class="col-md-4">
      <a href="holidays.php" class="text-decoration-none">
        <div class="card text-center shadow-sm border-success h-100">
          <div class="card-body">
            <h5 class="card-title text-success">Holidays</h5>
            <p class="display-4 text-success"><?= $totalHoli ?></p>
            <p class="card-text">Total</p>
          </div>
        </div>
      </a>
    </div>
  </div>
</main>

