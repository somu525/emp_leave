<?php
session_start();
include 'includes/db.php';
include 'includes/header.php';
include 'includes/email.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

if (isset($_GET['approve'])) {
    $emp_id = $_GET['approve'];
    $conn->query("UPDATE Employees SET status = 'active' WHERE employee_id = $emp_id");

    $emp = $conn->query("SELECT * FROM Employees WHERE employee_id = $emp_id")->fetch_assoc();
    $year = date('Y');

    $subject = "Welcome to the Company!";
    $body = "
        <h4>Hi {$emp['name']},</h4>
        <p>You have been approved as an employee at our company.</p>
        <p><strong>Email:</strong> {$emp['email']}<br>
           <strong>Position:</strong> {$emp['position']}<br>
           <strong>Hire Date:</strong> {$emp['hire_date']}</p>
        <p>Please log in to the portal using your email and password. An OTP will be sent for login verification.</p>
        <br><p>Regards,<br>Admin</p>";
    sendEmail($emp['email'], $subject, $body);

    $types = $conn->query("SELECT leave_type_id, max_days_per_year FROM leave_types");
    if ($types->num_rows > 0) {
        $stmt = $conn->prepare("INSERT INTO leave_balances (employee_id, leave_type_id, year, total_allocated, used) VALUES (?, ?, ?, ?, 0)");
        foreach ($types as $row) {
            $stmt->bind_param("iiii", $emp_id, $row['leave_type_id'], $year, $row['max_days_per_year']);
            $stmt->execute();
        }
    }

    $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Employee approved.'];
    header("Location: approve_employee.php");
    exit;
}

if (isset($_GET['reject'])) {
    $emp_id = $_GET['reject'];
    $emp = $conn->query("SELECT * FROM Employees WHERE employee_id = $emp_id")->fetch_assoc();
    sendEmail($emp['email'], "Application Rejected", "<h4>Dear {$emp['name']},</h4><p>Your employment application has been rejected. We wish you all the best.</p>");
    $conn->query("DELETE FROM Employees WHERE employee_id = $emp_id");
    $_SESSION['toast'] = ['type' => 'info', 'msg' => 'Employee rejected and removed.'];
    header("Location: approve_employee.php");
    exit;
}

if (isset($_POST['add_employee'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $dept = $_POST['department_id'];
    $position = $_POST['position'];
    $hire_date = $_POST['hire_date'];
    $password = 'employee@123'; 
    $year = date('Y');

    $stmt = $conn->prepare("INSERT INTO Employees (name, email, department_id, position, hire_date, password, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
    $stmt->bind_param("ssisss", $name, $email, $dept, $position, $hire_date, $password);

    if ($stmt->execute()) {
        $new_emp_id = $conn->insert_id;

        $subject = "Welcome to the Company!";
        $body = "
            <h4>Hi {$name},</h4>
            <p>You have been added as an employee at our company.</p>
            <p><strong>Email:</strong> {$email}<br>
               <strong>Password:</strong> employee@123<br>
               <strong>Position:</strong> {$position}<br>
               <strong>Hire Date:</strong> {$hire_date}</p>
            <p>You can log in using these credentials. An OTP will be sent to your email for verification.</p>
            <br><p>Regards,<br>Admin</p>";
        sendEmail($email, $subject, $body);

        $types = $conn->query("SELECT leave_type_id, max_days_per_year FROM leave_types");
        if ($types->num_rows > 0) {
            $stmt2 = $conn->prepare("INSERT INTO leave_balances (employee_id, leave_type_id, year, total_allocated, used) VALUES (?, ?, ?, ?, 0)");
            foreach ($types as $row) {
                $stmt2->bind_param("iiii", $new_emp_id, $row['leave_type_id'], $year, $row['max_days_per_year']);
                $stmt2->execute();
            }
        }
    }
    header("Location: approve_employee.php");
    exit;
}

if (isset($_POST['edit_employee'])) {
    $emp_id = $_POST['emp_id'];
    $name = $_POST['edit_name'];
    $email = $_POST['edit_email'];
    $dept = $_POST['edit_department_id'];
    $position = $_POST['edit_position'];
    $hire_date = $_POST['edit_hire_date'];

    $stmt = $conn->prepare("UPDATE Employees SET name=?, email=?, department_id=?, position=?, hire_date=? WHERE employee_id=?");
    $stmt->bind_param("ssissi", $name, $email, $dept, $position, $hire_date, $emp_id);
    sendEmail($emp['email'], "Updated details", "
        <h4>Dear {$emp['name']},</h4>
<p>Your details have been updated in website.</p>
            <p><strong>Email:</strong> {$email}<br>
               <strong>Position:</strong> {$position}<br>
               <strong>Hire Date:</strong> {$hire_date}</p>
            <p>You can log in using the credentials. An OTP will be sent to your email for verification.</p>
            <br><p>Regards,<br>Admin</p>    ");

            if ($stmt->execute()) {
                $subject = "Employee Details Updated";
                $body = "<h4>Hello $name,</h4><p>Your employee details were updated by admin.</p><p><strong>Email:</strong> $email<br><strong>Position:</strong> $position<br><strong>Hire Date:</strong> $hire_date</p>";
                sendEmail($email, $subject, $body);
        
                $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Employee updated.'];
            }
            header("Location: approve_employee.php");
            exit;
}

if (isset($_POST['delete_employee'])) {
    $emp_id = $_POST['delete_id'];
    $emp = $conn->query("SELECT * FROM Employees WHERE employee_id = $emp_id")->fetch_assoc();

    sendEmail($emp['email'], "Removed from Company", "
        <h4>Dear {$emp['name']},</h4>
        <p>You have been removed from the company records. Your access to the portal has been revoked.</p>
    ");

    $conn->query("DELETE FROM leave_balances WHERE employee_id = $emp_id");
    $conn->query("DELETE FROM leave_requests WHERE employee_id = $emp_id");
    $conn->query("DELETE FROM Employees WHERE employee_id = $emp_id");

    $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Employee and related data deleted.'];
    header("Location: approve_employee.php");
    exit;
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Approve Employees</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
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
</head>
<body class="container mt-5">
    <h4>Employees</h4>
    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addModal">Add Employee</button>

    <table id="myTable" class="table table-striped">
        <thead>
            <tr><th>Name</th><th>Email</th><th>Department</th><th>Position</th><th>Hire Date</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php
            $result = $conn->query("SELECT e.*, d.name AS dept FROM Employees e JOIN Departments d ON e.department_id = d.department_id ORDER BY e.status DESC, e.hire_date ASC");
            while ($row = $result->fetch_assoc()):
            ?>
            <tr>
                <td><?= $row['name'] ?></td>
                <td><?= $row['email'] ?></td>
                <td><?= $row['dept'] ?></td>
                <td><?= $row['position'] ?></td>
                <td><?= $row['hire_date'] ?></td>
                <td><?= ucfirst($row['status']) ?></td>
                <td>
                    <?php if ($row['status'] == 'inactive'): ?>
                        <a href="?approve=<?= $row['employee_id'] ?>" class="btn btn-success btn-sm">Approve</a>
                        <a href="?reject=<?= $row['employee_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Reject and delete this employee?');">Reject</a>
                    <?php else: ?>
                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['employee_id'] ?>">Edit</button>
                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $row['employee_id'] ?>">Delete</button>
                    <?php endif; ?>
                </td>
            </tr>

            <div class="modal fade" id="editModal<?= $row['employee_id'] ?>" tabindex="-1">
                <div class="modal-dialog"><div class="modal-content">
                    <form method="POST">
                        <div class="modal-header"><h5>Edit Employee</h5></div>
                        <div class="modal-body">
                            <input type="hidden" name="emp_id" value="<?= $row['employee_id'] ?>">
                            <input name="edit_name" class="form-control mb-2" value="<?= $row['name'] ?>" style="background-color: lightgray;cursor: not-allowed;" readonly >
                            <input name="edit_email" class="form-control mb-2" value="<?= $row['email'] ?>" style="background-color: lightgray;cursor: not-allowed;" readonly>
                            <select name="edit_department_id" class="form-control mb-2">
                                <?php
                                $depts = $conn->query("SELECT * FROM Departments");
                                while ($d = $depts->fetch_assoc()) {
                                    $selected = ($d['department_id'] == $row['department_id']) ? 'selected' : '';
                                    echo "<option value='{$d['department_id']}' $selected>{$d['name']}</option>";
                                }
                                ?>
                            </select>
                            <input name="edit_position" class="form-control mb-2" value="<?= $row['position'] ?>" required>
                            <input type="date" name="edit_hire_date" class="form-control" value="<?= $row['hire_date'] ?>" required>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-success" name="edit_employee">Save Changes</button>
                        </div>
                    </form>
                </div></div>
            </div>

            <div class="modal fade" id="deleteModal<?= $row['employee_id'] ?>" tabindex="-1">
                <div class="modal-dialog"><div class="modal-content">
                    <form method="POST">
                        <div class="modal-header"><h5>Delete Employee</h5></div>
                        <div class="modal-body">
                            Are you sure you want to delete <strong><?= $row['name'] ?></strong>?
                            <input type="hidden" name="delete_id" value="<?= $row['employee_id'] ?>">
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-danger" name="delete_employee">Delete</button>
                        </div>
                    </form>
                </div></div>
            </div>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
            <form method="POST">
                <div class="modal-header"><h5>Add Employee</h5></div>
                <div class="modal-body">
                    <input name="name" class="form-control mb-2" placeholder="Name" required>
                    <input name="email" class="form-control mb-2" placeholder="Email" required>
                    <select name="department_id" class="form-control mb-2" required>
                        <option value="">Select Department</option>
                        <?php
                        $depts = $conn->query("SELECT * FROM Departments");
                        while ($d = $depts->fetch_assoc()) {
                            echo "<option value='{$d['department_id']}'>{$d['name']}</option>";
                        }
                        ?>
                    </select>
                    <input name="position" class="form-control mb-2" placeholder="Position" required>
                    <input type="date" name="hire_date" class="form-control" required>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" name="add_employee">Add</button>
                </div>
            </form>
        </div></div>
    </div>
<script>
  $(document).ready(function() {
    $('#myTable').DataTable({
      dom: 'Bfrtip',
      buttons: [
        'copy', 'csv', 'excel', 'pdf', 'print'
      ],
      pageLength: 5,
      lengthMenu: [5, 10, 20],
      order: [[0, 'asc']]
    });
  });
</script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
