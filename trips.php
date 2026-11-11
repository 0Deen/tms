<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role(['Administrator', 'SACCO Official', 'Conductor', 'Driver']);
$pdo = db(); $errors = [];
$canRecord = has_role(['Administrator', 'SACCO Official', 'Conductor']);
if ($canRecord && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $r = record_trip($pdo, $_POST);
    if ($r['ok']) { flash('Trip saved. Revenue calculated: KES ' . number_format($r['revenue'], 2)); header('Location: trips.php'); exit; }
    $errors = $r['errors'];
}
$vehicles = fetch_all($pdo, "SELECT * FROM vehicles WHERE status='Active' ORDER BY registration_number");
$routes = fetch_all($pdo, 'SELECT * FROM routes ORDER BY route_name');
$drivers = fetch_all($pdo, "SELECT * FROM staff WHERE staff_role='Driver' AND status='Active' ORDER BY full_name");
$conductors = fetch_all($pdo, "SELECT * FROM staff WHERE staff_role='Conductor' AND status='Active' ORDER BY full_name");
$trips = list_trips($pdo);
page_header('Trip Recording'); show_errors($errors);
function opts(array $rows, string $id, string $label) { foreach ($rows as $r) echo '<option value="' . (int)$r[$id] . '">' . e($r[$label]) . '</option>'; }
if ($canRecord): ?>
<form method="post" class="card"><?= csrf_field() ?>
  <label>Vehicle</label><select name="vehicle_id" required><option value="">-- select --</option><?php opts($vehicles, 'vehicle_id', 'registration_number'); ?></select>
  <label>Route</label><select name="route_id" required><option value="">-- select --</option><?php opts($routes, 'route_id', 'route_name'); ?></select>
  <label>Driver</label><select name="driver_id" required><option value="">-- select --</option><?php opts($drivers, 'staff_id', 'full_name'); ?></select>
  <label>Conductor</label><select name="conductor_id" required><option value="">-- select --</option><?php opts($conductors, 'staff_id', 'full_name'); ?></select>
  <label>Trip date</label><input type="date" name="trip_date" value="<?= date('Y-m-d') ?>" required>
  <label>Departure time</label><input type="time" name="departure_time" required>
  <label>Arrival time</label><input type="time" name="arrival_time" required>
  <label>Number of passengers</label><input type="number" name="passengers_count" min="0" required>
  <p><small>Total revenue is calculated automatically (passengers x route fare).</small></p>
  <button type="submit">Save trip</button>
</form>
<?php endif; ?>
<table><tr><th>Date</th><th>Vehicle</th><th>Route</th><th>Driver</th><th>Conductor</th><th>Dep.</th><th>Arr.</th><th>Pax</th><th>Revenue (KES)</th></tr>
<?php foreach ($trips as $t): ?>
<tr><td><?= e($t['trip_date']) ?></td><td><?= e($t['registration_number']) ?></td><td><?= e($t['route_name']) ?></td><td><?= e($t['driver_name']) ?></td><td><?= e($t['conductor_name']) ?></td>
<td><?= e(substr($t['departure_time'], 0, 5)) ?></td><td><?= e(substr($t['arrival_time'], 0, 5)) ?></td><td><?= (int)$t['passengers_count'] ?></td><td><?= number_format((float)$t['total_revenue'], 2) ?></td></tr>
<?php endforeach; ?></table>
<?php page_footer();
