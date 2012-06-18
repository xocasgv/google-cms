<?php include_once('google-cms.php'); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="fr-FR">
<head>
	<title>Google-CMS Tester</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<style type="text/css" media="screen">
		*{margin:0;padding:0}
		body{font-family:"Lucida Grande", "Lucida Sans", "Lucida Sans Unicode", Verdana, Arial, sans-serif;background:#527785}
		table,td,tr,ul,li{font-size:1em}
		h1,h2,h3,h4,h5,h6{font-family:"Trebuchet MS", Verdana, sans-serif;color:#D82026}
		h1{font-size:2.5em;font-weight:700;padding:40px 0 20px}
		h2{font-size:2.5em;padding:20px 0 5px}
		h3,h4{font-size:2em;font-weight:400;letter-spacing:-1px}
		img{border:0}
		#page{clear:both;padding-top:40px;width:900px;border:1px solid #042E48;background:#FFE7AE;margin:0 auto}
		#header{height:122px;background:#D82026;font-size:3.5em;color:white;padding:55px 40px 0}
		#menu{float:right;width:300px;min-height:700px;font-family:"Trebuchet MS", Verdana, sans-serif;
			font-size:1.3em;font-weight:700;letter-spacing:1px;line-height:1.5em;color:#042E48;padding:35px 0 0;
			background-image: linear-gradient(bottom, rgb(255,231,174) 0%, rgb(113,146,159) 100%);
			background-image: -o-linear-gradient(bottom, rgb(255,231,174) 0%, rgb(113,146,159) 100%);
			background-image: -moz-linear-gradient(bottom, rgb(255,231,174) 0%, rgb(113,146,159) 100%);
			background-image: -webkit-linear-gradient(bottom, rgb(255,231,174) 0%, rgb(113,146,159) 100%);
			background-image: -ms-linear-gradient(bottom, rgb(255,231,174) 0%, rgb(113,146,159) 100%)}
		#menu h4{display:block;margin-bottom:5px;font-size:1.88em;font-weight:400;padding:16px 0 8px}
		#menu ul li{list-style-type:none;list-style-image:none;margin:15px 20px;padding:5px 0 5px 30px}
		#menu li.on{color:#FFE7AE;background:#D82026}
		#menu li.off{background:#042E48}
		#menu a,#menu a:visited{color:#8CABAD;text-decoration:none}
		#menu a:hover,#menu a:visited:hover{color:#FFE7AE;text-decoration:underline}
		#corpus{width:520px;min-height:800px;padding:0 0 0 40px}
		#corpus p{font-size:1.1em;line-height:1.5em;color:#042E48}
		#footer{clear:both;text-align:center;color:#FFE7AE;font-size:.8em;line-height:1.6em;margin:0 auto;padding:20px 0 40px}
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
				foreach($MENU["pages"] as $page) {
		       		$pageURLF = StringTools::urlFormat($page);
					if ($pageURLF == $currentPageURLF)
						echo "<ul><li class=\"on\">".$page."</li></ul>\n";
					else
						echo "<ul><li class=\"off\"><a href=\"".$pageURLF.'">'.$page."</a></li></ul>\n";
				}
			?>
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