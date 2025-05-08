<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}
include 'includes/db.php';
include 'includes/header.php';

// Add
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

// Edit
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

// Delete
if (isset($_POST['delete_department'])) {
    $id = $_POST['delete_id'];
    $stmt = $conn->prepare("DELETE FROM Departments WHERE department_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: departments.php");
    exit;
}

// Fetch departments
$departments = $conn->query("SELECT * FROM Departments ORDER BY department_id ");
?>

<main class="container py-4">
  <div class="d-flex justify-content-between mb-3">
    <h2>Departments</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDeptModal">+ Add Department</button>
  </div>

  <table class="table table-bordered">
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

<!-- Add Department Modal -->
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

<!-- Edit Department Modal -->
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

<!-- Delete Department Modal -->
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
  // Edit modal data fill
  document.querySelectorAll('.editBtn').forEach(btn => {
    btn.addEventListener('click', function () {
      document.getElementById('editDeptId').value = this.dataset.id;
      document.getElementById('editDeptName').value = this.dataset.name;
    });
  });

  // Delete modal ID set
  document.querySelectorAll('.deleteBtn').forEach(btn => {
    btn.addEventListener('click', function () {
      document.getElementById('deleteDeptId').value = this.dataset.id;
    });
  });
</script>

<?php include 'includes/footer.php'; ?>
