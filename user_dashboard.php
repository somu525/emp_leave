<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: index.php");
    exit;
}
include 'includes/db.php';
require 'includes/header.php';


$userId = $_SESSION['user_id'];
$name = $_SESSION['name'];

$balanceQuery = $conn->prepare("
SELECT 
    LT.type_name,
    LB.total_allocated,
    LB.used
  FROM Leave_Balances LB
  JOIN Leave_Types LT ON LB.leave_type_id = LT.leave_type_id
  WHERE LB.employee_id = ?
");
$balanceQuery->bind_param("i", $userId);
$balanceQuery->execute();
$balances = $balanceQuery->get_result();

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
$draftQuery = $conn->prepare("
  SELECT COUNT(*) AS draft_count
  FROM Leave_Requests
  WHERE employee_id = ? AND status = 'draft'
");
$draftQuery->bind_param("i", $userId);
$draftQuery->execute();
$draftResult = $draftQuery->get_result()->fetch_assoc();
$draftCount = $draftResult['draft_count'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Dashboard - Leave Portal</title>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" />
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css" />

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

<!-- Buttons extension -->
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<!-- JSZip for Excel export -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<!-- PDFMake for PDF export -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container mt-5">
  
  
  <div class="row g-3 mb-4">
  <?php while ($row = $balances->fetch_assoc()): ?>
    <div class="col-md-3">
      <a href="apply_leave.php" class="text-decoration-none">
        <div class="card text-center shadow-sm border-dark h-100">
          <div class="card-body">
            <h5 class="card-title text-dark"><?= htmlspecialchars($row['type_name']) ?></h5>
            <p class="display-4 text-success">
              <?= max(0, $row['total_allocated'] - $row['used']) ?>
            </p>
            <p class="card-text">Remaining</p>
          </div>
        </div>
      </a>
    </div>
  <?php endwhile; ?>

  <!-- Draft Tile -->
  <div class="col-md-3">
    <a href="drafts.php" class="text-decoration-none">
      <div class="card text-center shadow-sm border-dark h-100">
        <div class="card-body">
          <h5 class="card-title text-dark">Drafts</h5>
          <p class="display-4 text-warning"><?= $draftCount ?></p>
          <p class="card-text">Saved Drafts</p>
        </div>
      </div>
    </a>
  </div>
</div>


  <div class="mb-4">
    <h4 class="mb-2">Leave History</h4>
    <div class="mb-3">
  <label for="statusFilterUser" class="form-label">Filter by Status:</label>
  <select id="statusFilterUser" class="form-select" style="width: 200px;">
    <option value="">All</option>
    <option value="draft">Draft</option>
    <option value="pending">Pending</option>
    <option value="approved">Approved</option>
    <option value="rejected">Rejected</option>
  </select>
</div>
    <table id="historytable"class="table table-striped">
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
    switch ($row['status']) {
      case 'draft':     $badge = 'secondary'; break;
      case 'pending': $badge = 'warning'; break;
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

  

<script>
  $(document).ready(function () {
    const userTable = $('#historytable').DataTable({
      dom: 'Bfrtip',
      buttons: [
        'copy', 'csv', 'excel', 'pdf', 'print'
      ],
      pageLength: 5,
      lengthMenu: [5, 10, 20],
      order: [[0, 'asc']]
    });

    $('#statusFilterUser').on('change', function () {
      const selectedStatus = $(this).val().toLowerCase();
      userTable.column(3).search(selectedStatus).draw();
    });
  });
</script>

</body>
</html>
