<?php
session_start();
include 'includes/db.php';
include 'includes/header.php';
include 'includes/email.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}
$manager_id = $_SESSION['user_id'];

if (isset($_GET['action'], $_GET['id'])) {
    $action = $_GET['action'];
    $req_id = (int)$_GET['id'];

    $reqRes = $conn->query("SELECT * FROM Leave_Requests WHERE request_id = $req_id");
    if ($reqRes && $reqRes->num_rows === 1) {
        $req = $reqRes->fetch_assoc();
        if ($req['status'] === 'pending') {
            $status = $action === 'approve' ? 'approved' : 'rejected';
            $reviewed_at = date("Y-m-d H:i:s");

            $upd = $conn->query(
                "UPDATE Leave_Requests
                 SET status='$status', reviewed_at='$reviewed_at', reviewed_by=$manager_id
                 WHERE request_id=$req_id"
            );
            if (!$upd) {
                echo "<div class='alert alert-danger'>Failed to update request: " . $conn->error . "</div>";
            } elseif ($status === 'rejected') {
              $start = new DateTime($req['start_date']);
              $end = new DateTime($req['end_date']);
          
              function countWeekdays($start, $end) {
                  $count = 0;
                  $current = clone $start; 
                  while ($current <= $end) {
                      $day = $current->format('N'); 
                      if ($day < 6) $count++;
                      $current->modify('+1 day');
                  }
                  return $count;
              }
          
              $days = countWeekdays($start, $end);
          
              $stmt = $conn->prepare("
                  UPDATE Leave_Balances
                  SET used = used - ?
                  WHERE employee_id = ? AND leave_type_id = ?
              ");
              $stmt->bind_param("iii", $days, $req['employee_id'], $req['leave_type_id']);
              if (!$stmt->execute()) {
                  echo "<div class='alert alert-danger'>Failed to restore balance: " . $stmt->error . "</div>";
              }
          }
          
            $emp = $conn->query("SELECT name, email FROM Employees WHERE employee_id = {$req['employee_id']}")->fetch_assoc();
            $leaveType = $conn->query("SELECT type_name FROM Leave_Types WHERE leave_type_id = {$req['leave_type_id']}")->fetch_assoc();

            $subject = "Leave Request " . ucfirst($status);
            $body = "
                <p>Hi {$emp['name']},</p>
                <p>Your leave request from <strong>{$req['start_date']}</strong> to <strong>{$req['end_date']}</strong> for <strong>{$leaveType['type_name']}</strong> has been <strong>" . ucfirst($status) . "</strong>.</p>
                <p><strong>Reason:</strong> {$req['reason']}</p>
                <br>
                <p>Regards,<br>Admin</p>
            ";

            sendEmail($emp['email'], $subject, $body);
        }
    } else {
        echo "<div class='alert alert-warning'>Request not found or already processed.</div>";
    }
    header("Location: approve_leave.php");
    exit;
}

$sql = "
    SELECT r.*, e.name AS emp_name, l.type_name
    FROM Leave_Requests r
    JOIN Employees e ON r.employee_id = e.employee_id
    JOIN Leave_Types l ON r.leave_type_id = l.leave_type_id
    WHERE r.status = 'pending'
";
$result = $conn->query($sql);

if (!$result) {
    die("<div class='alert alert-danger'>Query failed: " . $conn->error . "</div>");
}

?>

<h5 class="mb-3">Pending Leave Requests</h5>
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

<table class="table table-bordered">
    <thead>
        <tr>
            <th>Employee</th>
            <th>Leave Type</th>
            <th>From</th>
            <th>To</th>
            <th>Reason</th>
            <th>Requested At</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
    <?php
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                <td>{$row['emp_name']}</td>
                <td>{$row['type_name']}</td>
                <td>{$row['start_date']}</td>
                <td>{$row['end_date']}</td>
                <td>{$row['reason']}</td>
                <td>{$row['requested_at']}</td>
                <td>
                    <a href='approve_leave.php?action=approve&id={$row['request_id']}' class='btn btn-success btn-sm'>Approve</a>
                    <a href='approve_leave.php?action=reject&id={$row['request_id']}' class='btn btn-danger btn-sm' onclick=\"return confirm('Reject this leave?');\">Reject</a>
                </td>
            </tr>";
        }
    } else {
        echo "<tr><td colspan='7' class='text-center'>No pending requests found.</td></tr>";
    }
    ?>
    </tbody>
</table>
<hr class="my-4">
<h4 class="mb-3">Processed Leave Requests</h4>
<div class="mb-3">
  <label for="statusFilter" class="form-label">Filter by Status:</label>
  <select id="statusFilter" class="form-select" style="width: 200px;">
    <option value="">All</option>
    <option value="approved">Approved</option>
    <option value="rejected">Rejected</option>
  </select>
</div>

<table id="myTable" class="table table-bordered table-striped">
  <thead class="table-light">
    <tr>
      <th>Employee</th>
      <th>Leave Type</th>
      <th>Start Date</th>
      <th>End Date</th>
      <th>Reason</th>
      <th>Reviewed At</th>
      <th>Status</th>
    </tr>
  </thead>
  <tbody>
    <?php
    $stmt = $conn->query("
      SELECT 
        lr.*, 
        e.name AS employee_name, 
        lt.type_name 
      FROM Leave_Requests lr
      JOIN Employees e ON lr.employee_id = e.employee_id
      JOIN Leave_Types lt ON lr.leave_type_id = lt.leave_type_id
      WHERE lr.status IN ('approved', 'rejected')
      ORDER BY lr.reviewed_at DESC
    ");

    while ($row = $stmt->fetch_assoc()):
    ?>
      <tr>
        <td><?= htmlspecialchars($row['employee_name']) ?></td>
        <td><?= htmlspecialchars($row['type_name']) ?></td>
        <td><?= $row['start_date'] ?></td>
        <td><?= $row['end_date'] ?></td>
        <td><?= htmlspecialchars($row['reason']) ?></td>
        <td><?= date('d M Y, h:i A', strtotime($row['reviewed_at'])) ?></td>
        <td>
          <span class="badge bg-<?= $row['status'] === 'approved' ? 'success' : 'danger' ?>">
            <?= ucfirst($row['status']) ?>
          </span>
        </td>
    </tr>
    <?php endwhile; ?>
  </tbody>
</table>
<script>
  $(document).ready(function () {
    const table = $('#myTable').DataTable({
      dom: 'Bfrtip',
      buttons: [
        'copy', 'csv', 'excel', 'pdf', 'print'
      ],
      pageLength: 5,
      lengthMenu: [5, 10, 20],
      order: [[2, 'asc']]
    });

    $('#statusFilter').on('change', function () {
      const selected = $(this).val().toLowerCase();
      table.column(6).search(selected).draw();  
    });
  });
</script>


<a href="admin_dashboard.php" class="btn btn-outline-secondary">← Back to Dashboard</a>
<footer class="text-center bg-light mt-auto py-3 text-muted small ">
  &copy; <?= date("Y") ?> Employee Leave Portal 
</footer>
