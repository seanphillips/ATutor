<?php
/************************************************************************/
/* ATutor																*/
/************************************************************************/
/* Copyright (c) 2002-2010                                              */
/* Inclusive Design Institute                                           */
/* http://atutor.ca                                                     */
/* This program is free software. You can redistribute it and/or        */
/* modify it under the terms of the GNU General Public License          */
/* as published by the Free Software Foundation.                        */
/************************************************************************/
// $Id$

define('AT_INCLUDE_PATH', '../../../include/');

$cid = intval($_POST['cid']);

$rows_content = $contentManager->getContentPage($cid);

foreach($rows_content as $content_row){
    if(!isset($content_row['content_id'])){
        $msg->printErrors('PAGE_NOT_FOUND');
    }
}
$course_base_href = '';
$content_base_href = '';
$_config['achecker_url'] = rtrim($_config['achecker_url'], '/') . '/';
define('AT_ACHECKER_URL', $_config['achecker_url']);
define('AT_ACHECKER_WEB_SERVICE_ID', $_config['achecker_key']);
//make decisions
if ($_POST['make_decision']) 
{
	//get list of decisions	
	$desc_query = '';
	if (is_array($_POST['d'])) {
		foreach ($_POST['d'] as $sequenceID => $decision) {
			$desc_query .= '&'.$sequenceID.'='.$decision;
		}
	}

	$checker_url = AT_ACHECKER_URL. 'decisions.php?'
				.'uri='.urlencode($_POST['pg_url']).'&id='.AT_ACHECKER_WEB_SERVICE_ID
				.'&session='.$_POST['sessionid'].'&output=html'.$desc_query;

	if (@file_get_contents($checker_url) === false) {
		$msg->addInfo('DECISION_NOT_SAVED');
	}
} 
else if (isset($_POST['reverse'])) 
{
	$reverse_url = AT_ACHECKER_URL. 'decisions.php?'
				.'uri='.urlencode($_POST['pg_url']).'&id='.AT_ACHECKER_WEB_SERVICE_ID
				.'&session='.$_POST['sessionid'].'&output=html&reverse=true&'.key($_POST['reverse']).'=N';
	
	if (@file_get_contents($reverse_url) === false) {
		$msg->addInfo('DECISION_NOT_REVERSED');
	} else {
		$msg->addInfo('DECISION_REVERSED');
	}
}

?>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>?popup=1" method="post" name="form">
  <div class="row">
<?php 					
	echo '    <input type="hidden" name="body_text" value="'.htmlspecialchars(stripslashes($_POST['body_text'])).'" />';
	echo '    <input type="hidden" name="cid" value="'.$_POST['cid'].'" />';
	
	if (!$cid) {
		$msg->printInfos('SAVE_CONTENT');

		echo '  </div>';

		return;
	}

$msg->printInfos();
if ($_POST['body_text'] != '') {
	//save temp file
	$_POST['content_path'] = $content_row['content_path'];
	write_temp_file();

	$pg_url = AT_BASE_HREF.'get_acheck.php/'.$_POST['cid'] . '.html';
	$checker_url = AT_ACHECKER_URL.'checkacc.php?uri='.urlencode($pg_url).'&id='.AT_ACHECKER_WEB_SERVICE_ID
					. '&guide=WCAG2-L2&output=html';

	$report = @file_get_contents($checker_url);

	if (stristr($report, '<div id="error">')) {
		$msg->printErrors('INVALID_URL');
	} else if ($report === false) {
		$msg->printInfos('SERVICE_UNAVAILABLE');
	} else {
		echo '    <input type="hidden" name="pg_url" value="'.$pg_url.'" />';
		echo $report;	

		echo '    <p>'._AT('access_credit').'</p>';
	}
	//delete file
	@unlink(AT_CONTENT_DIR . $_POST['cid'] . '.html');

} else {
	$msg->printInfos('NO_PAGE_CONTENT');
} 
?>
  </div>
</form>
