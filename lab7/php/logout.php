<?php
require_once __DIR__ . '/../includes/auth.php';

logoutSession();

header('Location: login.php?logged_out=1', true, 303);
exit;

