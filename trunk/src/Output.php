<?php
class Output {
	private $fileMode;
	private $folderMode;
	private $preFlushNeeded;
	
	public function __construct($fileModeARG, $folderModeARG) {
		$this->fileMode = $fileModeARG;
		$this->folderMode = $folderModeARG;
		$this->preFlushNeeded = true;
	}
	
	// Instantly print a String with a line break
	// ------------------------------------------
	public function println($s) {
		if($this->preFlushNeeded) {
			// Pre flush instruction needed for Safari 
			echo str_pad('',1024);
			$this->preFlushNeeded = false;
		}
		echo $s."<br>\n";
		flush();
	}
	
	// Store string in a file with the appropriate permissions
	// -------------------------------------------------------
	public function store($string, $target) {
		$fp = fopen($target, "w");
		fwrite($fp, $string);
		fclose($fp);
		@chmod($target, $this->fileMode);
	}
	
	// Create a folder with the appropriate permissions
	// ------------------------------------------------
	public function createFolder($path) {
		mkdir($path, $this->folderMode);		
	}
}
?>