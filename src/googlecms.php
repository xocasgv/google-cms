<?php
require(dirname(__FILE__).'/StringTools.php');
require(dirname(__FILE__).'/Connection.php');
require(dirname(__FILE__).'/FolderScanner.php');
require(dirname(__FILE__).'/PageDownloader.php');
require(dirname(__FILE__).'/Output.php');

require(dirname(__FILE__).'/config.php');
// $login
// $password
// $rootFolderId
// $templatePath
// $rootPath
// $folderMode
// $fileMode

$privateFilePath = dirname(__FILE__).'/private.php';
@include($privateFilePath);
// $lastUpdateDate
// $authString
// $menuTree

$output = new Output($fileMode, $folderMode);
$connection = new Connection($login, $password, $authString);

$visitingThisFile = (strrchr(__FILE__, '/')) === (strrchr($_SERVER['PHP_SELF'], '/'));
if($visitingThisFile) {
	$output->println('Start');
	date_default_timezone_set('GMT'); // this is the time used in google feeds
	$newUpdateDate = gmdate(substr(DateTime::ATOM, 0, -1), strtotime('-1 hour')); // -1 hour because of the feeds delay. 'Y-m-d\TH:i:s\Z'
	$rootFolderUrl = 'https://docs.google.com/feeds/default/private/full/folder%3A'.$rootFolderId.'/contents';
	$pageDownloader = new PageDownloader($connection, $output, $templatePath);
	$folderScanner = new FolderScanner($connection, $output, $lastUpdateDate, $pageDownloader);
	$menuTree = $folderScanner->scan($rootFolderUrl, $rootPath); // Publish all pages and files
	$newAuthString = $connection->getAuthString();
	$output->println('Done.');
	$privateString = StringTools::serializeForInclude($newUpdateDate, $newAuthString, $menuTree);
	$output->store($privateString, $privateFilePath);
} else { // The file is "included"
	// $docid
	// $etag
	$action = $_SERVER['QUERY_STRING'];
	if($action == 'edit' or $action == 'e') {
		header('Location: https://docs.google.com/document/d/'.$docid.'/edit');
	} else {
		$response = $connection->checkForUpdates('https://docs.google.com/feeds/default/private/full/document%3A'.$docid, $etag);
		$authStringGotUpdated = strcmp($authString, $connection->getAuthString()) != 0;
		if($authStringGotUpdated) {
			$privateString = StringTools::serializeForInclude($lastUpdateDate, $connection->getAuthString(), $menuTree);
			$output->store($privateString, $privateFilePath);
		}
		$pageChanged = strcmp($response, '304') != 0;
		if($pageChanged) {
			libxml_use_internal_errors(true);
			$responseContent = simplexml_load_string(str_replace('gd:etag', 'etag', $response));
			if(0 == count(libxml_get_errors())) {
				$newEtag = $responseContent['etag'];
				$srcUrl = $responseContent->content['src'];
				$pageDownloader = new PageDownloader($connection, $output, $templatePath);
				$pageDownloader->download($srcUrl, $newEtag, substr(strrchr($_SERVER['SCRIPT_NAME'], '/'), 1));
				header('Location: http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']);
			}
		}		
	}
}
$connection->close();
?>