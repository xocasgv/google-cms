<?php
class PageDownloader {
	private $connection;
	private $output;
	private $headerPath;
	private $footerPath;
	
	function __construct(Connection $connectionARG, Output $outputARG, $headerPathARG, $footerPathARG) {
		$this->connection = $connectionARG;
		$this->output = $outputARG;
		$this->headerPath = $headerPathARG;
		$this->footerPath = $footerPathARG;
	}
	
	function download($gdocUrl, $etag, $target) {
		$content = $this->connection->getRequest($gdocUrl);

		// ROT13 mail anti-spam
		// example: <a href="mailto:toto@gmail.com">contact</a>
	  	while(true) {
	  		$fromPos = @strpos($content, '<a href="mailto:');
			$toPos = @strpos($content, '</a>', $fromPos);
			if($fromPos == 0 or $toPos == 0)
				break;
	   		$mailString = substr($content, $fromPos, $toPos - $fromPos);
	   		$mailText = substr(strrchr(str_replace('@', ' a ', $mailString), ">"), 1);
			$mailROT13 = str_rot13(substr($mailString, 16, -1 * (2 + strlen($mailText))));
			$content = substr($content, 0, $fromPos)
				.'<script type="text/javascript">document.write("<n uers=\"znvygb:'.$mailROT13.'\" ery=\"absbyybj\">".replace(/[a-zA-Z]/g, function(c){return String.fromCharCode((c<="Z"?90:122)>=(c=c.charCodeAt(0)+13)?c:c-26);}));</script>'.$mailText.'</a>'
				.substr($content, $toPos + 4);
	    }

		// TODO: backup images
		// TODO: backup drawings
		// TODO: backup forumlas
		// Correction of a bug that makes formulas not working.
		$content = str_replace('&amp;', '&', $content);

		$content = str_replace("<style type=", "<sstyle type=", $content);

		// Add $docid, include(header) and include(footer)
		$docid = substr(strstr($gdocUrl, '='), 1);
		$pageString = <<<BIGSTRING
<?php
\$docid = '$docid';
\$etag = '$etag';
include('$this->headerPath');
?>$content<?php
include('$this->footerPath');
?>
BIGSTRING;
		$this->output->store($pageString, $target);
	}
}

?>