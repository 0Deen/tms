<?php
// Integration tests - need MySQL with schema.sql imported.   Run:  php tests/IntegrationTests.php
// Everything runs inside a transaction that is rolled back, so real data is never changed.
require_once __DIR__ . '/../includes/bootstrap.php';

$pass = 0; $fail = 0;
function check(string $name, $expected, $actual): void {
    global $pass, $fail;
    if ($expected === $actual) { $pass++; echo "[PASS] $name\n"; }
    else { $fail++; echo "[FAIL] $name  (expected " . var_export($expected, true) . ", got " . var_export($actual, true) . ")\n"; }
}

$pdo = db();
$pdo->beginTransaction();
try {
    // IT01-IT04: each module saves data
    $v = add_vehicle($pdo, ['registration_number' => 'KZZ 999Z', 'vehicle_model' => 'Test Van', 'seating_capacity' => 14, 'owner_name' => 'Test Owner', 'status' => 'Active']);
    check('IT01 vehicle registered', true, $v['ok']);
    $dup = add_vehicle($pdo, ['registration_number' => 'kzz999z', 'vehicle_model' => 'Test Van', 'seating_capacity' => 14, 'owner_name' => 'Test Owner']);
    check('IT02 duplicate registration rejected', false, $dup['ok']);

    $d = add_staff($pdo, ['full_name' => 'Test Driver', 'national_id' => '90000001', 'phone' => '0700000001', 'staff_role' => 'Driver', 'license_number' => 'DL-1']);
    $c = add_staff($pdo, ['full_name' => 'Test Conductor', 'national_id' => '90000002', 'phone' => '0700000002', 'staff_role' => 'Conductor']);
    check('IT03 driver and conductor registered', true, $d['ok'] && $c['ok']);

    $r = add_route($pdo, ['starting_point' => 'Nairobi', 'destination' => 'Kitengela', 'distance' => '35', 'fare' => '150']);
    check('IT04 route added with fare', true, $r['ok']);
    check('IT05 route fare can be updated', true, update_route_fare($pdo, $r['id'], 200));

    // IT06-IT09: trip module uses vehicle + route + staff
    $trip = record_trip($pdo, ['vehicle_id' => $v['id'], 'route_id' => $r['id'], 'driver_id' => $d['id'], 'conductor_id' => $c['id'],
        'trip_date' => date('Y-m-d'), 'departure_time' => '08:00', 'arrival_time' => '09:10', 'passengers_count' => 10]);
    check('IT06 trip recorded using data from 3 modules', true, $trip['ok']);
    check('IT07 revenue = 10 x updated fare 200', 2000.0, $trip['revenue']);
    $bad = record_trip($pdo, ['vehicle_id' => $v['id'], 'route_id' => $r['id'], 'driver_id' => $d['id'], 'conductor_id' => $c['id'],
        'trip_date' => date('Y-m-d'), 'departure_time' => '08:00', 'arrival_time' => '09:10', 'passengers_count' => 40]);
    check('IT08 overloaded trip rejected', false, $bad['ok']);
    $swap = record_trip($pdo, ['vehicle_id' => $v['id'], 'route_id' => $r['id'], 'driver_id' => $c['id'], 'conductor_id' => $d['id'],
        'trip_date' => date('Y-m-d'), 'departure_time' => '08:00', 'arrival_time' => '09:10', 'passengers_count' => 5]);
    check('IT09 conductor cannot be used as driver', false, $swap['ok']);

    update_vehicle_status($pdo, $v['id'], 'Maintenance');
    $inactive = record_trip($pdo, ['vehicle_id' => $v['id'], 'route_id' => $r['id'], 'driver_id' => $d['id'], 'conductor_id' => $c['id'],
        'trip_date' => date('Y-m-d'), 'departure_time' => '10:00', 'arrival_time' => '11:00', 'passengers_count' => 5]);
    check('IT10 vehicle in maintenance cannot take trips', false, $inactive['ok']);

    // IT11-IT14: passenger + complaint modules link to trip
    $p = add_passenger($pdo, ['full_name' => 'Zed Tester', 'phone_number' => '0799887766', 'email' => '']);
    check('IT11 passenger saved', true, $p['ok']);
    check('IT12 search by name finds passenger', true, count(search_passengers($pdo, 'Zed Tes', '')) >= 1);
    check('IT13 search by phone finds passenger', true, count(search_passengers($pdo, '', '0799887766')) >= 1);
    check('IT14 empty search returns nothing', 0, count(search_passengers($pdo, '', '')));

    $cm = add_complaint($pdo, ['passenger_id' => $p['id'], 'trip_id' => $trip['id'], 'complaint_description' => 'Vehicle was overcharging fare.']);
    check('IT15 complaint linked to passenger and trip', true, $cm['ok']);
    check('IT16 complaint status can be updated', true, update_complaint_status($pdo, $cm['id'], 'Resolved'));
    $bc = add_complaint($pdo, ['passenger_id' => 0, 'trip_id' => $trip['id'], 'complaint_description' => 'Valid description here.']);
    check('IT17 complaint with unknown passenger rejected', false, $bc['ok']);

    // IT18: joined query / report across modules
    $row = fetch_one($pdo, 'SELECT t.total_revenue, v.registration_number, r.route_name, d.full_name AS drv
        FROM trips t JOIN vehicles v ON v.vehicle_id=t.vehicle_id JOIN routes r ON r.route_id=t.route_id JOIN staff d ON d.staff_id=t.driver_id WHERE t.trip_id=?', [$trip['id']]);
    check('IT18 trip report joins vehicle, route and driver', 'KZZ 999Z', $row['registration_number']);
    check('IT19 revenue summary includes the new trip', true, (float)revenue_summary($pdo)['revenue'] >= 2000.0);

    // IT20: database integrity
    $blocked = false;
    try { $pdo->exec('DELETE FROM vehicles WHERE vehicle_id=' . (int)$v['id']); } catch (PDOException $ex) { $blocked = true; }
    check('IT20 foreign key blocks deleting a vehicle that has trips', true, $blocked);

    // IT21-IT23: authentication
    $pdo->prepare('INSERT INTO users (full_name,username,password,role) VALUES (?,?,?,?)')
        ->execute(['Test Admin', 'tms_test_user', password_hash('Secret#123', PASSWORD_DEFAULT), 'Administrator']);
    check('IT21 login succeeds with correct password', true, login($pdo, 'tms_test_user', 'Secret#123'));
    check('IT22 session holds the user role', 'Administrator', $_SESSION['user']['role'] ?? null);
    check('IT23 login fails with wrong password', false, login($pdo, 'tms_test_user', 'nope'));
} finally {
    $pdo->rollBack();
}
echo "\nResult: $pass passed, $fail failed (all test data rolled back)\n";
exit($fail > 0 ? 1 : 0);
