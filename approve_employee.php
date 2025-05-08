<?php
session_start();
include 'includes/db.php';
include 'includes/header.php';

// Redirect if not admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Approve employee
// Approve employee
if (isset($_GET['approve'])) {
    $emp_id = $_GET['approve'];

    // 1. Set employee status to active
    $update = $conn->prepare("UPDATE Employees SET status = 'active' WHERE employee_id = ?");
    $update->bind_param("i", $emp_id);

    if ($update->execute()) {
        $year = date('Y');

        // 2. Get all leave types and their max_days_per_year
        $types = $conn->query("SELECT leave_type_id, max_days_per_year FROM leave_types");

        if ($types && $types->num_rows > 0) {
            $stmt = $conn->prepare("
                INSERT INTO leave_balances (employee_id, leave_type_id, year, total_allocated, used)
                VALUES (?, ?, ?, ?, 0)
            ");

            while ($row = $types->fetch_assoc()) {
                $typeId = $row['leave_type_id'];
                $maxDays = $row['max_days_per_year'];
                $stmt->bind_param("iiii", $emp_id, $typeId, $year, $maxDays);
                $stmt->execute();
            }

            $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Employee approved and leave balances initialized.'];
        } else {
            $_SESSION['toast'] = ['type' => 'warning', 'msg' => 'Employee approved, but no leave types found to assign.'];
        }
    } else {
        $_SESSION['toast'] = ['type' => 'danger', 'msg' => 'Failed to approve employee.'];
    }

    header("Location: approve_employee.php");
    exit;
}


// Reject (delete) employee
if (isset($_GET['reject'])) {
    $emp_id = $_GET['reject'];
    $conn->query("DELETE FROM Employees WHERE employee_id = $emp_id");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Approve Users</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="d-flex flex-column min-vh-100">
<main class="flex-grow-1">
<div class="container mt-5">
           <h5>Employees</h5>

    <table class="table table-bordered table-striped mt-3">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Department</th>
                <th>Position</th>
                <th>Hire Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $result = $conn->query("SELECT e.*, d.name AS dept_name FROM Employees e 
                                JOIN Departments d ON e.department_id = d.department_id 
                                ORDER BY e.status DESC,e.hire_date ASC");

        if ($result->num_rows > 0) {
            while ($emp = $result->fetch_assoc()) {
                echo "<tr>
                        <td>{$emp['name']}</td>
                        <td>{$emp['email']}</td>
                        <td>{$emp['dept_name']}</td>
                        <td>{$emp['position']}</td>
                        <td>{$emp['hire_date']}</td>";
                        
                        if($emp['status']==='inactive'){
                            echo"
                        <td>
                            <a href='?approve={$emp['employee_id']}' class='btn btn-success btn-sm'>Approve</a>
                            <a href='?reject={$emp['employee_id']}' class='btn btn-danger btn-sm' onclick=\"return confirm('Reject and delete this employee?');\">Reject</a>
                        </td>
                    </tr>";}
                    else{
                        echo"<td>{$emp['status']}</td>";
                    }
            }
        }else {
            echo "<tr><td colspan='6' class='text-center'>No pending approvals</td></tr>";
        }
        ?>
        </tbody>
    </table>
    <a href="admin_dashboard.php" class="btn btn-outline-secondary">← Back to Dashboard</a>
    </div>
    
    </main>
 
    
</body>
</html>
