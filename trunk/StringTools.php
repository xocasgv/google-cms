<?php
class StringTools {
	// String output
	// --------------------------------------
	public static function sysout($stringARG) {
		$string = (string) $stringARG;
		echo $string."<br>\n";
		flush(); // Display the page before the end of the script
	}
	
	// Clean the index of a string if exists
	// --------------------------------------
	public static function indexClean($stringARG) {
		$string = (string) $stringARG;
		if($string[0] >= '0' and $string[0] <= '9') {
			$clean = trim(strstr($string, ' '));
			if($clean != '') { // for $string = "25a8"
				return $clean;
			}
		}
		return $string;
	}
	
	// Format a text for URL (special UTF-8)
	// Note: dots are not removed
	// Source: http://www.developpez.net/forums/d284411/php/langage/fonctions/suppression-daccents-utf-8-a/#post1787019
	// --------------------------------------
	public static function urlFormat($stringARG) {
		$string = (string) $stringARG;
	    $string = mb_strtolower($string, 'UTF-8');
	    $string = str_replace(
	        array(
	            'à', 'â', 'ä', 'á', 'ã', 'å', 'ß',
	            'î', 'ï', 'ì', 'í',
	            'ô', 'ö', 'ò', 'ó', 'õ', 'ø',
	            'ù', 'û', 'ü', 'ú',
	            'é', 'è', 'ê', 'ë',
	            'ç', 'ÿ', 'ñ',
	            '’', "'", ',', ':', ';', '!', '?', '	',
	            ' ', '@', '#', '%', '&', '<','>', '*', '=', '(', ')',
	        ),
	        array(
	            'a', 'a', 'a', 'a', 'a', 'a', 'b',
	            'i', 'i', 'i', 'i',
	            'o', 'o', 'o', 'o', 'o', 'o',
	            'u', 'u', 'u', 'u',
	            'e', 'e', 'e', 'e',
	            'c', 'y', 'n',
	            '', '', '', '', '', '', '', '',
	            '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-',
	        ),
	        $string
	    );
	    return $string;
	}
	
	public static function serializeForInclude($lastUpdateDateARG, $authStringARG, $menuTreeARG) {
		$lastUpdateDate = (string) $lastUpdateDateARG;
		$authString = (string) $authStringARG;
		$menuTree = (array) $menuTreeARG;
		$menuTreeSerialized = str_replace(
			array( "] => " , "[", "\n\n",	"\n",			"\"Array*$*\",",	"(*$*\",",	")*$*\",",	"*$*"	),
			array( '" => "', '"', "\n",		"*$*\",\n",		"Array",			"(",		"),",		""		),
			print_r($menuTree, true)
		);
		// array( "] => ",	"[", "\n\n",	"\n",		'"Array",',	'(",', ')",'	),
		// array( '" => "', '"', "\n",		"\",\n",	"Array",	"(", "),",		),
		// These ^ two lines are also fonctional but I added a token string *$* to prevent
		// page's names ending with Array or ) to be miss converted. Anyway if you need to
		// uderstand this you need to output print_r($menuTree) to see what's done here.
		$menuTreeSerialized = 'Array'.substr($menuTreeSerialized, 8, -2);
		$result = <<<BIGSTRING
<?php
*$*lastUpdateDate = "$lastUpdateDate";
*$*authString = "$authString";
*$*menuTree = $menuTreeSerialized;
?>
BIGSTRING;
		// Here also I use *$* as a token string. This is safe since all strings pass through
		// $name = str_replace('"', '\"', str_replace("$", "\$", $name));
		$result = str_replace('*$*', '$', $result);
		return $result;
	}
}
?>