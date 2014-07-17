<?php

/**
 * Creates zip-file from the directory.
 *
 * @param string $dir
 * @param string $file
 */
function create_zip($dir, $file) {
	$zipArchive = new ZipArchive ();
	if ($zipArchive->open ($file, ZipArchive::OVERWRITE )) {
		addFolderToZip ($dir, $zipArchive);
		$res = $zipArchive->close ();
		if ($res === false) {
			// TODO Error handling.
		}
	} else {
		// TODO Error handling.
	}
}

/**
 * Function to recursively add a directory,
 * sub-directories and files to a zip archive
 */
function addFolderToZip($dir, $zipArchive, $zipdir = '') {
	if(is_dir ($dir)) {
		if($dh = opendir($dir)) {
				
			// Add the directory
			if (!empty($zipdir)) {
				$zipArchive->addEmptyDir($zipdir);
			}
				
			// Loop through all the files
			while(($file = readdir($dh)) !== false) {

				// If it's a folder, run the function again!
				if (! is_file($dir . $file)) {
					// Skip parent and root directories
					if (($file !== ".") && ($file !== "..")) {
						addFolderToZip( $dir . $file . "/", $zipArchive, $zipdir . $file . "/");
					}
				} else {
					// Add the files
					$zipArchive->addFile ($dir . $file, $zipdir . $file);
				}
			}
		}
	}
}

  /**
   * Creates a temporary directory.
   * 
   * @param unknown $dir
   * @param string $prefix
   * @param number $mode
   * @return string
   */
  function tempdir($dir, $prefix='', $mode=0777)
  {
    $tmpfile = tempnam($dir, 'smart_');
    unlink($tmpfile);
    $tmpfile = $tmpfile . "/";
    mkdir($tmpfile);
    
    return $tmpfile;
  }

  /**
   * Creates the directory structure for the notebook format.
   * 
   * @param string $dir
   * @return string
   */
  function createDirStructure($basedir) {
	mkdir($basedir . '/pictures');
	//mkdir($basedir . '/flash');
	//mkdir($basedir . '/files');
	return $basedir;
  }
  
  /**
   * Deletes directory with all files and subdirectories in it.
   * 
   * @param unknown $dir
   * @return boolean
   */
  function recurseRmdir($dir) {
  	$files = array_diff(scandir($dir), array('.','..'));
  
  	foreach ($files as $file) {
  		(is_dir("$dir/$file")) ? recurseRmdir("$dir/$file") : unlink("$dir/$file");
  	}
  
  	return rmdir($dir);
  }
  
  function save_simplexml($simplexml, $file) {
  	$dom = new DOMDocument("1.0");
  	$dom->preserveWhiteSpace = false;
  	$dom->formatOutput = true;
  	$dom->loadXML($simplexml->asXML());
  	$result = $dom->save($file);
  	
  	//$result = $simplexml->asXML($file);
  	if(!$result) {
  		// TODO: error handling
  		return false;
  	}
  	return true;
  }
  
  function load_simplexml($file) {
  	if (file_exists($file)) {
	  	$xml_doc = simplexml_load_file($file);
		return $xml_doc;
  	}
  	else {
  		// TODO: error handling
  	}
  	
  }
  
  function save_domdocument($domdocument, $file) {
  	$domdocument->formatOutput = true;
  	$result = $domdocument->save($filename);
  	if(!$result) {
  		// TODO: error handling
  	}
  }
  
  function load_domdocument($file) {
  	$xml_doc = new DOMDocument();
  	$result = $xml_doc->load($filename);
  	if(!$result) {
  		// TODO: error handling
  	}
  	return $xml_doc;
  }
  
?>
