<?php
class StringTools {
	// Clean the index of a string if exists
	// --------------------------------------
	public static function indexClean($string) { // string
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
	public static function urlFormat($string) { // string
	    $string = mb_strtolower($string, 'UTF-8');
	    $string = str_replace(
	        array(
	            'à', 'â', 'ä', 'á', 'ã', 'å', 'ß', "à", "á",
	            'î', 'ï', 'ì', 'í', "ì", "í",
	            'ô', 'ö', 'ò', 'ó', 'õ', 'ø', "ò", "ó",
	            'ù', 'û', 'ü', 'ú', "ù", "ú",
	            'é', 'è', 'ê', 'ë', "è", "é",
	            'ç', 'ÿ', 'ñ',
	            '’', "'", ',', ':', ';', '!', '?', '	',
	            ' ', '@', '#', '%', '&', '<','>', '*', '=', '(', ')',
	        ),
	        array(
	            'a', 'a', 'a', 'a', 'a', 'a', 'b', 'a', 'a',
	            'i', 'i', 'i', 'i', 'i', 'i',
	            'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o',
	            'u', 'u', 'u', 'u', 'u', 'u',
	            'e', 'e', 'e', 'e', 'e', 'e',
	            'c', 'y', 'n',
	            '', '', '', '', '', '', '', '',
	            '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-',
	        ),
	        $string
	    );
	    return $string;
	}

	// serializeForInclude
	// --------------------------------------	
	public static function serializeForInclude($lastUpdateDate, $authString, $menuTree) { // string, string, array
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