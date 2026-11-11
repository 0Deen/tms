<?php
// Run ONCE in the browser after importing schema.sql: creates the default accounts. Delete this file afterwards.
require_once __DIR__ . '/includes/bootstrap.php';
$pdo = db();
$accounts = [
    ['System Administrator', 'admin',     'Admin@123',    'Administrator'],
    ['SACCO Official',       'official',  'Official@123', 'SACCO Official'],
    ['Trip Conductor',       'conductor', 'Conductor@123','Conductor'],
];
$st = $pdo->prepare('INSERT IGNORE INTO users (full_name,username,password,role) VALUES (?,?,?,?)');
foreach ($accounts as [$n, $u, $p, $r]) { $st->execute([$n, $u, password_hash($p, PASSWORD_DEFAULT), $r]); }
echo 'Default accounts created (admin / Admin@123, official / Official@123, conductor / Conductor@123). Delete install.php now.';
