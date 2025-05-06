<?php
session_start();
include 'includes/db.php';
include 'includes/header.php';

// only admin allowed
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// 1) Total employees (exclude manager)
$resTotal = $conn->query("
  SELECT COUNT(*) AS total 
  FROM Employees 
  WHERE status='active'
");
$totalEmp = $resTotal->fetch_assoc()['total'];

// 2) Pending employee approvals
$resEmpAppr = $conn->query("
  SELECT COUNT(*) AS cnt 
  FROM Employees 
  WHERE status = 'inactive'
");
$pendingEmp = $resEmpAppr->fetch_assoc()['cnt'];

// 3) Pending leave approvals
$resLeaveAppr = $conn->query("
  SELECT COUNT(*) AS cnt 
  FROM Leave_Requests 
  WHERE status = 'submitted'
");
$pendingLeave = $resLeaveAppr->fetch_assoc()['cnt'];
?>

<main class="flex-grow-1 container py-4">
  <h2 class="mb-4">Admin Dashboard</h2>

  <div class="row g-3">
    <!-- Total Employees -->
    <div class="col-md-4">
      <div class="card text-center border-dark shadow-sm">
        <div class="card-body">
          <h5 class="card-title">Total Employees</h5>
          <p class="display-4"><?= $totalEmp ?></p>
          <p class="card-text">Active</p>
        </div>
      </div>
    </div>

    <!-- Employee Approvals -->
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

    <!-- Leave Approvals -->
    <div class="col-md-4">
      <a href="approve_leave.php" class="text-decoration-none">
        <div class="card text-center shadow-sm border-success h-100">
          <div class="card-body">
            <h5 class="card-title text-success">Leave Approvals</h5>
            <p class="display-4 text-success"><?= $pendingLeave ?></p>
            <p class="card-text">Pending</p>
          </div>
        </div>
      </a>
    </div>
  </div>
</main>


