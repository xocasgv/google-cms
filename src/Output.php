<?php
class Output {
	private $fileMode;
	private $folderMode;
	private $minFlushNeeded;

	function __construct($fileModeARG, $folderModeARG) {
		$this->fileMode = $fileModeARG;
		$this->folderMode = $folderModeARG;
		$this->minFlushNeeded = true;
	}
	
	public function sout($s) {
		if($this->minFlushNeeded) {
			// Minimum start for Safari flush
			echo str_pad('',1024);
			$this->minFlushNeeded = false;
		}
		echo $s."<br>\n";
		flush(); // Display the page before the end of the script
	}
	
	public function store($data, $target) {
		$fp = fopen($target, "w");
		fwrite($fp, $data);
		fclose($fp);
		@chmod($target, $this->fileMode);
	}

	public function createFolder($path) {
		mkdir($path, $this->folderMode);		
	}
}
?>