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
if (isset($_GET['approve'])) {
    $emp_id = $_GET['approve'];
    $conn->query("UPDATE Employees SET status = 'active' WHERE employee_id = $emp_id");
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
           <h5>Pending Employee Approvals</h5>

    <table class="table table-bordered table-striped mt-3">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Department</th>
                <th>Position</th>
                <th>Hire Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $result = $conn->query("SELECT e.*, d.name AS dept_name FROM Employees e 
                                JOIN Departments d ON e.department_id = d.department_id 
                                WHERE e.status = 'inactive'");

        if ($result->num_rows > 0) {
            while ($emp = $result->fetch_assoc()) {
                echo "<tr>
                        <td>{$emp['name']}</td>
                        <td>{$emp['email']}</td>
                        <td>{$emp['dept_name']}</td>
                        <td>{$emp['position']}</td>
                        <td>{$emp['hire_date']}</td>
                        <td>
                            <a href='?approve={$emp['employee_id']}' class='btn btn-success btn-sm'>Approve</a>
                            <a href='?reject={$emp['employee_id']}' class='btn btn-danger btn-sm' onclick=\"return confirm('Reject and delete this employee?');\">Reject</a>
                        </td>
                    </tr>";
            }
        } else {
            echo "<tr><td colspan='6' class='text-center'>No pending approvals</td></tr>";
        }
        ?>
        </tbody>
    </table>
    </div>
    </main>
 
    <footer class="text-center mt-auto py-3 text-muted small bottom-0">
  &copy; <?= date("Y") ?> Employee Leave Portal
</footer>
</body>
</html>
