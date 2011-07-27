<?php
class Output {
	private $fileMode;
	private $folderMode;
	private $minFlushNeeded;
	
	public function __construct($fileModeARG, $folderModeARG) {
		$this->fileMode = $fileModeARG;
		$this->folderMode = $folderModeARG;
		$this->minFlushNeeded = true;
	}
	
	// Print a String and a line break
	// -------------------------------
	public function sout($s) {
		if($this->minFlushNeeded) {
			// Minimum start for Safari flush
			echo str_pad('',1024);
			$this->minFlushNeeded = false;
		}
		echo $s."<br>\n";
		flush(); // Display the page before the end of the script
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