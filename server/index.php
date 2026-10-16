<?php
require_once("XML/RPC/Server.php");
require_once("functions.php");
require_once("config.php");

require_once("class_cg_Resource.php");
require_once("class_cg_ResearchProject.php");
require_once("class_cg_Structure.php");
require_once("class_cg_Event.php");

// Startup Stuff
ob_start("ob_gzhandler");
srand((double) microtime() * 1000000);

// Configure Session Options
if(SESSION_TYPE == "FILE") {
	ini_set("session.use_cookies","0");
	ini_set("session.save_handler","files");
	if(!is_dir(SESSION_STORAGE_DIR)) {
		logmsg("Server session sorage directory (" . SESSION_STORAGE_DIR . ") does not exist, attempting to create.");
		if(!mkdir(SESSION_STORAGE_DIR)) {
			logmsg("Server session storage directory creation failed.");
		}
	}
	ini_set("session.save_path",SESSION_STORAGE_DIR);
	ini_set("session.cookie_lifetime", 60 * 60 * 24 * 365 * 20);
}

// Class Defs Here, Before Session Start
include_once("class_cg_GameData.php");

session_start();

// This function sets up the session and returns the remote session ID to the client.
function createSession($params) {
	$param = $params->getParam(0);
	if(!session_is_registered("SESSION_ID")) {
		$_SESSION["SESSION_ID"] = session_id();
		$_SESSION["CLIENT_URL"] = $param->scalarval();
	}
	
	$out = new XML_RPC_Value($_SESSION["SESSION_ID"],"string");
	return new XML_RPC_Response($out);
}

function getGameInfo($params) {
	$out = $_SESSION["GameData"]->getGameInfo();
	return new XML_RPC_Response( XML_RPC_encode( $out ) );
}

function getResourceGraphData($params) {
	$param = $params->getParam(0);
	$resource = $param->scalarval();
	
	$data["values"] = $_SESSION["GameData"]->resources[$resource]->values;
	$data["highestValue"] = $_SESSION["GameData"]->resources[$resource]->highestValue;
	$data["turns"] = $_SESSION["GameData"]->turns;
	
	return new XML_RPC_Response( XML_RPC_encode( $data ) );
}

function getStructureInfo($params) {
	$out = $_SESSION["GameData"]->getStructureInfo();
	return new XML_RPC_Response( XML_RPC_encode( $out ) );
}

function getResearchProjectsInfo($params) {
	$out = $_SESSION["GameData"]->getResearchProjectsInfo();
	return new XML_RPC_Response( XML_RPC_encode( $out ) );
}

function getInstructions($params) {
	$out = $_SESSION["GameData"]->getInstructions();
	return new XML_RPC_Response( XML_RPC_encode( $out ) );
}

function setTheme($params) {
	$param = $params->getParam(0);
	$_SESSION["GameData"] = new cg_GameData($param->scalarval());
	return new XML_RPC_Response( XML_RPC_encode( "setTheme(" . $param->scalarval() . ")" ) );
}

function takeTurn($params) {
	$_SESSION["GameData"]->everyTurn();
	return new XML_RPC_Response( XML_RPC_encode( "takeTurn" ) );
}

function addToBuildQueue($params) {
	$structure = $params->getParam(0);
	$amount = $params->getParam(1);
	$_SESSION["GameData"]->constructionQueue[] = array($structure->scalarval()=>$amount->scalarval());
	$_SESSION["GameData"]->msg($amount->scalarval() . " " .  $structure->scalarval() . " added to the construction queue for next turn.");
	$_SESSION["GameData"]->displayQueues();
	return new XML_RPC_Response( XML_RPC_encode( "addToBuildQueue" ) );
}

function addToResearchQueue($params) {
	$project = $params->getParam(0);
	$_SESSION["GameData"]->researchQueue[] = $project->scalarval();
	$_SESSION["GameData"]->msg($project->scalarval() . " added to the research queue for next turn.");
	$_SESSION["GameData"]->displayQueues();
	return new XML_RPC_Response( XML_RPC_encode( "addToResearchQueue" ) );
}

function addToDismantleQueue($params) {
	$structure = $params->getParam(0);
	$amount = $params->getParam(1);
	$_SESSION["GameData"]->dismantleQueue[] = array($structure->scalarval()=>$amount->scalarval());
	$_SESSION["GameData"]->msg($amount->scalarval() . " " .  $structure->scalarval() . " added to the dismantling queue for next turn.");
	$_SESSION["GameData"]->displayQueues();
	return new XML_RPC_Response( XML_RPC_encode( "addToDismantleQueue" ) );
}

function saveGame($params) {
	$label = $params->getParam(0);
	if(!empty($_SESSION["savedGameData"][$label->scalarval()])) {
		$_SESSION["GameData"]->loadLastTurnDisplay();
		$_SESSION["GameData"]->msg("A saved game with that name already exists.");
		return new XML_RPC_Response( XML_RPC_encode( FALSE ) );
	}

	if(count($_SESSION["savedGameData"]) < MAX_SAVED_GAMES) {
		$_SESSION["savedGameData"][$label->scalarval()] = serialize($_SESSION["GameData"]);
		$_SESSION["GameData"]->loadLastTurnDisplay();
		$_SESSION["GameData"]->msg("Game saved.");
		return new XML_RPC_Response( XML_RPC_encode( TRUE ) );
	}
	else {
		$_SESSION["GameData"]->loadLastTurnDisplay();
		$_SESSION["GameData"]->msg("No more save slots available.");
		return new XML_RPC_Response( XML_RPC_encode( FALSE ) );
	}
}

function loadGame($params) {
	$label = $params->getParam(0);
	if(!empty($_SESSION["savedGameData"][$label->scalarval()])) {
		$_SESSION["GameData"] = unserialize($_SESSION["savedGameData"][$label->scalarval()]);
		$_SESSION["GameData"]->loadLastTurnDisplay();
		return new XML_RPC_Response( XML_RPC_encode( TRUE ) );
	}
	else {
		return new XML_RPC_Response( XML_RPC_encode( FALSE ) );
	}
}

function deleteGame($params) {
	$label = $params->getParam(0);
	unset($_SESSION["savedGameData"][$label->scalarval()]);
	return new XML_RPC_Response( XML_RPC_encode( TRUE ) );
}

function getAvailableThemes($params) {
	$themes = array();
	$dir = opendir("./themes/");
	while($filename = readdir($dir)) {
		if(is_dir("./themes/" . $filename) && $filename != "." && $filename != ".." && $filename != ".svn") {
			$themes[] = $filename;
		}
	}
	closedir($dir);
	return new XML_RPC_Response( XML_RPC_encode( $themes ) );
}

// Create XML-RPC Server 
$server = new XML_RPC_Server(
		array(
			"createSession" => array("function"=>"createSession"),
			"getAvailableThemes" => array("function"=>"getAvailableThemes"),
			"setTheme" => array("function"=>"setTheme"),
			"getGameInfo" => array("function"=>"getGameInfo"),
			"getStructureInfo" => array("function"=>"getStructureInfo"),
			"getResearchProjectsInfo" => array("function"=>"getResearchProjectsInfo"),
			"getInstructions" => array("function"=>"getInstructions"),
			"takeTurn" => array("function"=>"takeTurn"),
			"addToBuildQueue" => array("function"=>"addToBuildQueue"),
			"addToDismantleQueue" => array("function"=>"addToDismantleQueue"),
			"addToResearchQueue" => array("function"=>"addToResearchQueue"),
			"getResourceGraphData" => array("function"=>"getResourceGraphData"),
			"saveGame" => array("function"=>"saveGame"),
			"loadGame" => array("function"=>"loadGame"),
			"deleteGame" => array("function"=>"deleteGame")
		)
	);
?>