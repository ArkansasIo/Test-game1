<?php
// Debugging
define("SYSLOG_DEBUG",0);

// Choose session type, only FILE supported right now.
define("SESSION_TYPE","FILE");
define("SESSION_STORAGE_DIR","/tmp/resMgmtServer");

// Maximum number of saved games.
// Too many games can decrease performance due to session overhead.
define("MAX_SAVED_GAMES", 10);
?>