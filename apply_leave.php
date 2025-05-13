<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: ../index.php");
    exit;
}
include 'includes/db.php';
include 'includes/header.php';

$userId = $_SESSION['user_id'];
$message = '';
$types = $conn->query("SELECT * FROM Leave_Types WHERE leave_type_id IN (1,2,3)");

// Fetch holiday dates
$holidays = [];
$year = date('Y');
$holidayQuery = $conn->query("SELECT holiday_date FROM Holidays WHERE YEAR(holiday_date) = '$year'");
while ($row = $holidayQuery->fetch_assoc()) {
    $holidays[] = $row['holiday_date'];
}
$holidayJson = json_encode($holidays);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leave_type_id = (int)$_POST['leave_type'];
    $start_date    = $_POST['start_date'];
    $end_date      = $_POST['end_date'];
    $reason        = $_POST['reason'];
    $status        = in_array($_POST['action'], ['draft', 'pending']) ? $_POST['action'] : 'draft';
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);

    // Count only valid days (no weekends or holidays)
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

    $days = countValidDays($start, $end, $holidays);

    if ($status === 'pending') {
        if ($days > 3) {
            $message = "<div class='alert alert-warning'>You cannot apply for more than 3 valid leave days (excluding weekends & holidays).</div>";
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

                    $stmt = $conn->prepare("
                        INSERT INTO Leave_Requests (employee_id, leave_type_id, start_date, end_date, reason, status, requested_at)
                        VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->bind_param("iissss", $userId, $leave_type_id, $start_date, $end_date, $reason, $status);

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
        $stmt = $conn->prepare("
            INSERT INTO Leave_Requests (employee_id, leave_type_id, start_date, end_date, reason, status, requested_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("iissss", $userId, $leave_type_id, $start_date, $end_date, $reason, $status);

        if ($stmt->execute()) {
            header("Location: user_dashboard.php");
            exit;
        } else {
            $message = "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
        }
    }
}
?>

<main class="flex-grow-1 container py-4">
  <div class="d-flex justify-content-between mb-3">
    <a href="user_dashboard.php" class="btn btn-outline-secondary">← Back to Dashboard</a>
  </div>

  <h2 class="mb-4">Apply for Leave</h2>

  <?= $message ?>

  <form method="post">
    <div class="mb-3">
      <label class="form-label">Leave Type</label>
      <select name="leave_type" class="form-select" required>
        <option value="">-- Select --</option>
        <?php while ($type = $types->fetch_assoc()): ?>
          <option value="<?= $type['leave_type_id'] ?>">
            <?= htmlspecialchars($type['type_name']) ?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">Leave Dates (max 3 working days)</label>
      <input type="text" id="leave_range" class="form-control" placeholder="Select date range" required>
      <input type="hidden" name="start_date" id="start_date">
      <input type="hidden" name="end_date" id="end_date">
    </div>

    <div class="mb-3">
      <label class="form-label">Reason</label>
      <textarea name="reason" class="form-control" rows="3" required></textarea>
    </div>

    <div class="d-flex justify-content-between">
      <button type="submit" name="action" value="draft" class="btn btn-secondary">Save as Draft</button>
      <button type="submit" name="action" value="pending" class="btn btn-primary">Submit for Approval</button>
    </div>
  </form>
</main>

<!-- Scripts -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const holidays = <?= $holidayJson ?>;
  const today = new Date();
  const endOfYear = new Date(today.getFullYear(), 11, 31);

  // Helpers
  const isWeekend = date => [0, 6].includes(date.getDay());
  const isHoliday = date => holidays.includes(date.toISOString().split('T')[0]);

  const countValidDays = (start, end) => {
    let count = 0;
    const current = new Date(start);
    while (current <= end) {
      if (!isWeekend(current) && !isHoliday(current)) count++;
      current.setDate(current.getDate() + 1);
    }
    return count;
  };

  flatpickr("#leave_range", {
    mode: "range",
    dateFormat: "Y-m-d",
    minDate: today,
    maxDate: endOfYear,
    onDayCreate: function(dObj, dStr, fp, dayElem) {
      const dateStr = dayElem.dateObj.toISOString().split('T')[0];
      if (holidays.includes(dateStr)) {
        dayElem.classList.add('holiday');
        dayElem.setAttribute('title', 'Holiday');
      }
    },
    onClose: function(selectedDates) {
      if (selectedDates.length === 2) {
        const [start, end] = selectedDates;
        const validDays = countValidDays(start, end);
        if (validDays > 3) {
          alert("You can apply for a maximum of 3 working days (excluding weekends & holidays).");
          this.clear();
          document.getElementById("start_date").value = '';
          document.getElementById("end_date").value = '';
        } else {
          document.getElementById("start_date").value = start.toISOString().split('T')[0];
          document.getElementById("end_date").value = end.toISOString().split('T')[0];
        }
      }
    }
    
  });});
</script>


<style>
  .holiday {
    background-color: #ffc107 !important;
    color: #000;
  }
</style>
