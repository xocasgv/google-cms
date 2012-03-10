<?php
class StringTools {
	// Clean the index of a string if it exists
	// ----------------------------------------
	public static function indexClean($s) {
		$string = (string) $s;
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
	// Source: http://goo.gl/Guqk3
	// -------------------------------------
	public static function urlFormat($s) {
		$string = (string) $s;
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
	// -----------------------------------------------------
	public static function serializeForInclude($lastUpdateDateARG, $authStringARG, $menuTreeARG) {
		$lastUpdateDate = (string) $lastUpdateDateARG;
		$authString = (string) $authStringARG;
		$menuTree = (array) $menuTreeARG;
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