<?php
session_start();
include 'includes/db.php';
include 'includes/header.php';

// Redirect if not admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$manager_id = $_SESSION['user_id'];

// Handle approve/reject actions (unchanged)
if (isset($_GET['action'], $_GET['id'])) {
    $action = $_GET['action'];
    $req_id = (int)$_GET['id'];

    // Fetch the specific request
    $reqRes = $conn->query("SELECT * FROM Leave_Requests WHERE request_id = $req_id");
    if ($reqRes && $reqRes->num_rows === 1) {
        $req = $reqRes->fetch_assoc();
        if ($req['status'] === 'submitted') {
            $status = $action === 'approve' ? 'approved' : 'rejected';
            $reviewed_at = date("Y-m-d H:i:s");

            $upd = $conn->query(
                "UPDATE Leave_Requests
                 SET status='$status', reviewed_at='$reviewed_at', reviewed_by=$manager_id
                 WHERE request_id=$req_id"
            );
            if (!$upd) {
                echo "<div class='alert alert-danger'>Failed to update request: " . $conn->error . "</div>";
            } elseif ($status === 'approved') {
                // update balance
                $days = (strtotime($req['end_date']) - strtotime($req['start_date'])) / (60*60*24) + 1;
                $balUpd = $conn->query(
                    "UPDATE Leave_Balances
                     SET used = used + $days
                     WHERE employee_id={$req['employee_id']} 
                       AND leave_type_id={$req['leave_type_id']}"
                );
                if (!$balUpd) {
                    echo "<div class='alert alert-danger'>Failed to update balance: " . $conn->error . "</div>";
                }
            }
        }
    } else {
        echo "<div class='alert alert-warning'>Request not found or already processed.</div>";
    }
    // redirect to refresh list
    header("Location: approve_leave.php");
    exit;
}

// 2. Check for Pending Leave Requests & 4. Debugging Output
$sql = "
    SELECT r.*, e.name AS emp_name, l.type_name
    FROM Leave_Requests r
    JOIN Employees e ON r.employee_id = e.employee_id
    JOIN Leave_Types l ON r.leave_type_id = l.leave_type_id
    WHERE r.status = 'submitted'
";
$result = $conn->query($sql);

// Debug: did the query succeed?
if (!$result) {
    die("<div class='alert alert-danger'>Query failed: " . $conn->error . "</div>");
}

// Debug: how many pending?
echo "<div class='alert alert-info'>Found <strong>{$result->num_rows}</strong> pending request(s).</div>";
?>

<h5 class="mb-3">Pending Leave Requests</h5>

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
        // 2. No pending requests
        echo "<tr><td colspan='7' class='text-center'>No pending requests found.</td></tr>";
    }
    ?>
    </tbody>
</table>


