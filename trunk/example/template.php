<?php include_once('googlecms.php'); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="fr-FR"> 
<head> 
	<title>Google-CMS Tester</title> 
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"> 
	<link rel="stylesheet" type="text/css" media="screen" href="style.css"> 
	<style type="text/css" media="screen">
		<?= $css ?>
	</style>
</head>
<body> 
	<div id="page">
		<div id="header">
			Google-CMS Tester
		</div>
		<div id="menu"> 
			<?php
				$chemin = explode('/', $_SERVER["SCRIPT_NAME"]);
				$currentPage = substr($chemin[(count($chemin)-1)], 0, -4);
	       		$currentPageURLF = StringTools::urlFormat($currentPage);
				foreach($menuTree["pages"] as $page) {
		       		$pageURLF = StringTools::urlFormat($page);
					if ($pageURLF == $currentPageURLF)
						echo "<ul><li class=\"on\">".$page."</li></ul>\n";
					else
						echo "<ul><li class=\"off\"><a href=\"".$pageURLF.'">'.$page."</a></li></ul>\n";
				}
            ?>
			<div id="deco_menu"><img src="fond.png"></div> 
		</div>
		<div id="corpus">
			<?= $html ?>
			<br><br>
		</div> <!-- corpus --> 
	</div> <!-- page --> 
	<div id="footer">
		There is nothing to say in this footer.
	</div>
</body> 
</html>