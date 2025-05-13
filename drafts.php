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

// Fetch holidays from database
$holidaysQuery = $conn->query("SELECT holiday_date FROM Holidays");
$holidays = [];
while ($row = $holidaysQuery->fetch_assoc()) {
  $holidays[] = $row['holiday_date'];
}
$holidayJSArray = json_encode($holidays);

// DELETE DRAFT
if (isset($_GET['delete'])) {
  $delId = (int)$_GET['delete'];
  $conn->query("DELETE FROM Leave_Requests WHERE request_id = $delId AND employee_id = $userId AND status = 'draft'");
  header("Location: drafts.php");
  exit;
}

// SUBMIT OR SAVE DRAFT
if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['request_id'], $_POST['start_date'], $_POST['end_date']) &&
    !empty($_POST['start_date']) && !empty($_POST['end_date'])) {

  $reqId         = (int)$_POST['request_id'];
  $leave_type_id = (int)$_POST['leave_type'];
  $start_date    = $_POST['start_date'];
  $end_date      = $_POST['end_date'];
  $reason        = $_POST['reason'];
  $status        = in_array($_POST['action'], ['draft', 'pending']) ? $_POST['action'] : 'draft';

  $start = new DateTime($start_date);
  $end = new DateTime($end_date);

  // Fetch holidays again for PHP side validation
  $holidaysStmt = $conn->query("SELECT holiday_date FROM Holidays");
  $holidayDates = [];
  while ($row = $holidaysStmt->fetch_assoc()) {
    $holidayDates[] = $row['holiday_date'];
  }

  function countValidDays($start, $end, $holidays) {
    $count = 0;
    $current = clone $start;
    while ($current <= $end) {
      $day = $current->format('N');
      $dateStr = $current->format('Y-m-d');
      if ($day < 6 && !in_array($dateStr, $holidays)) {
        $count++;
      }
      $current->modify('+1 day');
    }
    return $count;
  }

  $days = countValidDays($start, $end, $holidayDates);

  if ($status === 'pending') {
    if ($days > 3) {
      $message = "<div class='alert alert-warning'>You cannot apply for more than 3 valid days (excluding weekends and holidays).</div>";
    } else {
      $year = date('Y');
      $check = $conn->prepare("SELECT total_allocated, used FROM leave_balances WHERE employee_id = ? AND leave_type_id = ? AND year = ?");
      $check->bind_param("iii", $userId, $leave_type_id, $year);
      $check->execute();
      $result = $check->get_result()->fetch_assoc();

      if (!$result) {
        $message = "<div class='alert alert-danger'>No leave balance found for this type.</div>";
      } else {
        $remaining = $result['total_allocated'] - $result['used'];
        if ($days > $remaining) {
          $message = "<div class='alert alert-danger'>You have only $remaining days left for this leave type.</div>";
        } else {
          $update = $conn->prepare("UPDATE leave_balances SET used = used + ? WHERE employee_id = ? AND leave_type_id = ? AND year = ?");
          $update->bind_param("iiii", $days, $userId, $leave_type_id, $year);
          $update->execute();

          $stmt = $conn->prepare("UPDATE Leave_Requests SET leave_type_id=?, start_date=?, end_date=?, reason=?, status=? WHERE request_id=? AND employee_id=?");
          $stmt->bind_param("issssii", $leave_type_id, $start_date, $end_date, $reason, $status, $reqId, $userId);

          if ($stmt->execute()) {
            header("Location: user_dashboard.php");
            exit;
          } else {
            $message = "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
          }
        }
      }
    }
  } else {
    // Just save as draft
    $stmt = $conn->prepare("UPDATE Leave_Requests SET leave_type_id=?, start_date=?, end_date=?, reason=?, status=? WHERE request_id=? AND employee_id=?");
    $stmt->bind_param("issssii", $leave_type_id, $start_date, $end_date, $reason, $status, $reqId, $userId);
    if ($stmt->execute()) {
      $message = "<div class='alert alert-success'>Draft saved successfully.</div>";
    } else {
      $message = "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
    }
  }
}

// Get leave types
$types = $conn->query("SELECT * FROM Leave_Types WHERE leave_type_id IN (1,2,3)");

// Edit mode
$editing = false;
$draft = null;
if (isset($_GET['edit'])) {
  $editId = (int)$_GET['edit'];
  $res = $conn->prepare("SELECT * FROM Leave_Requests WHERE request_id=? AND employee_id=? AND status='draft'");
  $res->bind_param("ii", $editId, $userId);
  $res->execute();
  $draft = $res->get_result()->fetch_assoc();
  if ($draft) $editing = true;
}

// Drafts list
$drafts = $conn->prepare("
  SELECT r.request_id, l.type_name, r.start_date, r.end_date
  FROM Leave_Requests r
  JOIN Leave_Types l ON r.leave_type_id = l.leave_type_id
  WHERE r.employee_id = ? AND r.status = 'draft'
  ORDER BY r.requested_at DESC
");
$drafts->bind_param("i", $userId);
$drafts->execute();
$draftList = $drafts->get_result();
?>

<main class="container py-4">
  <h2>My Drafts</h2>
  <?= $message ?>

  <?php if (!$editing): ?>
    <?php if ($draftList->num_rows): ?>
      <table class="table table-striped">
        <thead><tr><th>#</th><th>Type</th><th>From</th><th>To</th><th>Actions</th></tr></thead>
        <tbody>
        <?php while($d = $draftList->fetch_assoc()): ?>
          <tr>
            <td><?= $d['request_id'] ?></td>
            <td><?= htmlspecialchars($d['type_name']) ?></td>
            <td><?= $d['start_date'] ?></td>
            <td><?= $d['end_date'] ?></td>
            <td>
              <a href="?edit=<?= $d['request_id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
              <a href="?delete=<?= $d['request_id'] ?>" onclick="return confirm('Delete this draft?')" class="btn btn-sm btn-outline-danger">Delete</a>
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
              <option value="<?= $t['leave_type_id'] ?>" <?= $t['leave_type_id'] == $draft['leave_type_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($t['type_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Leave Dates (max 3 working days)</label>
          <input type="text" id="leave_range" class="form-control" placeholder="Select date range" required>
          <input type="hidden" name="start_date" id="start_date" value="<?= $draft['start_date'] ?>">
          <input type="hidden" name="end_date" id="end_date" value="<?= $draft['end_date'] ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Reason</label>
          <textarea name="reason" class="form-control" required><?= htmlspecialchars($draft['reason']) ?></textarea>
        </div>
        <div class="d-flex justify-content-between">
          <div class="d-flex gap-2">
            <button type="submit" name="action" value="draft" class="btn btn-secondary">Save as Draft</button>
            <a href="?delete=<?= $draft['request_id'] ?>" class="btn btn-danger" onclick="return confirm('Delete this draft permanently?')">Delete</a>
          </div>
          <button type="submit" name="action" value="pending" class="btn btn-primary">Submit for Approval</button>
        </div>
      </form>
    </div>
  <?php endif; ?>

  <a href="user_dashboard.php" class="btn btn-outline-secondary">← Back to Dashboard</a>
</main>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
const holidays = <?= $holidayJSArray ?>;
const startHidden = document.getElementById('start_date');
const endHidden = document.getElementById('end_date');
const preStart = startHidden.value;
const preEnd = endHidden.value;

function countValidDays(start, end) {
  let count = 0;
  let current = new Date(start);
  while (current <= end) {
    const day = current.getDay();
    const dateStr = current.toISOString().split('T')[0];
    if (day !== 0 && day !== 6 && !holidays.includes(dateStr)) {
      count++;
    }
    current.setDate(current.getDate() + 1);
  }
  return count;
}

document.addEventListener('DOMContentLoaded', () => {
  flatpickr("#leave_range", {
    mode: "range",
    dateFormat: "Y-m-d",
    minDate: "today",
    maxDate: new Date().getFullYear() + "-12-31",
    defaultDate: (preStart && preEnd) ? [preStart, preEnd] : null,
    disable: holidays,
    onClose: function(selectedDates) {
      if (selectedDates.length === 2) {
        const [start, end] = selectedDates;
        const validDays = countValidDays(start, end);
        if (validDays > 3) {
          alert("You can select only up to 3 working days (excluding weekends and holidays).");
          this.clear();
          startHidden.value = '';
          endHidden.value = '';
        } else {
          startHidden.value = start.toISOString().split('T')[0];
          endHidden.value = end.toISOString().split('T')[0];
        }
      }
    },
    onDayCreate: function(dObj, dStr, fp, dayElem) {
      const date = dayElem.dateObj.toISOString().split('T')[0];
      if (holidays.includes(date)) {
        dayElem.classList.add("bg-danger", "text-white");
        dayElem.title = "Holiday";
      }
    }
  });
});
</script>
