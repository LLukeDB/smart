<?php

require_once ($CFG->dirroot . '/question/format/smart/format.php');
require_once ($CFG->dirroot . '/question/format/smart/filetools.php');
require_once ($CFG->dirroot . '/question/format/smart/generator/generator.php');

class settingsxml_generator extends file_generator {

	private static $settingsxml_template = "generator/templates/settings.xml";

	private $xml;

	public function __construct(){
		global $CFG;

		// Load settings.xml-template.
		$filename = $CFG->dirroot . qformat_smart::get_plugin_dir() . settingsxml_generator::$settingsxml_template;
		$this->xml = load_simplexml($filename);
	}

	protected function generate_xml() {
		// Nothing to generate.
	}

	public function save($dir) {
		$this->generate_xml();

		// Write settings.xml to directory.
		$filename = $dir . "settings.xml";
		return save_simplexml($this->xml, $filename);
	}

}