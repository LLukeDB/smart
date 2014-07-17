<?php
require_once ($CFG->dirroot . '/question/format/smart/filetools.php');
require_once ($CFG->dirroot . '/question/format/smart/importer/import.php');

class import_data {

	public $pages;
	public $metadataxml;
	public $settingsxml;
	public $imsmanifest;
	public $metadatardf;

	public function __construct($filename) {
		$this->pages = array();
		$this->load_from_zip($filename);
	}
	
	private function load_from_zip($filename) {
		$zip = zip_open($filename);
		
		if ($zip) {
			while ($zip_entry = zip_read($zip)) {
				$name = zip_entry_name($zip_entry);
				
				$buf = "";
				if (zip_entry_open($zip, $zip_entry, "r")) {
					$buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
					zip_entry_close($zip_entry);
				}
				
				if(preg_match("/^page\d+\.svg$/", $name)) {
					$page = simplexml_load_string($buf);
					array_push($this->pages, $page);
				}
				else if(preg_match("/^metadata\.xml$/", $name)) {
					$this->metadataxml = simplexml_load_string($buf);
				}
				else if(preg_match("/^settings\.xml$/", $name)) {
					$this->settingsxml = simplexml_load_string($buf);
				}
				else if(preg_match("/^imsmanifest\.xml$/", $name)) {
					$this->imsmanifest = simplexml_load_string($buf);
				}
				else if(preg_match("/^metadata\.rdf$/", $name)) {
					$this->metadatardf = simplexml_load_string($buf);
				}
			}
			zip_close($zip);
			
			$this->validate();
		
		}
		else {
			print_error("filenotreadable", "qtype_smart");
		}
	}
	
	private function validate() {
		$valid = true;
		
		if(count($this->pages) == 0) {
			$valid = false;
		}
		
		if($this->metadatardf == null) {
			$valid = false;
		}
		
		if($this->imsmanifest == null) {
			$valid = false;
		}
		
		if(!$valid) {
			print_error("filenotvalid", "qtype_smart");
		}
	}

}

