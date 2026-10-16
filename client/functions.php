<?php
function arrayToXML($data,$name,$level=0) {
	if($level == 0) {
		$xml = "<?xml version=\"1.0\"?>\n";
		$xml .= "<$name>\n";
	}
	else {
		$xml = "\n";
	}

	while(list($key, $value) = each($data)) {
		$id = "";
		if(is_int($key)) {
			$id = " id=\"$key\"";
			$key = $name . "-item";
		}
		$xml .= str_repeat("\t",$level) . "<$key$id>";
		if(is_array($value)) {
			$xml .= arrayToXML($value,$key,$level+1);
			$xml .= str_repeat("\t",$level);
		}
		else {
			$xml .= $value;
		}
		$xml .= "</$key>\n";
	}

	if($level == 0) {
		$xml .= "</$name>\n";
	}

	return $xml;
}

function logmsg($msg) {
	if(SYSLOG_DEBUG == 1) {
		$prefix = session_id() ? session_id() : "NO SESSION";
		syslog(LOG_DEBUG,  $prefix . ": " . $msg);
	}
}
?>