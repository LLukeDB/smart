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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Exports questions into the SMART Notebook format.
 *
 * @package qformat_smart
 * @copyright 2014 Lukas Baumann
 * @author Lukas Baumann
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later (5)
 */

require_once ("$CFG->libdir/xmlize.php");
require_once ($CFG->dirroot . '/lib/uploadlib.php');
require_once ($CFG->dirroot . '/question/format/smart/helper/filetools.php');
require_once ($CFG->dirroot . '/question/format/smart/exporter/export.php');
require_once ("$CFG->dirroot/question/format.php");
require_once ($CFG->dirroot . '/question/format/smart/helper/logging.php');
require_once ($CFG->dirroot . '/question/format/smart/generator/generator.php');
require_once ($CFG->dirroot . '/question/format/smart/generator/page_generator.php');
require_once ($CFG->dirroot . '/question/format/smart/importer/import.php');

class qformat_smart extends qformat_default {
	private static $plugin_dir = "/question/format/smart/"; 				// Folder where the plugin is installed, relative to Moodle $CFG->dirroot.
	
	public static function get_plugin_dir() {
		return qformat_smart::$plugin_dir;
	}
	
	/**
	 * @return bool whether this plugin provides export functionality.
	 */
	public function provide_export() {
		return true;
	}
	
	/**
	 * @return bool whether this plugin provides import functionality.
	 */
	public function provide_import() {
		return false;  // Not fully implemented yet.
	}
	
	public function mime_type() {
		return 'application/x-smarttech-notebook';
		//return 'multipart/x-zip';
	}
	
	// EXPORT FUNCTIONS START HERE
	
	/**
	 * @return string file extension
	 */
	function export_file_extension() {
		return ".notebook";
	}
	
	/**
	 * Do any pre-processing that may be required
	 *
	 * @return     	bool success
	 */
	public function exportpreprocess() {
		return true;
	}
	
	/**
	 * Enable any processing to be done on the content
	 * just prior to the file being saved
	 * default is to do nothing
	 *
	 * @param	string output text
	 *        	
	 * @param	string processed output text
	 *        	
	 */
	protected function presave_process($content) {
		return $content;
	}
	
	/**
	 * Do the export
	 * For most types this should not need to be overrided
	 *
	 * @return stored_file
	 */
	public function exportprocess() {
		global $CFG, $OUTPUT, $DB, $USER;

		// get the questions (from database) in this category
		// only get q's with no parents (no cloze subquestions specifically)
		if ($this->category) {
			$questions = get_questions_category ( $this->category, true );
		} else {
			$questions = $this->questions;
		}
		
		// Check if the neccessary extensions are loaded.
		if(!extension_loaded('imagick')) {
			$error_msg_params = "Imagick";
			print_error('missingextension', 'qformat_smart', $this->get_continue_path(), $error_msg_params);
		}
		
		// Export the questions.
		$filecontent = $this->export_questions($questions);
		
		// Start the download of the export file.
		$this->start_download($filecontent);
	}
	
	public function get_continue_path() {
		$course = $this->course;
		$continuepath = "$CFG->wwwroot/question/export.php?courseid=$course->id";
		return $continuepath;
	}
	
	public function export_questions($questions) {
	    
	    $exporters = array();
	    $unsupported_questions = array();
	    
	    foreach($questions as $question) {
	        $exporter = qtype_exporter_factory::get_exporter($question);
	    
	        if(!$exporter) {
	            array_push($unsupported_questions, $question);
	        }
	        else {
	            array_push($exporters, $exporter);
	        }
	    }
	    
	    // Create error message if there are unsuported questions.
	    if(count($unsupported_questions) > 0) {
	        // Create string list with unsupported questions.
	        $error_msg_params = "";
	        foreach($unsupported_questions as $question) {
	            $error_msg_params .= " - " . $question->name . " [" . get_string($question->qtype, 'quiz') . "]<br/>";
	        }
	        	
	        print_error('unsupportedquestiontype', 'qformat_smart', $this->get_continue_path(), $error_msg_params);
	    }
	    
	    // Export all questions.
	    $export_data = new export_data();
	    foreach ( $exporters as $exporter ) {
	        $exporter->export($export_data);
	    }
	    
	    // Export logged errors.
	    $log = error_logger::get_instance()->get_error_log();
	    if(count($log) > 0) {
	        $dummy_question=new stdClass();
	        $dummy_question->qtype='log';
	        $exporter = qtype_exporter_factory::get_exporter($dummy_question);
	        $exporter->export($export_data);
	    }
	    
	    // Create zip-file from export_data.
	    $zip_file = $export_data->toZIP();
	    
	    // Return the zip file.
	    $filehandle = fopen($zip_file, "r");
	    $filecontent = fread($filehandle, filesize($zip_file));
	    fclose($filehandle);
	    unlink($zip_file);
	    
	    return $filecontent;
	}
	
	/**
	 * Do an post-processing that may be required
	 *
	 * @return bool success
	 */
	protected function exportpostprocess() {
		return true;
	}
	
	private function start_download($filecontent) {
		$name = "quiz-" . $this->course->shortname . "-" . $this->category->name . "-" . $date = date("Ymd-Hi") . ".notebook";
		$name = str_replace(" ", "_", $name);
		$encoding = "BASE64";
		
		header('Content-Description: File Transfer');
		header('Content-Type: '. $this->mime_type());
		header('Content-Disposition: attachment; filename='. $name);
		header('Content-Transfer-Encoding: '.$encoding);
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		ob_clean();
		flush();
		echo $filecontent;
		die();
	}
}


