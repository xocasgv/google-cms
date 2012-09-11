<?php error_reporting(E_ALL);

// "https://docs.google.com/document/d/$docid/edit"

include(dirname(__FILE__).'/config.php');
Main::init();

class Main {
  public static function init() {
		$configured = true;
		if(!$configured) {
			echo("There is no config.php file");
			exit();
		}
		
		echo str_pad('', 1024); // Pre flush instruction needed for Safari
		Connection::$authString = LAST_AUTH_STRING;
		$visitingThisFile = (strrchr(__FILE__, '/')) === (strrchr($_SERVER['PHP_SELF'], '/'));
		if($visitingThisFile) {
			self::globalRefresh();
		} else { // The file is "included"
			register_shutdown_function('Main::pageRefresh');
		}
  }

  public static function pageRefresh() {
		flush();

		global $docid, $etag; // google-cms.php is included from an executing page.php where this variables should be declared.
		$response = Connection::etagRequest('https://docs.google.com/feeds/default/private/full/document%3A'.$docid, $etag);
 		$isAuthStringUpdated = strcmp(LAST_AUTH_STRING, Connection::$authString) != 0;
 		if($isAuthStringUpdated) {
 			$configPhpString = StringTools::serializeForInclude(LAST_UPDATE_DATE, MENU);
			Output::store($configPhpString, __DIR__ . '/config.php');
 		}
 		$isPageUpdated = strcmp($response, '304') != 0;
 		if($isPageUpdated) {
 			libxml_use_internal_errors(true);
 			$responseContent = simplexml_load_string(str_replace('gd:etag', 'etag', $response));
 			if(0 == count(libxml_get_errors())) {
 				$newEtag = $responseContent['etag'];
 				$srcUrl = $responseContent->content['src'];
 				PageDownloader::download($srcUrl, $newEtag, $_SERVER['SCRIPT_FILENAME']);
 				Output::refresh();
 			}
 		}
  }
  
  private static function globalRefresh() {
		Output::println('Start');
  	date_default_timezone_set('GMT'); // this is the time used in google feeds
  	$newUpdateDate = gmdate(substr(DateTime::ATOM, 0, -1), strtotime('-1 hour')); // -1 hour because of the feeds delay.
  	$rootFolderUrl = 'https://docs.google.com/feeds/default/private/full/folder%3A'.ROOT_FOLDER_ID.'/contents';
  	$menuTree = FolderScanner::scan($rootFolderUrl, ROOT_PATH); // Publish all pages and files
  	Output::println('Done.');
  	$configPhpString = StringTools::serializeForInclude($newUpdateDate, $menuTree);
		Output::store($configPhpString, __DIR__ . '/config.php');
  }
}

class FolderScanner {
	// Scan a folder recursively and download new objects
	public static function scan($url, $currentFolderDepth) {
		// debug : echo getRequest("https://docs.google.com/feeds/default/private/full?prettyprint=true");
		$result = Connection::getRequest($url); //."?prettyprint=true"
		$folderContent = simplexml_load_string(str_replace('gd:etag', 'etag', $result));
		$foldersArray = array();
		$pagesArray = array();

		// foldersArray: key:name => value:sub-folder
		// pagesArray: key:menu_position => value:name
		foreach($folderContent->entry as $file) {
			$type = (string) ($file->content['type']);
			$name = (string) ($file->title);
			$name = str_replace('"', '\"', str_replace('$', '\$', $name));
			$srcUrl = (string) ($file->content['src']);
			$lastModified = (string) ($file->updated);
			$etag = (string) ($file['etag']);
			$path = $currentFolderDepth.($currentFolderDepth == '' ? '' : '/').StringTools::urlFormat(StringTools::indexClean($name));
			$isNew = LAST_UPDATE_DATE < $lastModified;

			if($type == 'application/atom+xml;type=feed') {
				if(!is_dir($path)) {
					Output::createFolder($path);
					Output::println("Folder: $path");
				}
				$foldersArray[$name.'!$!'] = self::scan($srcUrl, $path); // Recursively store the sub folder.
			} else if(substr($srcUrl, 0, strlen('https://docs.g')) == 'https://docs.g') {
				$pagesArray[] = $name.'!$!'; // !$! is a end of name protection. It's removed in StringTools::serializeForInclude()
				Output::println("Page: $path");
				PageDownloader::download($srcUrl, $etag, $path.'.php');
			} else if($isNew || !file_exists($path)) {
				Output::store(Connection::getRequest($srcUrl), $path);
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
		$content = Connection::getRequest($gdocUrl);
		
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
		
		// ROT13 mail anti-spam.
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
		
		// TODO: backup images, drawings, forumlas
		
		// Create the final page.php file with it's docid, etag, contentHtml and contentCss variable in addition to the include('absoluteTemplatePath') instruction.
		$docid = substr(strstr($gdocUrl, '='), 1);
		$templatePath = __DIR__ . '/template.php';
		$pageString = <<<EOD
<?php
\$docid = '$docid';
\$etag = '$etag';

\$html = <<<HTML
$html
HTML;

\$css = <<<CSS
$css
CSS;

include_once('$templatePath');
?>
EOD;
    if(strlen($docid) != 0)
		  Output::store($pageString, $target);
	}
}

class Connection {
	public static $authString;
	
	// Perform an authentified http GET request to the given url
	public static function getRequest($url) {
		$headers = array("Authorization: GoogleLogin auth=".self::$authString, "GData-Version: 3.0");
		$response = self::get($url, $headers);
		
		// If the authString is out of date (each 2 weeks) or invalid, get another one and make the request a second time.
		if (strpos($response, "<H1>Token invalid</H1>") or strpos($response, "<H1>Token expired</H1>")) {
			self::refreshAuthString();
			$headers = array("Authorization: GoogleLogin auth=".self::$authString, "GData-Version: 3.0");
			$response = self::get($url, $headers);

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
		    "Email" => LOGIN,
		    "Passwd" => PASSWORD,
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
		self::$authString = $matches[1];
	}
}

class Output {
	public static function println($s) {
		echo $s."<br>\n";
		flush();
	}
		
	public static function refresh() {
		$url = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'].'?';
		echo '<script>window.onload = function() { window.location.href = "'.$url.'"; };</script>';
	}
	
	public static function store($string, $target) {
		// TODO: error handling
		// if (!is_dir($imageDir) or !is_writable($imageDir)) {
		//     // Error if directory doesn't exist or isn't writable.
		// } elseif (is_file($imagePath) and !is_writable($imagePath)) {
		//     // Error if the file exists and isn't writable.
		// }
		file_put_contents($target, $string);
		chmod($target, fileperms(__FILE__));
	}
	
	public static function createFolder($path) {
		mkdir($path, fileperms(__DIR__));
	}
}

class StringTools {
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
    return str_replace(
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
        mb_strtolower($string, 'UTF-8')
    );
	}

	public static function serializeForInclude($lastUpdateDate, $menuTree) {
	  $serialisedMenu = str_replace(
			array( "] => ",	"[", "\n\n",	"\n",		'"Array",',	'(",', ')",',	'!$!'	),
			array( '" => "', '"', "\n",		"\",\n",	"Array",	"(", "),",		''		),
			print_r($menuTree, true)
		);
		if(strlen($serialisedMenu) == 0)
			$serialisedMenu = '()';
		else
			$serialisedMenu = substr($serialisedMenu, 8, -2);
		$login = LOGIN;
		$password = PASSWORD;
		$rootFolderId = ROOT_FOLDER_ID;
		$rootPath = ROOT_PATH;
		$lastAuthString = Connection::$authString;
		return <<<EOD
<?php
define("LOGIN", "$login");
define("PASSWORD", "$password");
define("ROOT_FOLDER_ID", "$rootFolderId");

define("ROOT_PATH", "$rootPath");
define("LAST_UPDATE_DATE", "$lastUpdateDate");
define("LAST_AUTH_STRING", "$lastAuthString");
\$MENU = Array$serialisedMenu;
?>
EOD;
	}
}

?>