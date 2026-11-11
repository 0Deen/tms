<?php
// Database settings (XAMPP defaults). Environment variables override them for testing.
define('DB_HOST', getenv('TMS_DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('TMS_DB_NAME') ?: 'tms_db');
define('DB_USER', getenv('TMS_DB_USER') ?: 'root');
define('DB_PASS', getenv('TMS_DB_PASS') !== false ? getenv('TMS_DB_PASS') : '');
define('APP_NAME', 'Online Transport Management System');
define('SESSION_TIMEOUT', 1800); // 30 minutes of inactivity
