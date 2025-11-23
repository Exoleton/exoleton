<?php
require __DIR__ . '/auth.php';

logout_user();
header('Location: index.php');
exit;
