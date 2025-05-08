<?php
session_start();
include 'includes/db.php';
include 'includes/header.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Fetch holidays
$holidays = $conn->query("SELECT * FROM Holidays ORDER BY holiday_date ASC");

// Handle add holiday
if (isset($_POST['addHoliday'])) {
    $name = $_POST['name'];
    $date = $_POST['date'];
    $stmt = $conn->prepare("INSERT INTO Holidays (description, holiday_date) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $date);
    $stmt->execute();
    header("Location: holidays.php");
    exit;
}

// Handle update
if (isset($_POST['editHoliday'])) {
    $id = $_POST['holiday_id'];
    $name = $_POST['name'];
    $date = $_POST['date'];
    $stmt = $conn->prepare("UPDATE Holidays SET description = ?, holiday_date = ? WHERE holiday_id = ?");
    $stmt->bind_param("ssi", $name, $date, $id);
    $stmt->execute();
    header("Location: holidays.php");
    exit;
}

// Handle delete
if (isset($_POST['deleteHoliday'])) {
    $id = $_POST['holiday_id'];
    $stmt = $conn->prepare("DELETE FROM Holidays WHERE holiday_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: holidays.php");
    exit;
}
?>

<main class="container py-4">
  <div class="d-flex justify-content-between mb-3">
    <h2>Holidays</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">+ Add Holiday</button>
  </div>

  <table class="table table-bordered table-hover">
    <thead class="table-light">
      <tr>
        <th>#</th>
        <th>Holiday Name</th>
        <th>Date</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php $i = 1; while ($row = $holidays->fetch_assoc()): ?>
      <tr>
        <td><?= $i++ ?></td>
        <td><?= htmlspecialchars($row['description']) ?></td>
        <td><?= $row['holiday_date'] ?></td>
        <td>
          <!-- Edit Button -->
          <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['holiday_id'] ?>">Edit</button>

          <!-- Delete Button -->
          <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $row['holiday_id'] ?>">Delete</button>
        </td>
      </tr>

      <!-- Edit Modal -->
      
      <?php endwhile; ?>
    </tbody>
  </table>
</main>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Holiday</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Holiday Name</label>
          <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Date</label>
          <input type="date" name="date" class="form-control" required>
        </div>
      </div>
      <div class="modal-footer">
        <button name="addHoliday" class="btn btn-success">Add Holiday</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>
<?php
// Re-fetch holidays to loop for modals again
$holidays->data_seek(0);
while ($row = $holidays->fetch_assoc()):
?>

<!-- Edit Modal -->
<div class="modal fade" id="editModal<?= $row['holiday_id'] ?>" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Holiday</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="holiday_id" value="<?= $row['holiday_id'] ?>">
        <div class="mb-3">
          <label class="form-label">Holiday Name</label>
          <input type="text" name="name" value="<?= htmlspecialchars($row['description']) ?>" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Date</label>
          <input type="date" name="date" value="<?= $row['holiday_date'] ?>" class="form-control" required>
        </div>
      </div>
      <div class="modal-footer">
        <button name="editHoliday" class="btn btn-primary">Save Changes</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal<?= $row['holiday_id'] ?>" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Delete Holiday</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="holiday_id" value="<?= $row['holiday_id'] ?>">
        Are you sure you want to delete "<strong><?= htmlspecialchars($row['description']) ?></strong>"?
      </div>
      <div class="modal-footer">
        <button name="deleteHoliday" class="btn btn-danger">Yes, Delete</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>
<?php endwhile; ?>


<?php include 'includes/footer.php'; ?>
