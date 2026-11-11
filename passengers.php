<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role(['Administrator', 'SACCO Official', 'Conductor']);
$pdo = db(); $errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $r = add_passenger($pdo, $_POST);
    if ($r['ok']) { flash('Passenger saved.'); header('Location: passengers.php'); exit; }
    $errors = $r['errors'];
}
$searched = isset($_GET['name']) || isset($_GET['phone']);
$results = $searched ? search_passengers($pdo, $_GET['name'] ?? '', $_GET['phone'] ?? '') : [];
page_header('Passenger Search'); show_errors($errors);
?>
<form method="get" class="card">
  <label>Passenger name</label><input name="name" value="<?= e($_GET['name'] ?? '') ?>">
  <label>Phone number</label><input name="phone" value="<?= e($_GET['phone'] ?? '') ?>">
  <button type="submit">Search</button>
</form>
<?php if ($searched): ?>
<table><tr><th>Name</th><th>Phone</th><th>Email</th><th>Registered</th></tr>
<?php foreach ($results as $p): ?><tr><td><?= e($p['full_name']) ?></td><td><?= e($p['phone_number']) ?></td><td><?= e($p['email']) ?></td><td><?= e($p['registration_date']) ?></td></tr><?php endforeach; ?>
<?php if (!$results) echo '<tr><td colspan="4">No matching passengers found.</td></tr>'; ?></table>
<?php endif; ?>
<h2>Register a passenger</h2>
<form method="post" class="card"><?= csrf_field() ?>
  <label>Full name</label><input name="full_name" required>
  <label>Phone number</label><input name="phone_number" placeholder="0712345678" required>
  <label>Email (optional)</label><input name="email" type="email">
  <button type="submit">Save passenger</button>
</form>
<?php page_footer();
