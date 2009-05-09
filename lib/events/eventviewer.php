<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/
 * This script is distributed under the GNU General Public License 2 or later.
 *
 * Filename $RCSfile: eventviewer.php,v $
 *
 * @version $Revision: 1.23 $
 * @modified $Date: 2009/05/09 17:59:19 $ by $Author: schlundus $
 *
 * rev: 20081029 - franciscom - added 'clear' action to delete all events and transactions
 *                              present on database.
**/
require_once("../../config.inc.php");
require_once("common.php");
require_once("users.inc.php");
testlinkInitPage($db,false,false,"checkRights");

$templateCfg = templateConfiguration();

$logLevels = array(
			tlLogger::AUDIT => lang_get("log_level_AUDIT"),
			tlLogger::ERROR => lang_get("log_level_ERROR"),
			tlLogger::WARNING => lang_get("log_level_WARNING"),
			tlLogger::INFO => lang_get("log_level_INFO"),
			tlLogger::DEBUG => lang_get("log_level_DEBUG"),
			);

$args = init_args();
$startTime = null;
$endTime = null;

switch($args->doAction)
{
    case 'clear':
	    $g_tlLogger->deleteEventsFor();
	    logAuditEvent(TLS("audit_events_deleted",$_SESSION['currentUser']->login),"DELETE",null,"events");
	    break;
    
    case 'filter':
	default:
	    if ($args->startDate != "")
	    {
	    	$startTime = strToTime($args->startDate);
	    	if (!$startTime)
	    		$startTime = null;
	    }
	    if ($args->endDate != "")
	    {
	    	$endTime = strToTime($args->endDate) + (24*60*60-1);
	    	if (!$endTime)
	    		$endTime = null;
	    }
    	break;
}

$events = $g_tlLogger->getEventsFor($args->logLevel,$args->object_id ? $args->object_id : null,
									$args->object_type ? $args->object_type : null,null,500,$startTime,$endTime);
$users = getUsersForHtmlOptions($db);
$users[0] = false;

$gui = new stdClass();
$gui->canDelete = has_rights($db,"events_mgt") ? 1: 0;

$smarty = new TLSmarty();
$smarty->assign('gui',$gui);
$smarty->assign('events',$events);
$smarty->assign('users',$users);
$smarty->assign('logLevels',$logLevels);
$smarty->assign('object_id',$args->object_id);
$smarty->assign('object_type',$args->object_type);
$smarty->assign('selectedLogLevels',$args->logLevel ? array_values($args->logLevel) : array());
$smarty->assign('startDate',$args->startDate);
$smarty->assign('endDate',$args->endDate);
$smarty->display($templateCfg->template_dir . $templateCfg->default_template);

function init_args()
{
	$iParams = array(
			"startDate" => array("POST",tlInputParameter::STRING_N,0,10),
			"endDate" => array("POST",tlInputParameter::STRING_N,0,10),
			"doAction" => array("POST",tlInputParameter::STRING_N,0,100),
			"object_id" => array("REQUEST",tlInputParameter::INT_N),
			"object_type" => array("REQUEST",tlInputParameter::STRING_N),
			"logLevel" => array("POST",tlInputParameter::ARRAY_INT),
		);

	$args = new stdClass();
	$pParams = I_PARAMS($iParams.$args);

	return $args;
}

function checkRights(&$db,&$user)
{
	$action = isset($_REQUEST['doAction']) ? $_REQUEST['doAction'] : null;
	
	if (!$user->hasRight($db,"mgt_view_events"))
		return false;
	if ($action == 'clear')
		return $user->hasRight($db,'events_mgt');
	
	return true;
}
?>