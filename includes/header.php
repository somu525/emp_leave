<?php
if (!isset($_SESSION)) session_start();
$name = $_SESSION['name'] ?? 'User';
$role = $_SESSION['role'] ?? '';
$email = $_SESSION['email'] ?? '';
$dashboard = $role === 'admin' ? 'Admin Portal' : 'Employee Portal';
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<!-- Date Range Picker Dependencies -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<!-- Flatpickr CSS & JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<style>
  #sidebar {
    height: 100vh;
    width: 250px;
    position: fixed;
    top: 0;left:-250px;
    background-color: #f8f9fa;
    transition: left 0.3s;
    z-index: 999;
    padding-top: 60px;
  }
  #sidebar.active { left: 0; }
  .sidebar-link { padding: 10px 20px; display: block; color: #333; text-decoration: none; }
  .sidebar-link:hover { background-color: #e9ecef; }

  #main-content { margin-left: 0; transition: margin-left 0.3s; }
  #main-content.shifted { margin-left: 250px; }
</style>


<header class="bg-black text-white py-2 px-3 d-flex justify-content-between align-items-center shadow fixed-top" style="z-index:1000;">
  <div class="d-flex align-items-center">
    <i class="fas fa-bars me-3 cursor-pointer" style="cursor:pointer;" onclick="toggleSidebar()"></i>
    <strong>ELMS</strong>
  </div>
  <div><h5 class="mb-0"><?= $dashboard ?></h5></div>
  <div class="d-flex align-items-center">
    <i class="fas fa-user-circle me-2"></i>
    <?= htmlspecialchars($name) ?>
    <a href="logout.php" class="btn btn-sm btn-light ms-3">Logout</a>
  </div>
</header>


<div id="sidebar" class="shadow">
  <div class="text-center">
    <i class="fas fa-user-circle fa-3x mb-2 mt-3"></i>
    <h6><?= htmlspecialchars($name) ?></h6>
    <small class="text-muted"><?= ucfirst($role) ?></small><br>
    <small class="text-muted"><?= htmlspecialchars($email) ?></small>
  </div>
  <hr>

  <?php if ($role === 'admin'): ?>
   
    <a href="admin_dashboard.php" class="sidebar-link">
      <i class="fas fa-home me-2"></i>Dashboard
    </a>
    <a href="departments.php" class="sidebar-link">
      <i class="fas fa-building me-2"></i>Departments
    </a>
    <a href="approve_employee.php" class="sidebar-link">
      <i class="fas fa-user-check me-2"></i>Employees
    </a>
    <a href="approve_leave.php" class="sidebar-link">
      <i class="fas fa-check-circle me-2"></i>Approve Leave
    </a>

  <?php else: ?>
    
    <a href="user_dashboard.php" class="sidebar-link">
      <i class="fas fa-home me-2"></i>Dashboard
    </a>
    <a href="apply_leave.php" class="sidebar-link">
      <i class="fas fa-paper-plane me-2"></i>Apply Leave
    </a>
    <a href="drafts.php" class="sidebar-link">
      <i class="fas fa-file-alt me-2"></i>Drafts
    </a>
  <?php endif; ?>
  <a href="holidays.php" class="sidebar-link">
    <i class="fas fa-calendar-alt me-2"></i>Holidays
  </a>
</div>

<script>
  function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
    document.getElementById('main-content').classList.toggle('shifted');
  }
</script>
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
<?php
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 60*60)) {
    session_unset();
    session_destroy();
 
    echo "<script>
    alert('Session expired. You will be logged out.');
    window.location.href = 'logout.php';
</script>";
    exit;
}
 ?>
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<div id="main-content" class="pt-5 mt-4 px-3 " >

