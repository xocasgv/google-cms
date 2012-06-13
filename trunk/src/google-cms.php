<?php
error_reporting(E_ALL);
require(dirname(__FILE__).'/config.php');
// $login
// $password
// $rootFolderId
// $templatePath
// $rootPath
// $folderMode
// $fileMode

// TODO: add to config.php
// $lastUpdateDate
// $authString
// $menuTree

Main::init();

class Main {
  public function init() {
    // if (!defined('TEST')) {
    // exit();}
    
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
  }

  private function pageRefresh() {
 
  }
  
  private function globalRefresh() {
 
  }  
}

class FolderScanner {
	private $connection;
	private $lastUpdateDate;
	private $output;
	private $pageDownloader;

	public function __construct(Connection $connectionARG, Output $outputARG, $lastUpdateDateARG, PageDownloader $pageDownloaderARG) {
		$this->connection = $connectionARG;
		$this->lastUpdateDate = (string) $lastUpdateDateARG;
		$this->output = $outputARG;
		$this->pageDownloader = $pageDownloaderARG;
	}

	// Scan a folder recursively and download new objects
	// --------------------------------------------------
	public function scan($url, $currentFolderDepth) {
		// debug : echo getRequest("https://docs.google.com/feeds/default/private/full?prettyprint=true");
		$folderContent = simplexml_load_string(str_replace('gd:etag', 'etag', $this->connection->getRequest($url))); //."?prettyprint=true"
		$foldersArray = array();
		$pagesArray = array();

		// foldersArray: key:name => value:sub-folder
		// pagesArray: key:menu_position => value:name
		foreach($folderContent->entry as $file) {
			$type = (string)($file->content['type']);
			$name = (string)($file->title);
			$name = str_replace('"', '\"', str_replace('$', '\$', $name));
			$srcUrl = (string)($file->content['src']);
			$lastModified = (string)($file->updated);
			$etag = (string)($file['etag']);
			$path = $currentFolderDepth.($currentFolderDepth == '' ? '' : '/').StringTools::urlFormat(StringTools::indexClean($name));
			$isNew = $this->lastUpdateDate < $lastModified;

			if($type == 'application/atom+xml;type=feed') {
				if(!is_dir($path)) {
					$this->output->createFolder($path);
					$this->output->println("Folder: $path");
				}
				// Recursively store the sub folder.
				$foldersArray[$name.'!$!'] = $this->scan($srcUrl, $path);
			} else if(substr($srcUrl, 0, strlen('https://docs.g')) == 'https://docs.g') {
				$pagesArray[] = $name.'!$!'; // !$! is a end of name protectio removed in StringTools::serializeForInclude(), i know that's not very nice..
				if($isNew || !file_exists($path.'.php')) {
					$this->output->println("Page: $path");
					$this->pageDownloader->download($srcUrl, $etag, $path.'.php');
				}
			} else if($isNew || !file_exists($path)) {
				$this->output->store($this->connection->getRequest($srcUrl), $path);
				$this->output->println("File: $path");
			}
		}

		// Sort folders and pages by name
		ksort($foldersArray);
		sort($pagesArray);
		// Remove indexes
		foreach ($foldersArray as $key => $value) {
			$cleanKey = StringTools::indexClean($key);
			if($key != $cleanKey) {
				$foldersArray[$cleanKey] = $foldersArray[$key];
				unset($foldersArray[$key]);
			}
		}
		foreach ($pagesArray as $key => $value) {
			$cleanValue = StringTools::indexClean($pagesArray[$key]);
			$pagesArray[$key] = $cleanValue;
		}
		return array('folders' => $foldersArray, 'pages' => $pagesArray);
	}
}

class PageDownloader {
	private $connection;
	private $output;
	private $templatePath;
	
	public function __construct(Connection $connectionARG, Output $outputARG, $templatePathARG) {
		$this->connection = $connectionARG;
		$this->output = $outputARG;
		$this->templatePath = (string) $templatePathARG;
	}
	
	public function download($gdocUrl, $etag, $target) {
		$content = $this->connection->getRequest($gdocUrl);
		
		$tagCssStart = '<style type="text/css">';
		$tagCssEnd = '</style></head><body';
		$tagHtmlStart = $tagCssEnd;
		$tagHtmlEnd = '</body></html>';
		
		$fromPos = strpos($content, $tagCssStart);
		$fromPos += strlen($tagCssStart);
		$toPos = strpos($content, $tagCssEnd);
		$css = substr($content, $fromPos, $toPos - $fromPos);
		
		$fromPos = $toPos; // = strpos($content, $tagHtmlStart);
		$fromPos += strlen($tagHtmlStart);
		$toPos = strpos($content, $tagHtmlEnd);
		$html = substr($content, $fromPos, $toPos - $fromPos);
		$html = substr($html, strpos($html, '>') + 1); // Removes the end of <body class="??">
		
		// ROT13 mail anti-spam
		// example: <a href="mailto:toto@gmail.com">contact</a>
		$fromPos = @strpos($html, '<a href="mailto:');
		$toPos = @strpos($html, '</a>', $fromPos);
		while($fromPos != 0 and $toPos != 0) {
			$mailString = substr($html, $fromPos, $toPos - $fromPos);
			$mailText = substr(strrchr(str_replace('@', ' a ', $mailString), ">"), 1);
			$mailROT13 = str_rot13(substr($mailString, 16, -1 * (2 + strlen($mailText))));
			$html = substr($html, 0, $fromPos)
				.'<script type="text/javascript">document.write("<n uers=\"znvygb:'.$mailROT13.'\" ery=\"absbyybj\">".replace(/[a-zA-Z]/g, function(c){return String.fromCharCode((c<="Z"?90:122)>=(c=c.charCodeAt(0)+13)?c:c-26);}));</script>'.$mailText.'</a>'
				.substr($html, $toPos + strlen('</a>'));
			$fromPos = @strpos($html, '<a href="mailto:');
			$toPos = @strpos($html, '</a>', $fromPos);
		}
		$html = str_replace('&amp;', '&', $html);
		
		// Removes all CSS rules that are not contextual (not starting with '.').
		// CSS removed: body{font-family:arial,sans,sans-serif;margin:0;color:#000000;font-size:11pt;}p{margin:0}h1{padding-top:0pt;line-height:1.15;text-align:left;color:#000000;font-size:24pt;font-weight:bold;padding-bottom:0pt}h2{padding-top:0pt;line-height:1.15;text-align:left;color:#000000;font-size:18pt;font-weight:bold;padding-bottom:0pt}h3{padding-top:0pt;line-height:1.15;text-align:left;color:#000000;font-size:14pt;font-weight:bold;padding-bottom:0pt}h4{padding-top:0pt;line-height:1.15;text-align:left;color:#000000;font-size:12pt;font-weight:bold;padding-bottom:0pt}h5{padding-top:0pt;line-height:1.15;text-align:left;color:#000000;font-size:11pt;font-weight:bold;padding-bottom:0pt}h6{padding-top:0pt;line-height:1.15;text-align:left;color:#000000;font-size:10pt;font-weight:bold;padding-bottom:0pt}maindiv{width:468pt;background-color:#ffffff;padding:72pt 72pt 72pt 72pt}
		$miniCss = "";
		$cursorPos = 0;
		$closingCBPos = strpos($css, '}', $cursorPos);
		while($closingCBPos != 0) {
			if ($css[$cursorPos] == '.')
				$miniCss .= substr($css, $cursorPos, $closingCBPos + 1 - $cursorPos);
			$cursorPos = $closingCBPos + 1;
			$closingCBPos = strpos($css, '}', $cursorPos);
		}
		$css = $miniCss;
		
		// TODO: backup images
		// TODO: backup drawings
		// TODO: backup forumlas
		// TODO: Correction of a bug that makes formulas not working.
		
		// Create the final page.php file with it's docid, etag, contentHtml and contentCss variable in addition to the include(templatePath) instruction.
		$docid = substr(strstr($gdocUrl, '='), 1);
		$pageString = <<<BIGSTRING
<?php
\$docid = '$docid';
\$etag = '$etag';

\$html = <<<HTML
$html
HTML;

\$css = <<<CSS
$css
CSS;

include_once('$this->templatePath');
?>
BIGSTRING;
		$this->output->store($pageString, $target);
	}
}

class Connection {
	private $curl;
	private $headers;
	
	// Initialize the curl object
	public function __construct($authStringARG) {
		$this->authString = (string) $authStringARG;
		$this->curl = curl_init();
		// Include the Auth string in the headers
		// Together with the API version being used
		$this->headers = array(
		    "Authorization: GoogleLogin auth=" . $this->authString,
		    "GData-Version: 3.0",
		);
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->headers);
		curl_setopt($this->curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($this->curl, CURLOPT_POST, false);
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
	}
	
	// Perform an authentified http GET request to the given url
	// if response is "Token invalid" refresh the authString() make the request again
	public function getRequest($url) {
		// Make the request
		curl_setopt($this->curl, CURLOPT_URL, $url);
		$response = curl_exec($this->curl);
		
		// If the authString is out of date (each 2 weeks)  strlen($response) < 1000 and 
		if (strpos($response, "<H1>Token invalid</H1>") or strpos($response, "<H1>Token expired</H1>")) {
			// Get another one and make the request a second time
			$this->refreshAuthString();
			curl_setopt($this->curl, CURLOPT_URL, $url);
			$response = curl_exec($this->curl);
			// If we stil get an error, display "Login failed" and die
			if (strpos($response, "<H1>Token invalid</H1>") or strpos($response, "<H1>Token expired</H1>")) {
				echo "<H1>Login failed</H1><BR>".$response;
				exit(-1);
				// Be carefull, after a few attempts google ask for captcha (not supported)
			}
		}
		if($response == "") {
			return curl_getinfo($this->curl, CURLINFO_HTTP_CODE); // ex: 404
		} else {
			return $response;
		}
	}
	
	// Return true if the page has been modified
	public function checkForUpdates($url, $etag) {
		$this->headers[] = 'If-None-Match: '.$etag;
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->headers);
		return $this->getRequest($url);
	}

	public function close() {
		curl_close($this->curl);
	}
	
	public function getAuthString() {
		return $this->authString;
	}
	
	private function refreshAuthString() {
		$tempCurl = curl_init();
		$clientloginUrl = "https://www.google.com/accounts/ClientLogin";
		$clientloginPost = array(
		    "accountType" => "HOSTED_OR_GOOGLE",
		    "Email" => $LOGIN,
		    "Passwd" => $PASSWORD,
		    "service" => "writely", // the "Google Documents List Data AP" service name
		    "source" => "GoogleCms 3beta"
		);
		curl_setopt($tempCurl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($tempCurl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($tempCurl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($tempCurl, CURLOPT_URL, $clientloginUrl);
		curl_setopt($tempCurl, CURLOPT_POST, true);
		curl_setopt($tempCurl, CURLOPT_POSTFIELDS, $clientloginPost);

		$response = curl_exec($tempCurl);
		curl_close($tempCurl);

		preg_match("/Auth=([a-z0-9_\-]+)/i", $response, $matches);
		$this->authString = $matches[1];
		$this->headers = array(
		    "Authorization: GoogleLogin auth=" . $this->authString,
		    "GData-Version: 3.0",
		);
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->headers);
	}
}

class Output {
	private $preFlushNeeded = true;
	
	// Instantly print a String with a line break
	public function println($s) {
		if($this->preFlushNeeded) {
			// Pre flush instruction needed for Safari 
			echo str_pad('',1024);
			$this->preFlushNeeded = false;
		}
		echo $s."<br>\n";
		flush();
	}
	
	// Store string in a file with the appropriate permissions
	public function store($string, $target) {
		$fp = fopen($target, "w");
		fwrite($fp, $string);
		fclose($fp);
		@chmod($target, $FILE_MODE);
	}
	
	// Create a folder with the appropriate permissions
	public function createFolder($path) {
		mkdir($path, $FOLDER_MODE);		
	}
}

class StringTools {
	// Clean the index of a string if it exists
	public static function indexClean($string) {
		if($string[0] >= '0' and $string[0] <= '9') {
			$clean = trim(strstr($string, ' '));
			if($clean != '') { // for $string = "25a8"
				return $clean;
			}
		}
		return $string;
	}
	
	// Format a text for URL (special UTF-8). Dots are not removed. Source: http://goo.gl/Guqk3
	public static function urlFormat($string) {
	  $string = mb_strtolower($string, 'UTF-8');
    $string = str_replace(
        array(
            'à', 'â', 'ä', 'á', 'ã', 'å', 'ß', "à", "á",
            'î', 'ï', 'ì', 'í', "ì", "í",
            'ô', 'ö', 'ò', 'ó', 'õ', 'ø', "ò", "ó",
            'ù', 'û', 'ü', 'ú', "ù", "ú",
            'é', 'è', 'ê', 'ë', "è", "é",
            'ç', 'ÿ', 'ñ',
            '’', "'", ',', ':', ';', '!', '?', '	', ' ',
            '@', '#', '%', '&', '<','>', '*', '=', '(', ')',
        ),
        array(
            'a', 'a', 'a', 'a', 'a', 'a', 'b', 'a', 'a',
            'i', 'i', 'i', 'i', 'i', 'i',
            'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o',
            'u', 'u', 'u', 'u', 'u', 'u',
            'e', 'e', 'e', 'e', 'e', 'e',
            'c', 'y', 'n',
            '', '', '', '', '', '', '', '', '-',
            '-', '-', '-', '-', '-', '-', '-', '-', '-', '-',
        ),
        $string
    );
    return $string;
	}

	// Serialize arguments as a php file "ready for include"
	public static function serializeForInclude($lastUpdateDate, $authString, $menuTree) {
		$mts = str_replace(
			array( "] => ",	"[", "\n\n",	"\n",		'"Array",',	'(",', ')",',	'!$!'	),
			array( '" => "', '"', "\n",		"\",\n",	"Array",	"(", "),",		''		),
			print_r($menuTree, true)
		);
		if(strlen($mts) == 0)
			$mts = 'Array()';
		else
			$mts = 'Array'.substr($mts, 8, -2);
		return <<<BIGSTRING
<?php
\$lastUpdateDate = "$lastUpdateDate";
\$authString = "$authString";
\$menuTree = $mts;
?>
BIGSTRING;
	}
}

?>