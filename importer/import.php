<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package qformat_smart
 * @copyright 2014 Lukas Baumann
 * @author Lukas Baumann
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later (5)
 */

require_once ($CFG->dirroot . '/question/format/smart/helper/logging.php');
require_once ($CFG->dirroot . '/question/format/smart/generator/page_generator.php');
require_once ($CFG->dirroot . '/question/format/smart/helper/filetools.php');
require_once ($CFG->dirroot . '/question/format/smart/question/question.php');
require_once ($CFG->dirroot . '/question/format/smart/helper/idgenerator.php');
require_once ($CFG->dirroot . '/question/format/smart/exporter/export.php');
require_once ($CFG->dirroot . '/question/format/smart/text/parse.php');

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

