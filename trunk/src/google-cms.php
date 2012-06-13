<?php error_reporting(E_ALL);
// $LOGIN
// $PASSWORD
// $ROOT_FOLDER_ID
// $TEMPLATE_PATH
// $ROOT_PATH
// $FOLDER_MODE
// $FILE_MODE

Main::init();

class Main {
  public static function init() {
		$configured = @include(dirname(__FILE__).'/config.php'); // TODO: add lastUpdateDate/LAST_AUTH_STRING/menuTree to config.php
		if(!$configured) {
			echo("There is no config.php file");
			exit();
		}
		
		echo str_pad('',1024); // Pre flush instruction needed for Safari
		Connection::$authString = $LAST_AUTH_STRING;
		$visitingThisFile = (strrchr(__FILE__, '/')) === (strrchr($_SERVER['PHP_SELF'], '/'));
		if($visitingThisFile) {
			self::globalRefresh();
		} else { // The file is "included"
			self::pageRefresh();
		}
  }

  private static function pageRefresh() {
		// $docid
  	// $etag
  	$action = $_SERVER['QUERY_STRING'];
  	if($action == 'edit' or $action == 'e') {
  		header('Location: https://docs.google.com/document/d/'.$docid.'/edit');
  	} else {
  		$response = Connection::etagRequest('https://docs.google.com/feeds/default/private/full/document%3A'.$docid, $etag);
  		$authStringGotUpdated = strcmp($authString, $connection->getAuthString()) != 0;
  		if($authStringGotUpdated) {
  			$privateString = StringTools::serializeForInclude($LAST_UPDATE_DATE, $connection->getAuthString(), $menuTree);
  			Output::store($privateString, $privateFilePath);
  		}
  		$pageChanged = strcmp($response, '304') != 0;
  		if($pageChanged) {
  			libxml_use_internal_errors(true);
  			$responseContent = simplexml_load_string(str_replace('gd:etag', 'etag', $response));
  			if(0 == count(libxml_get_errors())) {
  				$newEtag = $responseContent['etag'];
  				$srcUrl = $responseContent->content['src'];
  				PageDownloader::download($srcUrl, $newEtag, substr(strrchr($_SERVER['SCRIPT_NAME'], '/'), 1));
  				// TODO: header('Location: http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']);
  			}
  		}
  	}
  }
  
  private static function globalRefresh() {
		Output::println('Start');
  	date_default_timezone_set('GMT'); // this is the time used in google feeds
  	$newUpdateDate = gmdate(substr(DateTime::ATOM, 0, -1), strtotime('-1 hour')); // -1 hour because of the feeds delay.
  	$rootFolderUrl = 'https://docs.google.com/feeds/default/private/full/folder%3A'.$ROOT_FOLDER_ID.'/contents';
  	$menuTree = FolderScanner::scan($rootFolderUrl, $ROOT_PATH); // Publish all pages and files
  	$newAuthString = Connection::$authString;
  	Output::println('Done.');
  	$privateString = StringTools::serializeForInclude($newUpdateDate, $newAuthString, $menuTree);
  	Output::store($privateString, $privateFilePath);
  }
}

class FolderScanner {
	// Scan a folder recursively and download new objects
	public static function scan($url, $currentFolderDepth) {
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
					Output::createFolder($path);
					Output::println("Folder: $path");
				}
				// Recursively store the sub folder.
				$foldersArray[$name.'!$!'] = $this->scan($srcUrl, $path);
			} else if(substr($srcUrl, 0, strlen('https://docs.g')) == 'https://docs.g') {
				$pagesArray[] = $name.'!$!'; // !$! is a end of name protectio removed in StringTools::serializeForInclude(), i know that's not very nice..
				if($isNew || !file_exists($path.'.php')) {
					Output::println("Page: $path");
					$this->pageDownloader->download($srcUrl, $etag, $path.'.php');
				}
			} else if($isNew || !file_exists($path)) {
				Output::store($this->connection->getRequest($srcUrl), $path);
				Output::println("File: $path");
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
	public static function download($gdocUrl, $etag, $target) {
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
		Output::store($pageString, $target);
	}
}

class Connection {
	public static $authString;
	
	// Perform an authentified http GET request to the given url
	public static function getRequest($url) {
		$headers = array("Authorization: GoogleLogin auth=".self::$authString, "GData-Version: 3.0");
		$response = self::get($url, $headers)
		
		// If the authString is out of date (each 2 weeks) or invalid, get another one and make the request a second time.
		if (strpos($response, "<H1>Token invalid</H1>") or strpos($response, "<H1>Token expired</H1>")) {
			self::refreshAuthString();
			$headers = array("Authorization: GoogleLogin auth=".self::$authString, "GData-Version: 3.0");
			$response = self::get($url, $headers)

			if (strpos($response, "<H1>Token invalid</H1>") or strpos($response, "<H1>Token expired</H1>")) {
				echo "<H1>Login failed</H1><BR>".$response;
				exit(-1); // Be carefull, after a few attempts google ask for captcha (not supported)
			}
		}
		
		return $response;
	}
	
	public static function etagRequest($url, $etag) {
		$headers = array("Authorization: GoogleLogin auth=".self::$authString, "GData-Version: 3.0", 'If-None-Match: '.$etag);
		return self::get($url, $headers);
	}
	
	private static function get($url, $headers) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($curl, CURLOPT_POST, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_URL, $url);
		$response = curl_exec($curl);
		if($response == "") {
			$response = curl_getinfo($curl, CURLINFO_HTTP_CODE); // ex: 404
		}
		curl_close($curl);
		return $response;
	}
	
	private static function refreshAuthString() {
		$curl = curl_init();
		$clientloginUrl = "https://www.google.com/accounts/ClientLogin";
		$clientloginPost = array(
		    "accountType" => "HOSTED_OR_GOOGLE",
		    "Email" => $LOGIN,
		    "Passwd" => $PASSWORD,
		    "service" => "writely", // the "Google Documents List Data AP" service name
		    "source" => "GoogleCms 3beta"
		);
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_URL, $clientloginUrl);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $clientloginPost);
		$response = curl_exec($curl);
		curl_close($curl);

		preg_match("/Auth=([a-z0-9_\-]+)/i", $response, $matches);
		self::authString = $matches[1];
	}
}

class Output {
	// Print a String with a line break and flush the page.
	public static function println($s) {
		echo $s."<br>\n";
		flush();
	}
	
	// Store string in a file with the appropriate permissions
	public static function store($string, $target) {
		file_put_contents($target, $string);
		// $fp = fopen($target, "w");
		// fwrite($fp, $string);
		// fclose($fp);
		@chmod($target, $FILE_MODE);
	}
	
	public static function createFolder($path) {
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