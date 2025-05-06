<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: index.php");
    exit;
}
include 'includes/db.php';
include 'includes/header.php';


$userId = $_SESSION['user_id'];
$name = $_SESSION['name'];

// Fetch leave balances
$balanceQuery = $conn->prepare("
  SELECT 
    LT.type_name,
    LB.total_allocated,
    COALESCE(SUM(DATEDIFF(LR.end_date,LR.start_date) + 1), 0) AS used
  FROM Leave_Balances LB
  JOIN Leave_Types LT 
    ON LB.leave_type_id = LT.leave_type_id
  LEFT JOIN Leave_Requests LR 
    ON LB.employee_id    = LR.employee_id
   AND LB.leave_type_id = LR.leave_type_id
   AND LR.status        = 'approved'
  WHERE LB.employee_id = ?
  GROUP BY LB.leave_type_id, LT.type_name, LB.total_allocated
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
  <?php 
    // choose badge color based on status
    switch ($row['status']) {
      case 'draft':     $badge = 'warning'; break;
      case 'submitted': $badge = 'primary'; break;
      case 'approved':  $badge = 'success'; break;
      case 'rejected':  $badge = 'danger';  break;
      default:          $badge = 'secondary';
    }
  ?>
  <tr>
    <td><?= htmlspecialchars($row['type_name']) ?></td>
    <td><?= $row['start_date'] ?></td>
    <td><?= $row['end_date'] ?></td>
    <td>
      <span class="badge bg-<?= $badge ?>">
        <?= ucfirst($row['status']) ?>
      </span>
    </td>
    <td><?= htmlspecialchars($row['reason']) ?></td>
  </tr>
<?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <footer class="text-center mt-auto py-3 text-muted small bottom-0">
  &copy; <?= date("Y") ?> Employee Leave Portal
</footer>
</body>
</html>
