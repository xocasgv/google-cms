<?php
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
?>