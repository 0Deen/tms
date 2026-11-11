<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role(['Administrator', 'SACCO Official']);
$pdo = db(); $errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $r = add_staff($pdo, $_POST);
    if ($r['ok']) { flash('Staff member registered successfully.'); header('Location: staff.php'); exit; }
    $errors = $r['errors'];
}
$staff = fetch_all($pdo, 'SELECT * FROM staff ORDER BY staff_role, full_name');
page_header('Staff Registration (Drivers & Conductors)'); show_errors($errors);
?>
<form method="post" class="card"><?= csrf_field() ?>
  <label>Full name</label><input name="full_name" value="<?= e($_POST['full_name'] ?? '') ?>" required>
  <label>National ID</label><input name="national_id" value="<?= e($_POST['national_id'] ?? '') ?>" required>
  <label>Phone</label><input name="phone" placeholder="0712345678" value="<?= e($_POST['phone'] ?? '') ?>" required>
  <label>Role</label><select name="staff_role"><option>Driver</option><option>Conductor</option></select>
  <label>Licence number (drivers)</label><input name="license_number" value="<?= e($_POST['license_number'] ?? '') ?>">
  <button type="submit">Save staff</button>
</form>
<table><tr><th>Name</th><th>National ID</th><th>Phone</th><th>Role</th><th>Licence</th><th>Status</th></tr>
<?php foreach ($staff as $s): ?>
<tr><td><?= e($s['full_name']) ?></td><td><?= e($s['national_id']) ?></td><td><?= e($s['phone']) ?></td><td><?= e($s['staff_role']) ?></td><td><?= e($s['license_number']) ?></td><td><?= e($s['status']) ?></td></tr>
<?php endforeach; ?></table>
<?php page_footer();
