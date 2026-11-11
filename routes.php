<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role(['Administrator', 'SACCO Official']);
$pdo = db(); $errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? 'add';
    if ($action === 'add') {
        $r = add_route($pdo, $_POST);
        if ($r['ok']) { flash('Route added.'); header('Location: routes.php'); exit; }
        $errors = $r['errors'];
    } elseif ($action === 'fare') {
        flash(update_route_fare($pdo, (int)$_POST['route_id'], $_POST['fare'] ?? '') ? 'Fare updated.' : 'Fare must be greater than 0.');
        header('Location: routes.php'); exit;
    } elseif ($action === 'delete' && has_role(['Administrator'])) {
        try { $pdo->prepare('DELETE FROM routes WHERE route_id=?')->execute([(int)$_POST['route_id']]); flash('Route deleted.'); }
        catch (PDOException $ex) { flash('Cannot delete: the route has recorded trips.'); }
        header('Location: routes.php'); exit;
    }
}
$routes = fetch_all($pdo, 'SELECT * FROM routes ORDER BY route_name');
page_header('Route & Fare Management'); show_errors($errors);
?>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="action" value="add">
  <label>Route name (optional)</label><input name="route_name" value="<?= e($_POST['route_name'] ?? '') ?>">
  <label>Starting point</label><input name="starting_point" value="<?= e($_POST['starting_point'] ?? '') ?>" required>
  <label>Destination</label><input name="destination" value="<?= e($_POST['destination'] ?? '') ?>" required>
  <label>Distance (km)</label><input name="distance" type="number" step="0.01" min="0.01" value="<?= e($_POST['distance'] ?? '') ?>" required>
  <label>Passenger fare (KES)</label><input name="fare" type="number" step="0.01" min="0.01" value="<?= e($_POST['fare'] ?? '') ?>" required>
  <button type="submit">Add route</button>
</form>
<table><tr><th>Route</th><th>From</th><th>To</th><th>Km</th><th>Fare (KES)</th><th></th></tr>
<?php foreach ($routes as $r): ?>
<tr><td><?= e($r['route_name']) ?></td><td><?= e($r['starting_point']) ?></td><td><?= e($r['destination']) ?></td><td><?= e($r['distance']) ?></td>
<td><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="fare"><input type="hidden" name="route_id" value="<?= (int)$r['route_id'] ?>">
<input name="fare" type="number" step="0.01" min="0.01" value="<?= e($r['fare']) ?>" style="width:100px"><button type="submit">Edit</button></form></td>
<td><?php if (has_role(['Administrator'])): ?><form method="post" onsubmit="return confirm('Delete this route?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="route_id" value="<?= (int)$r['route_id'] ?>"><button class="danger">Delete</button></form><?php endif; ?></td></tr>
<?php endforeach; ?></table>
<?php page_footer();
