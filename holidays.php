<?php
session_start();
include 'includes/db.php';
include 'includes/header.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Holidays</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="d-flex flex-column min-vh-100">
  <main class="flex-grow-1 container mt-4">
    <h4>Holiday List</h4>
    <table class="table table-bordered table-striped mt-3">
      <thead class="table-dark">
        <tr>
          <th>#</th>
          <th>Date</th>
          <th>Description</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $result = $conn->query("SELECT * FROM Holidays ORDER BY holiday_date ASC");
        if ($result->num_rows > 0) {
            $i = 1;
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>{$i}</td>
                        <td>{$row['holiday_date']}</td>
                        <td>{$row['description']}</td>
                      </tr>";
                $i++;
            }
        } else {
            echo "<tr><td colspan='3' class='text-center'>No holidays found.</td></tr>";
        }
        ?>
      </tbody>
    </table>
    <a href="user_dashboard.php" class="btn btn-outline-secondary">← Back to Dashboard</a>
  </main>

  <footer class="text-center mt-auto py-3 text-muted small bottom-0">
  &copy; <?= date("Y") ?> Employee Leave Portal
</footer>

</body>
</html>
