<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role(['Administrator', 'SACCO Official']);
$pdo = db(); $errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? 'add';
    if ($action === 'add') {
        $r = add_vehicle($pdo, $_POST);
        if ($r['ok']) { flash('Vehicle registered successfully.'); header('Location: vehicles.php'); exit; }
        $errors = $r['errors'];
    } elseif ($action === 'status') {
        update_vehicle_status($pdo, (int)$_POST['vehicle_id'], $_POST['status'] ?? '');
        flash('Vehicle status updated.'); header('Location: vehicles.php'); exit;
    } elseif ($action === 'delete' && has_role(['Administrator'])) {
        try { $pdo->prepare('DELETE FROM vehicles WHERE vehicle_id=?')->execute([(int)$_POST['vehicle_id']]); flash('Vehicle deleted.'); }
        catch (PDOException $ex) { flash('Cannot delete: the vehicle has recorded trips.'); }
        header('Location: vehicles.php'); exit;
    }
}
$vehicles = fetch_all($pdo, 'SELECT * FROM vehicles ORDER BY registration_number');
page_header('Vehicle Registration'); show_errors($errors);
?>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="action" value="add">
  <label>Registration number</label><input name="registration_number" placeholder="KDA 123A" value="<?= e($_POST['registration_number'] ?? '') ?>" required>
  <label>Vehicle model</label><input name="vehicle_model" value="<?= e($_POST['vehicle_model'] ?? '') ?>" required>
  <label>Seating capacity</label><input name="seating_capacity" type="number" min="1" max="70" value="<?= e($_POST['seating_capacity'] ?? '14') ?>" required>
  <label>Owner's name</label><input name="owner_name" value="<?= e($_POST['owner_name'] ?? '') ?>" required>
  <label>Status</label><select name="status"><option>Active</option><option>Inactive</option><option>Maintenance</option></select>
  <button type="submit">Save vehicle</button>
</form>
<table><tr><th>Reg. No.</th><th>Model</th><th>Seats</th><th>Owner</th><th>Status</th><th>Registered</th><th></th></tr>
<?php foreach ($vehicles as $v): ?>
<tr><td><?= e($v['registration_number']) ?></td><td><?= e($v['vehicle_model']) ?></td><td><?= (int)$v['seating_capacity'] ?></td>
<td><?= e($v['owner_name']) ?></td>
<td><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="status"><input type="hidden" name="vehicle_id" value="<?= (int)$v['vehicle_id'] ?>">
  <select name="status"><?php foreach (['Active','Inactive','Maintenance'] as $s) echo '<option' . ($s === $v['status'] ? ' selected' : '') . '>' . $s . '</option>'; ?></select>
  <button type="submit">Update</button></form></td>
<td><?= e($v['registration_date']) ?></td>
<td><?php if (has_role(['Administrator'])): ?><form method="post" onsubmit="return confirm('Delete this vehicle?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="vehicle_id" value="<?= (int)$v['vehicle_id'] ?>"><button class="danger">Delete</button></form><?php endif; ?></td></tr>
<?php endforeach; ?></table>
<?php page_footer();
