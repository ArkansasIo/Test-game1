<?php
// Debugging
define("RPC_CLIENT_DEBUG",0);
define("SYSLOG_DEBUG",1);

// Choose session type, only FILE supported right now.
define("SESSION_TYPE","FILE");
define("SESSION_STORAGE_DIR","/tmp/resMgmtClient");

// Server Info
define("SERVER_URL",ereg_replace("client", "server", $_SERVER["SCRIPT_NAME"]));
logmsg("Defining SERVER_URL constant as " . SERVER_URL);

define("SERVER_HOST",$_SERVER["SERVER_NAME"]);
logmsg("Defining SERVER_HOST constant as " . SERVER_HOST);
?>