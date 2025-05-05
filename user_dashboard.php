<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: index.php");
    exit;
}
include 'includes/db.php';

$userId = $_SESSION['user_id'];
$name = $_SESSION['name'];

// Fetch leave balances
$balanceQuery = $conn->prepare("
    SELECT LT.type_name, LB.total_allocated, LB.used
    FROM Leave_Balances LB
    JOIN Leave_Types LT ON LB.leave_type_id = LT.leave_type_id
    WHERE LB.employee_id = ?
");
$balanceQuery->bind_param("i", $userId);
$balanceQuery->execute();
$balances = $balanceQuery->get_result();

// Fetch leave history
$historyQuery = $conn->prepare("
    SELECT LR.start_date, LR.end_date, LT.type_name, LR.status, LR.reason
    FROM Leave_Requests LR
    JOIN Leave_Types LT ON LR.leave_type_id = LT.leave_type_id
    WHERE LR.employee_id = ?
    ORDER BY LR.requested_at DESC
");
$historyQuery->bind_param("i", $userId);
$historyQuery->execute();
$history = $historyQuery->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Dashboard - Leave Portal</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container mt-5">
  <h2 class="mb-4">Welcome, <?= htmlspecialchars($name) ?>!</h2>

  <!-- Leave Balances -->
  <div class="mb-4">
    <h4>Leave Balances</h4>
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>Leave Type</th>
          <th>Allocated</th>
          <th>Used</th>
          <th>Remaining</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $balances->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($row['type_name']) ?></td>
          <td><?= $row['total_allocated'] ?></td>
          <td><?= $row['used'] ?></td>
          <td><?= $row['total_allocated'] - $row['used'] ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <!-- Leave History -->
  <div class="mb-4">
    <h4>Leave History</h4>
    <table class="table table-striped">
      <thead>
        <tr>
          <th>Leave Type</th>
          <th>Start Date</th>
          <th>End Date</th>
          <th>Status</th>
          <th>Reason</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $history->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($row['type_name']) ?></td>
          <td><?= $row['start_date'] ?></td>
          <td><?= $row['end_date'] ?></td>
          <td><span class="badge bg-<?php
              echo ($row['status'] === 'approved' ? 'success' :
                    ($row['status'] === 'pending' ? 'warning' : 'danger'));
          ?>"><?= ucfirst($row['status']) ?></span></td>
          <td><?= htmlspecialchars($row['reason']) ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <!-- Apply Button -->
  <a href="apply_leave.php" class="btn btn-primary">Apply for Leave</a>
</body>
</html>
