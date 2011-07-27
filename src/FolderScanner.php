<?php
class FolderScanner {
	private $connection;
	private $lastUpdateDate;
	private $output;
	private $pageDownloader;

	public function __construct(Connection $connectionARG, Output $outputARG, $lastUpdateDateARG, PageDownloader $pageDownloaderARG) {
		$this->connection = $connectionARG;
		$this->lastUpdateDate = (string) $lastUpdateDateARG;
		$this->output = $outputARG;
		$this->pageDownloader = $pageDownloaderARG;
	}

	// Scan a folder recursively and download new objects
	// --------------------------------------------------
	public function scan($url, $currentFolderDepth) {
		// debug : echo getRequest("https://docs.google.com/feeds/default/private/full?prettyprint=true");
		$folderContent = simplexml_load_string(str_replace('gd:etag', 'etag', $this->connection->getRequest($url))); //."?prettyprint=true"
		$foldersArray = array();
		$pagesArray = array();

		// foldersArray: key:name => value:sub-folder
		// pagesArray: key:menu_position => value:name
		foreach($folderContent->entry as $file) {
			$type = (string)($file->content['type']);
			$name = (string)($file->title);
			$name = str_replace('"', '\"', str_replace('$', '\$', $name));
			$srcUrl = (string)($file->content['src']);
			$lastModified = (string)($file->updated);
			$etag = (string)($file['etag']);
			$path = $currentFolderDepth.($currentFolderDepth == '' ? '' : '/').StringTools::urlFormat(StringTools::indexClean($name));
			$isNew = !file_exists($path) || $this->lastUpdateDate < $lastModified;

			if($type == 'application/atom+xml;type=feed') {
				if(!is_dir($path)) {
					$this->output->createFolder($path);
					$this->output->sout("Folder: $path");
				}
				// Recursively store the sub folder.
				$foldersArray[$name.'!$!'] = $this->scan($srcUrl, $path);
			} else if(substr($srcUrl, 0, 23) == 'https://docs.google.com') {
				$pagesArray[] = $name.'!$!'; // !$! is a end of name protectio removed in StringTools::serializeForInclude(), i know that's not very nice..
				if($isNew) {
					$this->output->sout("Page: $path");
					$this->pageDownloader->download($srcUrl, $etag, $path.'.php');
				}
			} else if($isNew) {
				$this->output->store($this->connection->getRequest($srcUrl), $path);
				$this->output->sout("File: $path");
			}
		}

		// Sort folders and pages by name
		ksort($foldersArray);
		sort($pagesArray);
		// Remove indexes
		foreach ($foldersArray as $key => $value) {
			$cleanKey = StringTools::indexClean($key);
			if($key != $cleanKey) {
				$foldersArray[$cleanKey] = $foldersArray[$key];
				unset($foldersArray[$key]);
			}
		}
		foreach ($pagesArray as $key => $value) {
			$cleanValue = StringTools::indexClean($pagesArray[$key]);
			$pagesArray[$key] = $cleanValue;
		}
		return array('folders' => $foldersArray, 'pages' => $pagesArray);
	}
}
?>