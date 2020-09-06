<?php

define('DEBUG', false);
define('AUTOGET', false);
define('DB_HOST', getenv("CCV_HOST"));
define('DB_PORT', getenv("CCV_PORT"));
$user = getenv("CCV_USER");
$pass = getenv("CCV_PASS");
$db = getenv("CCV_DB");
$spd = 4;

?>
