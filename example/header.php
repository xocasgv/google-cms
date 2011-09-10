<?php include_once('googlecms.php'); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="fr-FR"> 
<head> 
	<title>Google-CMS Tester</title> 
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"> 
	<link rel="stylesheet" type="text/css" media="screen" href="25a8_files/style.css"> 
</head> 
<body> 
	<div id="page">
		<div id="header">
			Google-CMS Tester
		</div>
		<div id="menu"> 
			<!-- 
			< ?php
			$chemin = explode('/', $_SERVER["SCRIPT_NAME"]);
			$rep = $chemin[(count($chemin)-2)];
			$page = substr($chemin[(count($chemin)-1)], 0, -4);
			foreach($menuTree["folders"] as $ongletName => $ongletArray ) {
				if ($ongletName[0] == "-") {
					continue;
				}
				$ongletArray["pages"][0];
	       		$page1 = StringTools::urlFormat($ongletArray["pages"][0]);
	       		$ongletUrlF = StringTools::urlFormat($ongletName);
				if ($ongletUrlF == $rep) {
					$pageList = $ongletArray["pages"];
					$currentongletName = $ongletName;
					echo "<li class=\"current_page_item\"><a href=\"../$ongletUrlF/$page1\">$ongletName</a></li>\n";
				} else {
					echo "<li><a href=\"../$ongletUrlF/$page1\">$ongletName</a></li>\n";
				}
			}
            ? > -->
			<ul><li class="on">Accueil</li></ul> 
			<ul><li class="off"><a href="../sites_web/sites_web">Sites Web</a></li></ul> 
			<div id="deco_menu"><img src="25a8_files/fond.png"></div> 
		</div>
		<div id="corpus"> 
<!-- ------------------------------------------------ -->