<?php
require_once 'config/config.php';
Auth::logout();
header('Location: /login.php?expired=1');
exit;