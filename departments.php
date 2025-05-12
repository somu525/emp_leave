<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}
include 'includes/db.php';
include 'includes/header.php';

if (isset($_POST['add_department'])) {
    $name = trim($_POST['add_name']);
    if ($name !== '') {
        $stmt = $conn->prepare("INSERT INTO Departments (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        $stmt->execute();
    }
    header("Location: departments.php");
    exit;
}

if (isset($_POST['update_department'])) {
    $id = $_POST['edit_id'];
    $name = trim($_POST['edit_name']);
    if ($name !== '') {
        $stmt = $conn->prepare("UPDATE Departments SET name=? WHERE department_id=?");
        $stmt->bind_param("si", $name, $id);
        $stmt->execute();
    }
    header("Location: departments.php");
    exit;
}

if (isset($_POST['delete_department'])) {
    $id = $_POST['delete_id'];
    $stmt = $conn->prepare("DELETE FROM Departments WHERE department_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: departments.php");
    exit;
}
$departments = $conn->query("SELECT * FROM Departments ORDER BY department_id ");
?>
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
<main class="container py-4">
  <div class="d-flex justify-content-between mb-3">
    <h2>Departments</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDeptModal">+ Add Department</button>
  </div>

  <table id="myTable" class="table table-striped">
    <thead class="table-light">
      <tr>
        <th>ID</th>
        <th>Department Name</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php $i = 1;while ($dept = $departments->fetch_assoc()): ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><?= htmlspecialchars($dept['name']) ?></td>
          <td>
            <button 
              class="btn btn-sm btn-warning editBtn"
              data-id="<?= $dept['department_id'] ?>"
              data-name="<?= htmlspecialchars($dept['name']) ?>"
              data-bs-toggle="modal" data-bs-target="#editDeptModal">Edit</button>
            <button 
              class="btn btn-sm btn-danger deleteBtn"
              data-id="<?= $dept['department_id'] ?>"
              data-bs-toggle="modal" data-bs-target="#deleteDeptModal">Delete</button>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</main>

<div class="modal fade" id="addDeptModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Department</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Department Name</label>
          <input type="text" class="form-control" name="add_name" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="add_department" class="btn btn-success">Add</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="editDeptModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Department</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="edit_id" id="editDeptId">
        <div class="mb-3">
          <label class="form-label">Department Name</label>
          <input type="text" class="form-control" name="edit_name" id="editDeptName" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="update_department" class="btn btn-primary">Update</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="deleteDeptModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirm Delete</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete this department?
        <input type="hidden" name="delete_id" id="deleteDeptId">
      </div>
      <div class="modal-footer">
        <button type="submit" name="delete_department" class="btn btn-danger">Delete</button>
      </div>
    </form>
  </div>
</div>

<script>
  document.querySelectorAll('.editBtn').forEach(btn => {
    btn.addEventListener('click', function () {
      document.getElementById('editDeptId').value = this.dataset.id;
      document.getElementById('editDeptName').value = this.dataset.name;
    });
  });

  document.querySelectorAll('.deleteBtn').forEach(btn => {
    btn.addEventListener('click', function () {
      document.getElementById('deleteDeptId').value = this.dataset.id;
    });
  });
 
  $(document).ready(function () {
    const table = $('#myTable').DataTable({
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

<?php include 'includes/footer.php'; ?>
