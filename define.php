<?php

define('DEBUG', false);
define('AUTOGET', false);
define('AUTONAME', true);
define('DB_HOST', getenv("CCV_HOST"));
define('DB_PORT', getenv("CCV_PORT"));
define('DB', getenv("CCV_DB"));
define('TB_MEMBER', getenv("CCV_TB_MEMBER"));
define('OWNERCOLOR', 93);
define('FIRSTCOLOR', 96);
define('SPEED', getenv("SPEED") ? (int)getenv("SPEED") : 4);
define('SAVE', getenv("SAVE") ? (bool)getenv("SAVE") : false);
$user = getenv("CCV_USER");
$pass = getenv("CCV_PASS");

?>
