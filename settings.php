<?php
// Forward settings.php to unified profile.php
require_once 'db.php';
requireAuth();
header("Location: profile.php");
exit();
