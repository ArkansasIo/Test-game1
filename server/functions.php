<?php
function logmsg($msg) {
	if(SYSLOG_DEBUG == 1) {
		$prefix = session_id() ? session_id() : "NO SESSION";
		syslog(LOG_DEBUG,  $prefix . ": " . $msg);
	}
}
?>