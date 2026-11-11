<?php
// Unit tests - no database needed.   Run:  php tests/UnitTests.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

$pass = 0; $fail = 0;
function check(string $name, $expected, $actual): void {
    global $pass, $fail;
    if ($expected === $actual) { $pass++; echo "[PASS] $name\n"; }
    else { $fail++; echo "[FAIL] $name  (expected " . var_export($expected, true) . ", got " . var_export($actual, true) . ")\n"; }
}

check('UT01 valid registration number',            true,  validate_reg_number('KDA 123A'));
check('UT02 registration without space is valid',  true,  validate_reg_number('kda123a'));
check('UT03 invalid registration number',          false, validate_reg_number('12345'));
check('UT04 normalise registration number',        'KDA 123A', normalize_reg_number('kda123a'));
check('UT05 valid phone 07',                       true,  validate_phone('0712345678'));
check('UT06 valid phone +254',                     true,  validate_phone('+254712345678'));
check('UT07 invalid phone (too short)',            false, validate_phone('07123'));
check('UT08 valid national ID',                    true,  validate_national_id('23456789'));
check('UT09 invalid national ID (letters)',        false, validate_national_id('ABC1234'));
check('UT10 seating capacity 14 valid',            true,  validate_seats('14'));
check('UT11 seating capacity 0 invalid',           false, validate_seats('0'));
check('UT12 seating capacity 200 invalid',         false, validate_seats('200'));
check('UT13 empty email allowed',                  true,  validate_email_optional(''));
check('UT14 bad email rejected',                   false, validate_email_optional('not-an-email'));
check('UT15 arrival after departure',              true,  validate_time_order('08:00', '09:15'));
check('UT16 arrival before departure rejected',    false, validate_time_order('09:00', '08:00'));
check('UT17 revenue = passengers x fare',          2100.0, calc_revenue(14, 150.00));
check('UT18 revenue for zero passengers',          0.0,   calc_revenue(0, 150.00));
check('UT19 passengers within capacity',           true,  passengers_within_capacity(14, 14));
check('UT20 passengers exceed capacity',           false, passengers_within_capacity(15, 14));
check('UT21 output escaping (XSS)',                '&lt;script&gt;', e('<script>'));
$h = password_hash('Admin@123', PASSWORD_DEFAULT);
check('UT22 password hash is not plain text',      true,  $h !== 'Admin@123');
check('UT23 correct password verifies',            true,  password_verify('Admin@123', $h));
check('UT24 wrong password fails',                 false, password_verify('wrong', $h));

echo "\nResult: $pass passed, $fail failed\n";
exit($fail > 0 ? 1 : 0);
