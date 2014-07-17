<?php
require_once ($CFG->dirroot . '/question/format/smart/logging.php');
require_once ($CFG->dirroot . '/question/format/smart/generator/page_generator.php');
require_once ($CFG->dirroot . '/question/format/smart/filetools.php');
require_once ($CFG->dirroot . '/question/format/smart/generator/question.php');
require_once ($CFG->dirroot . '/question/format/smart/idgenerator.php');
require_once ($CFG->dirroot . '/question/format/smart/exporter/export.php');

class export_data {

	public $pages;		// array of page_generators
	public $metadataxml_generator;
	public $settingsxml_generator;
	public $imsmanifest_generator;
	public $metadatardf_generator;

	public function __construct() {
		$this->pages = array();
		$this->settingsxml_generator = new settingsxml_generator();
		$this->metadataxml_generator = new metadataxml_generator();
		$this->metadatardf_generator = new metadatardf_generator();
		$this->imsmanifest_generator = new imsmanifest_generator();
	}

	public function add_page($page) {
		array_push($this->pages, $page);
	}

	public function toZIP() {
		global $CFG;

		// Create temporary directory for data.
		$moodletmpdir = $CFG->dataroot . "/temp/";
		$tmpdir = tempdir($moodletmpdir, "smart_");
		//createDirStructure($tmpdir);

		// Write data to temporary directory.
		$this->settingsxml_generator->save($tmpdir);
		$this->metadataxml_generator->save($tmpdir);
		$this->imsmanifest_generator->save($tmpdir);
		$this->metadatardf_generator->save($tmpdir);
		foreach ($this->pages as $page) {
			$page->save($tmpdir);
		}

		// Create zip file from temporary directory.
		$tmpfile = tempnam($moodletmpdir, 'smart_');
		create_zip($tmpdir, $tmpfile);
		//recurseRmdir($tmpdir);	// Commented out for development.

		return $tmpfile;
	}
}

