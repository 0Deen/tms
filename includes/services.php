<?php
// Business logic for each module. Pages call these; tests call these too.
function fetch_one(PDO $pdo, string $sql, array $p = []) { $s = $pdo->prepare($sql); $s->execute($p); return $s->fetch(); }
function fetch_all(PDO $pdo, string $sql, array $p = []): array { $s = $pdo->prepare($sql); $s->execute($p); return $s->fetchAll(); }
function dup_or_throw(PDOException $ex, string $message): array {
    if ($ex->getCode() === '23000') { return ['ok' => false, 'errors' => [$message]]; }
    throw $ex;
}

/* ---------- Vehicle module ---------- */
function add_vehicle(PDO $pdo, array $d): array {
    $err = [];
    $reg = normalize_reg_number($d['registration_number'] ?? '');
    $model = trim($d['vehicle_model'] ?? '');
    $owner = trim($d['owner_name'] ?? '');
    $status = $d['status'] ?? 'Active';
    if (!validate_reg_number($reg)) $err[] = 'Registration number must look like KDA 123A.';
    if ($model === '') $err[] = 'Vehicle model is required.';
    if (!validate_seats($d['seating_capacity'] ?? '')) $err[] = 'Seating capacity must be a number from 1 to 70.';
    if ($owner === '') $err[] = "Owner's name is required.";
    if (!in_array($status, ['Active', 'Inactive', 'Maintenance'], true)) $err[] = 'Invalid status.';
    if ($err) return ['ok' => false, 'errors' => $err];
    try {
        $pdo->prepare('INSERT INTO vehicles (registration_number,vehicle_model,seating_capacity,owner_name,status,registration_date) VALUES (?,?,?,?,?,CURDATE())')
            ->execute([$reg, $model, (int)$d['seating_capacity'], $owner, $status]);
        return ['ok' => true, 'id' => (int)$pdo->lastInsertId()];
    } catch (PDOException $ex) { return dup_or_throw($ex, 'A vehicle with that registration number already exists.'); }
}
function update_vehicle_status(PDO $pdo, int $id, string $status): bool {
    if (!in_array($status, ['Active', 'Inactive', 'Maintenance'], true)) return false;
    return $pdo->prepare('UPDATE vehicles SET status=? WHERE vehicle_id=?')->execute([$status, $id]);
}

/* ---------- Staff module ---------- */
function add_staff(PDO $pdo, array $d): array {
    $err = [];
    $name = trim($d['full_name'] ?? ''); $nid = trim($d['national_id'] ?? '');
    $phone = trim($d['phone'] ?? ''); $role = $d['staff_role'] ?? ''; $lic = trim($d['license_number'] ?? '');
    if ($name === '') $err[] = 'Full name is required.';
    if (!validate_national_id($nid)) $err[] = 'National ID must be 7 or 8 digits.';
    if (!validate_phone($phone)) $err[] = 'Phone must look like 0712345678.';
    if (!in_array($role, ['Driver', 'Conductor'], true)) $err[] = 'Role must be Driver or Conductor.';
    if ($role === 'Driver' && $lic === '') $err[] = 'Drivers must have a licence number.';
    if ($err) return ['ok' => false, 'errors' => $err];
    try {
        $pdo->prepare('INSERT INTO staff (full_name,national_id,phone,staff_role,license_number,status) VALUES (?,?,?,?,?,"Active")')
            ->execute([$name, $nid, $phone, $role, $lic === '' ? null : $lic]);
        return ['ok' => true, 'id' => (int)$pdo->lastInsertId()];
    } catch (PDOException $ex) { return dup_or_throw($ex, 'A staff member with that national ID already exists.'); }
}

/* ---------- Route module ---------- */
function add_route(PDO $pdo, array $d): array {
    $err = [];
    $from = trim($d['starting_point'] ?? ''); $to = trim($d['destination'] ?? '');
    $dist = $d['distance'] ?? ''; $fare = $d['fare'] ?? '';
    if ($from === '' || $to === '') $err[] = 'Starting point and destination are required.';
    if (strcasecmp($from, $to) === 0 && $from !== '') $err[] = 'Starting point and destination must differ.';
    if (!is_numeric($dist) || (float)$dist <= 0) $err[] = 'Distance must be greater than 0.';
    if (!is_numeric($fare) || (float)$fare <= 0) $err[] = 'Fare must be greater than 0.';
    if ($err) return ['ok' => false, 'errors' => $err];
    $name = trim($d['route_name'] ?? '') ?: ($from . ' - ' . $to);
    $pdo->prepare('INSERT INTO routes (route_name,starting_point,destination,distance,fare) VALUES (?,?,?,?,?)')
        ->execute([$name, $from, $to, (float)$dist, (float)$fare]);
    return ['ok' => true, 'id' => (int)$pdo->lastInsertId()];
}
function update_route_fare(PDO $pdo, int $id, $fare): bool {
    if (!is_numeric($fare) || (float)$fare <= 0) return false;
    return $pdo->prepare('UPDATE routes SET fare=? WHERE route_id=?')->execute([(float)$fare, $id]);
}

/* ---------- Trip module (uses vehicles + routes + staff) ---------- */
function record_trip(PDO $pdo, array $d): array {
    $err = [];
    $v = fetch_one($pdo, 'SELECT * FROM vehicles WHERE vehicle_id=?', [(int)($d['vehicle_id'] ?? 0)]);
    $r = fetch_one($pdo, 'SELECT * FROM routes WHERE route_id=?', [(int)($d['route_id'] ?? 0)]);
    $drv = fetch_one($pdo, 'SELECT * FROM staff WHERE staff_id=?', [(int)($d['driver_id'] ?? 0)]);
    $con = fetch_one($pdo, 'SELECT * FROM staff WHERE staff_id=?', [(int)($d['conductor_id'] ?? 0)]);
    $date = $d['trip_date'] ?? ''; $dep = $d['departure_time'] ?? ''; $arr = $d['arrival_time'] ?? '';
    $cnt = $d['passengers_count'] ?? '';
    if (!$v) $err[] = 'Select a valid vehicle.';
    elseif ($v['status'] !== 'Active') $err[] = 'Only Active vehicles can be assigned to trips.';
    if (!$r) $err[] = 'Select a valid route.';
    if (!$drv || $drv['staff_role'] !== 'Driver' || $drv['status'] !== 'Active') $err[] = 'Select an active driver.';
    if (!$con || $con['staff_role'] !== 'Conductor' || $con['status'] !== 'Active') $err[] = 'Select an active conductor.';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $err[] = 'Trip date is required.';
    $validTimes = preg_match('/^\d{2}:\d{2}$/', $dep) && preg_match('/^\d{2}:\d{2}$/', $arr);
    if (!$validTimes) $err[] = 'Enter valid departure and arrival times.';
    elseif ($dep === $arr) $err[] = 'Departure and arrival times cannot be identical.';
    if (filter_var($cnt, FILTER_VALIDATE_INT) === false || (int)$cnt < 0) $err[] = 'Passenger count must be zero or more.';
    elseif ($v && !passengers_within_capacity((int)$cnt, (int)$v['seating_capacity']))
        $err[] = 'Passengers exceed the vehicle seating capacity (' . $v['seating_capacity'] . ').';
    if ($err) return ['ok' => false, 'errors' => $err];
    $start = strtotime($date . ' ' . $dep);
    $end = strtotime($date . ' ' . $arr);
    if ($end <= $start) $end = strtotime('+1 day', $end);
    $existing = fetch_all($pdo, 'SELECT trip_date,departure_time,arrival_time FROM trips WHERE vehicle_id=? AND trip_date BETWEEN DATE_SUB(?, INTERVAL 1 DAY) AND DATE_ADD(?, INTERVAL 1 DAY)', [$v['vehicle_id'],$date,$date]);
    foreach ($existing as $ex) {
        $es = strtotime($ex['trip_date'].' '.$ex['departure_time']);
        $ee = strtotime($ex['trip_date'].' '.$ex['arrival_time']);
        if ($ee <= $es) $ee = strtotime('+1 day',$ee);
        if ($start < $ee && $end > $es) return ['ok'=>false,'errors'=>['This vehicle already has a trip overlapping the selected time.']];
    }
    $revenue = calc_revenue((int)$cnt, (float)$r['fare']);
    $pdo->prepare('INSERT INTO trips (vehicle_id,route_id,driver_id,conductor_id,trip_date,departure_time,arrival_time,passengers_count,total_revenue,fare_at_time) VALUES (?,?,?,?,?,?,?,?,?,?)')
        ->execute([$v['vehicle_id'], $r['route_id'], $drv['staff_id'], $con['staff_id'], $date, $dep, $arr, (int)$cnt, $revenue, (float)$r['fare']]);
    audit_log($pdo, 'trip.created', 'trip', (int)$pdo->lastInsertId(), 'Trip recorded; revenue '.money_kes($revenue).'.');
    return ['ok' => true, 'id' => (int)$pdo->lastInsertId(), 'revenue' => $revenue];
}
function list_trips(PDO $pdo, int $limit = 50): array {
    return fetch_all($pdo, 'SELECT t.*, v.registration_number, r.route_name, d.full_name AS driver_name, c.full_name AS conductor_name
        FROM trips t JOIN vehicles v ON v.vehicle_id=t.vehicle_id JOIN routes r ON r.route_id=t.route_id
        JOIN staff d ON d.staff_id=t.driver_id JOIN staff c ON c.staff_id=t.conductor_id
        ORDER BY t.trip_date DESC, t.departure_time DESC LIMIT ' . (int)$limit);
}

/* ---------- Passenger module ---------- */
function add_passenger(PDO $pdo, array $d): array {
    $err = []; $name = trim($d['full_name'] ?? ''); $phone = trim($d['phone_number'] ?? ''); $email = trim($d['email'] ?? '');
    if ($name === '') $err[] = 'Passenger name is required.';
    if (!validate_phone($phone)) $err[] = 'Phone must look like 0712345678.';
    if (!validate_email_optional($email)) $err[] = 'Email address is not valid.';
    if ($err) return ['ok' => false, 'errors' => $err];
    $pdo->prepare('INSERT INTO passengers (full_name,phone_number,email) VALUES (?,?,?)')->execute([$name, $phone, $email === '' ? null : $email]);
    return ['ok' => true, 'id' => (int)$pdo->lastInsertId()];
}
function search_passengers(PDO $pdo, string $name = '', string $phone = ''): array {
    $name = trim($name); $phone = trim($phone);
    if ($name === '' && $phone === '') return [];
    $sql = 'SELECT * FROM passengers WHERE 1=1'; $p = [];
    if ($name !== '')  { $sql .= ' AND full_name LIKE ?';    $p[] = '%' . $name . '%'; }
    if ($phone !== '') { $sql .= ' AND phone_number LIKE ?'; $p[] = '%' . $phone . '%'; }
    return fetch_all($pdo, $sql . ' ORDER BY full_name', $p);
}

/* ---------- Complaint module (uses passengers + trips) ---------- */
function add_complaint(PDO $pdo, array $d): array {
    $err = [];
    $desc = trim($d['complaint_description'] ?? '');
    if (!fetch_one($pdo, 'SELECT 1 FROM passengers WHERE passenger_id=?', [(int)($d['passenger_id'] ?? 0)])) $err[] = 'Select a valid passenger.';
    if (!fetch_one($pdo, 'SELECT 1 FROM trips WHERE trip_id=?', [(int)($d['trip_id'] ?? 0)])) $err[] = 'Select a valid trip.';
    if (strlen($desc) < 10) $err[] = 'Describe the complaint in at least 10 characters.';
    if ($err) return ['ok' => false, 'errors' => $err];
    $pdo->prepare('INSERT INTO complaints (passenger_id,trip_id,complaint_description,status) VALUES (?,?,?,"Open")')
        ->execute([(int)$d['passenger_id'], (int)$d['trip_id'], $desc]);
    return ['ok' => true, 'id' => (int)$pdo->lastInsertId()];
}
function update_complaint_status(PDO $pdo, int $id, string $status): bool {
    if (!in_array($status, ['Open', 'In Progress', 'Resolved'], true)) return false;
    return $pdo->prepare('UPDATE complaints SET status=? WHERE complaint_id=?')->execute([$status, $id]);
}

/* ---------- Reports ---------- */
function revenue_summary(PDO $pdo): array {
    return fetch_one($pdo, 'SELECT COUNT(*) AS trips, COALESCE(SUM(passengers_count),0) AS passengers, COALESCE(SUM(total_revenue),0) AS revenue FROM trips');
}
