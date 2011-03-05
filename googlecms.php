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
// $headerPath
// $footerPath
// $rootPath
// $folderMode
// $fileMode

@include(dirname(__FILE__).'/private.php');
// $lastUpdateDate
// $authString
// $menuTree

$output = new Output($fileMode, $folderMode);
$connection = new Connection($login, $password, $authString);

$visitingThisFile = (strrchr(__FILE__, '/')) === (strrchr($_SERVER['PHP_SELF'], '/'));
if($visitingThisFile) {
	$output->sout('Start');
	date_default_timezone_set('GMT'); // this is the time used in google feeds
	$newUpdateDate = gmdate(substr(DateTime::ATOM, 0, -1), strtotime('-1 hour')); // -1 hour because of the feeds delay. 'Y-m-d\TH:i:s\Z'
	$rootFolderUrl = 'https://docs.google.com/feeds/default/private/full/folder%3A'.$rootFolderId.'/contents';
	$pageDownloader = new PageDownloader($connection, $output, $headerPath, $footerPath);
	$folderScanner = new FolderScanner($connection, $output, $lastUpdateDate, $pageDownloader);
	$menuTree = $folderScanner->scan($rootFolderUrl, $rootPath); // Publish all pages and files
	$newAuthString = $connection->getAuthString();
	$output->sout('Done.');
	$privateString = StringTools::serializeForInclude($newUpdateDate, $newAuthString, $menuTree);
	$output->store($privateString, dirname(__FILE__).'/private.php');
} else { // The file is "included"
	// $docid
	// $etag
	$action = $_SERVER['QUERY_STRING'];
	if($action == 'edit' or $action == 'e') {
		header('Location: https://docs.google.com/document/d/'.$docid.'/edit');
		$connection->close();
		die();
	}
	
	$response = $connection->checkForUpdates('https://docs.google.com/feeds/default/private/full/document%3A'.$docid, $etag);
	$authStringGotUpdated = strcmp($authString, $connection->getAuthString()) != 0;
	if($authStringGotUpdated) {
		$privateString = StringTools::serializeForInclude($lastUpdateDate, $connection->getAuthString(), $menuTree);
		$output->store($privateString, dirname(__FILE__).'/private.php');
	}
	$pageChanged = strcmp($response, '304') != 0;
	if($pageChanged) {
		libxml_use_internal_errors(true);
		$responseContent = simplexml_load_string(str_replace('gd:etag', 'etag', $response));
		if(count(libxml_get_errors()) == 0) {
			$newEtag = $responseContent['etag'];
			$srcUrl = $responseContent->content['src'];
			$pageDownloader = new PageDownloader($connection, $output, $headerPath, $footerPath);
			$pageDownloader->download($srcUrl, $newEtag, substr(strrchr($_SERVER['SCRIPT_NAME'], '/'), 1));
			header('Location: http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']);
		}
	}
}
$connection->close();

// Download a page and do it several treatments:
// // extract body, ROT13 anti-spam, download formulas and images, format striked code
// // --------------------------------------
// function dlPage($gdocUrl, $etag, $target, $connection) {
// 	$content = $connection->getRequest($gdocUrl);
// 	
// 	// ROT13 mail anti-spam
// 	// example: <a href="mailto:toto@gmail.com">contact</a>
//   	while(true) {
//   		$fromPos = @strpos($content, '<a href="mailto:');
// 		$toPos = @strpos($content, '</a>', $fromPos);
// 		if($fromPos == 0 or $toPos == 0)
// 			break;
//    		$mailString = substr($content, $fromPos, $toPos - $fromPos);
//    		$mailText = substr(strrchr(str_replace('@', ' a ', $mailString), ">"), 1);
// 		$mailROT13 = str_rot13(substr($mailString, 16, -1 * (2 + strlen($mailText))));
// 		$content = substr($content, 0, $fromPos)
// 			.'<script type="text/javascript">document.write("<n uers=\"znvygb:'.$mailROT13.'\" ery=\"absbyybj\">".replace(/[a-zA-Z]/g, function(c){return String.fromCharCode((c<="Z"?90:122)>=(c=c.charCodeAt(0)+13)?c:c-26);}));</script>'.$mailText.'</a>'
// 			.substr($content, $toPos + 4);
//     }
// 	
// 	// TODO: backup images
// 	// TODO: backup drawings
// 	// TODO: backup forumlas
// 	// Correction of a bug that makes formulas not working.
// 	$content = str_replace('&amp;', '&', $content);
// 	
// 	$content = str_replace("<style type=", "<sstyle type=", $content);
// 
// 	// Add $docid, include(header) and include(footer)
// 	global $headerPath, $footerPath;
// 	$docid = substr(strstr($gdocUrl, '='), 1);
// 	$pageString = <<<BIGSTRING
// <?php
// \$docid = '$docid';
// \$etag = '$etag';
// include('$headerPath');
// ?!>$content<?php
// include('$footerPath');
// ?!>
// BIGSTRING;
// 	store($pageString, $target);
// 	return;
// }

// // Scan a folder recursively and download new objects
// // --------------------------------------
// function scanFolder($url, $currentFolderDepth, $connection) {
// 	// debug : echo getRequest("https://docs.google.com/feeds/default/private/full?prettyprint=true");
// 	// Make the GET request of the folder content feed and parse the folderContent	
// 	$folderContent = simplexml_load_string(str_replace('gd:etag', 'etag', $connection->getRequest($url))); //."?prettyprint=true"
// 	$foldersArray = array();
// 	$pagesArray = array();
// 	
// 	// Foreach entry, call the right methode and store the name in the right array
// 	// foldersArray: key:name => value:sub-folder
// 	// pagesArray: key:menu_position => value:name
// 	foreach($folderContent->entry as $file) {
// 		global $lastUpdateDate;
// 		// Extract the strings $type, $name, $srcUrl, $path
// 		// and the boolean $isNew wich is true if the file is new
// 		$type = (string)($file->content["type"]);
// 		$name = (string)($file->title);
// 		$name = str_replace('"', '\"', str_replace("$", "\$", $name)); // protect names (see StringTools::serializeForInclude())
// 		$srcUrl = (string)($file->content["src"]);
// 		$lastModified = (string)($file->updated);
// 		$etag = (string)($file['etag']);
// 		$path = $currentFolderDepth.($currentFolderDepth == "" ? "" : "/").StringTools::urlFormat(StringTools::indexClean($name));
// 		$isNew = $lastUpdateDate < $lastModified;
// 		
// 		if($type == "application/atom+xml;type=feed") {
// 			// Creat the next folder
// 			if(!is_dir($path)) {
// 				// global $folderMode;
// 				// mkdir($path, $folderMode);
// 				StringTools::sysout("Folder: $path");
// 			}
// 			// Recursively store the sub folder.
// 			// Note that googleDoc only allow a "standard" forder hierarchy
// 			$foldersArray[$name.'!$!'] = scanFolder($srcUrl, $path, $connection);
// 			//print_r($foldersArray); echo "**<br>";
// 		} else if(substr($srcUrl, 0, 23) == "https://docs.google.com") {
// 			$pagesArray[] = $name.'!$!'; // !$! is a end of name protectio removed in StringTools::serializeForInclude(), i know that's not very nice..
// 			if($isNew) {
// 				StringTools::sysout("Page: $path");
// 				dlPage($srcUrl, $etag, $path.".php", $connection);
// 			}
// 		} else if($isNew) {
// 			store($connection->getRequest($srcUrl), $path);
// 			StringTools::sysout("File: $path");
// 		}
// 	}
// 	
// 	// Sort folders and pages by name
// 	ksort($foldersArray);
// 	sort($pagesArray);
// 	
// 	// Remove indexes
// 	foreach ($foldersArray as $key => $value) {
// 		$cleanKey = StringTools::indexClean($key);
// 		if($key != $cleanKey) {
//     		$foldersArray[$cleanKey] = $foldersArray[$key];
// 			unset($foldersArray[$key]);
// 		}
// 	}
// 	foreach ($pagesArray as $key => $value) {
// 		$cleanValue = StringTools::indexClean($pagesArray[$key]);
// 		$pagesArray[$key] = $cleanValue;
// 	}
// 	
// 	return array("folders" => $foldersArray, "pages" => $pagesArray);
// }
?>