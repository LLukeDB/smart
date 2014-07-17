<?php

require_once ($CFG->dirroot . '/question/format/smart/format.php');
require_once ($CFG->dirroot . '/question/format/smart/filetools.php');
require_once ($CFG->dirroot . '/question/format/smart/generator/generator.php');


class metadataxml_generator extends file_generator {
	
	private static $metadataxml_template = "generator/templates/metadata.xml";
	
	private $xml;
	
	public function __construct(){
		global $CFG;
		
		// Load metadata.xml-template.
		$filename = $CFG->dirroot . qformat_smart::get_plugin_dir() . metadataxml_generator::$metadataxml_template;
		$this->xml = load_simplexml($filename);
	}
	
	protected function generate_xml() {
		// write current date to metadata.xml
		$date = date("Y-m-d\TH:i:s");
		$this->xml->children('lom', true)->lifeCycle->children('smartgallery', true)->creationdatetime = $date;
	}
	
	public function save($dir) {
		$this->generate_xml();
		
		// Write metadata.xml to directory.
		$filename = $dir . "metadata.xml";
		return save_simplexml($this->xml, $filename);
	}
	
}