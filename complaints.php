<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role(['Administrator', 'SACCO Official']);
$pdo = db(); $errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? 'add') === 'status') {
        update_complaint_status($pdo, (int)$_POST['complaint_id'], $_POST['status'] ?? '');
        flash('Complaint status updated.'); header('Location: complaints.php'); exit;
    }
    $r = add_complaint($pdo, $_POST);
    if ($r['ok']) { flash('Complaint recorded.'); header('Location: complaints.php'); exit; }
    $errors = $r['errors'];
}
$passengers = fetch_all($pdo, 'SELECT passenger_id, full_name, phone_number FROM passengers ORDER BY full_name');
$trips = list_trips($pdo, 100);
$complaints = fetch_all($pdo, 'SELECT c.*, p.full_name, t.trip_date, v.registration_number FROM complaints c
    JOIN passengers p ON p.passenger_id=c.passenger_id JOIN trips t ON t.trip_id=c.trip_id JOIN vehicles v ON v.vehicle_id=t.vehicle_id ORDER BY c.complaint_date DESC');
page_header('Complaint Handling'); show_errors($errors);
?>
<form method="post" class="card"><?= csrf_field() ?>
  <label>Passenger</label><select name="passenger_id" required><option value="">-- select --</option>
  <?php foreach ($passengers as $p) echo '<option value="' . (int)$p['passenger_id'] . '">' . e($p['full_name'] . ' (' . $p['phone_number'] . ')') . '</option>'; ?></select>
  <label>Trip</label><select name="trip_id" required><option value="">-- select --</option>
  <?php foreach ($trips as $t) echo '<option value="' . (int)$t['trip_id'] . '">' . e('#' . $t['trip_id'] . ' - ' . $t['trip_date'] . ' - ' . $t['registration_number']) . '</option>'; ?></select>
  <label>Complaint</label><textarea name="complaint_description" rows="3" required></textarea>
  <button type="submit">Submit complaint</button>
</form>
<table><tr><th>Date</th><th>Passenger</th><th>Trip</th><th>Complaint</th><th>Status</th></tr>
<?php foreach ($complaints as $c): ?>
<tr><td><?= e(substr($c['complaint_date'], 0, 10)) ?></td><td><?= e($c['full_name']) ?></td><td><?= e($c['registration_number'] . ' / ' . $c['trip_date']) ?></td><td><?= e($c['complaint_description']) ?></td>
<td><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="status"><input type="hidden" name="complaint_id" value="<?= (int)$c['complaint_id'] ?>">
<select name="status"><?php foreach (['Open','In Progress','Resolved'] as $s) echo '<option' . ($s === $c['status'] ? ' selected' : '') . '>' . $s . '</option>'; ?></select><button type="submit">Update</button></form></td></tr>
<?php endforeach; ?></table>
<?php page_footer();
