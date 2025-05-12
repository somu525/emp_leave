<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: index.php");
    exit;
}
include 'includes/db.php';
include 'includes/header.php';

$userId = $_SESSION['user_id'];
$message = '';

if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $conn->query("
      DELETE FROM Leave_Requests 
      WHERE request_id = $delId 
        AND employee_id = $userId 
        AND status = 'draft'
    ");
    header("Location: drafts.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'])) {
    $reqId         = (int)$_POST['request_id'];
    $leave_type_id = (int)$_POST['leave_type'];
    $start_date    = $_POST['start_date'];
    $end_date      = $_POST['end_date'];
    $reason        = $_POST['reason'];
    $status        = ($_POST['action'] === 'pending' ? 'pending' : 'draft');

    $upd = $conn->prepare("
      UPDATE Leave_Requests
      SET leave_type_id=?, start_date=?, end_date=?, reason=?, status=?
      WHERE request_id=? AND employee_id=?
    ");
    $upd->bind_param(
      "isssiii",
      $leave_type_id,
      $start_date,
      $end_date,
      $reason,
      $status,
      $reqId,
      $userId
    );
    $upd->execute();
    $message = "<div class='alert alert-success'>Draft " . 
               ($status==='pending' ? "pending" : "updated") . " successfully.</div>";
              
                header("Location: user_dashboard.php");
                
            
}

$editing = false;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $res = $conn->prepare("
      SELECT * FROM Leave_Requests 
      WHERE request_id=? AND employee_id=? AND status='draft'
    ");
    $res->bind_param("ii", $editId, $userId);
    $res->execute();
    $draft = $res->get_result()->fetch_assoc();
    if ($draft) {
        $editing = true;
    }
}

$types = $conn->query("SELECT * FROM Leave_Types WHERE leave_type_id IN (1,2,3)");
$drafts = $conn->prepare("
  SELECT r.request_id, l.type_name, r.start_date, r.end_date 
  FROM Leave_Requests r
  JOIN Leave_Types l ON r.leave_type_id=l.leave_type_id
  WHERE r.employee_id=? AND r.status='draft'
  ORDER BY r.requested_at DESC
");
$drafts->bind_param("i", $userId);
$drafts->execute();
$draftList = $drafts->get_result();
?>

<main class="flex-grow-1 container py-4">
  <h2 class="mb-4">My Drafts</h2>
  <?= $message ?>

  <?php if (!$editing): ?>
    <?php if ($draftList->num_rows): ?>
      <table class="table table-striped">
        <thead>
          <tr><th>#</th><th>Type</th><th>From</th><th>To</th><th>Edit/Delete</th></tr>
        </thead>
        <tbody>
          <?php while($d = $draftList->fetch_assoc()): ?>
            <tr>
              <td><?= $d['request_id'] ?></td>
              <td><?= htmlspecialchars($d['type_name']) ?></td>
              <td><?= $d['start_date'] ?></td>
              <td><?= $d['end_date'] ?></td>
              <td>
                <a href="?edit=<?= $d['request_id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                <a href="?delete=<?= $d['request_id'] ?>"
                   class="btn btn-sm btn-outline-danger"
                   onclick="return confirm('Delete this draft?');">
                  Delete
                </a>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="alert alert-info">No drafts found.</div>
    <?php endif; ?>
  <?php endif; ?>

  <?php if ($editing): ?>
    <div class="card p-4 mb-4">
      <h4>Edit Draft</h4>
      <form method="post">
        <input type="hidden" name="request_id" value="<?= $draft['request_id'] ?>">
        <div class="mb-3">
          <label class="form-label">Leave Type</label>
          <select name="leave_type" class="form-select" required>
            <?php foreach ($types as $t): ?>
              <option value="<?= $t['leave_type_id'] ?>"
                <?= $t['leave_type_id']==$draft['leave_type_id']?'selected':'' ?>>
                <?= htmlspecialchars($t['type_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Start Date</label>
          <input type="date" name="start_date" class="form-control"
                 value="<?= $draft['start_date'] ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">End Date</label>
          <input type="date" name="end_date" class="form-control"
                 value="<?= $draft['end_date'] ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Reason</label>
          <textarea name="reason" class="form-control" rows="3" required><?= 
            htmlspecialchars($draft['reason']) ?></textarea>
        </div>
        <div class="d-flex justify-content-between">
          <div class="d-flex justify-content-start gap-1"><button type="submit" name="action" value="draft" class="btn btn-secondary">
            Save as Draft
          </button>
          <a href="?delete=<?= $draft['request_id'] ?>" class="btn btn-danger"
         onclick="return confirm('Delete this draft permanently?');">
        Delete Draft
      </a></div>
          
          <button type="submit" name="action" value="pending" class="btn btn-primary">
            Submit for Approval
          </button>
        </div>
      </form>
      
    </div>
  <?php endif; ?>

  <a href="user_dashboard.php" class="btn btn-outline-secondary">← Back to Dashboard</a>
  
</main>

