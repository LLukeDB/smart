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
 * Unit tests for the SMART Notebook format.
 *
 * @package qformat_smart
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once ($CFG->libdir . '/questionlib.php');
require_once ($CFG->dirroot . '/question/format/xml/format.php');
require_once ($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once ($CFG->dirroot . '/question/format/smart/importer/import.php');

class qformat_smart_test extends question_testcase {

    public function assert_same_xml($expectedxml, $xml) {
        $this->assertEquals(str_replace("\r\n", "\n", $expectedxml), str_replace("\r\n", "\n", $xml));
    }

    public function zip_to_import_data($zipcontent) {
        global $CFG;
        
        $moodletmpdir = $CFG->dataroot . "/temp/";
        $tmpfile = tempnam($moodletmpdir, 'smart_');
        
        $handle = fopen($tmpfile, "w");
        fwrite($handle, $zipcontent);
        fclose($handle);
        
        $import_data = new import_data($tmpfile);
        unlink($tmpfile);
        
        return $import_data;
    }

    public function make_test_question() {
        global $USER;
        $q = new stdClass();
        $q->id = 0;
        $q->contextid = 0;
        $q->category = 0;
        $q->parent = 0;
        $q->questiontextformat = FORMAT_HTML;
        $q->generalfeedbackformat = FORMAT_HTML;
        $q->defaultmark = 1;
        $q->penalty = 0.3333333;
        $q->length = 1;
        $q->stamp = make_unique_id_code();
        $q->version = make_unique_id_code();
        $q->hidden = 0;
        $q->timecreated = time();
        $q->timemodified = time();
        $q->createdby = $USER->id;
        $q->modifiedby = $USER->id;
        return $q;
    }

    public function test_export_truefalse() {
        // Create moodle question object.
        $qdata = new stdClass();
        $qdata->id = 12;
        $qdata->contextid = 0;
        $qdata->qtype = 'truefalse';
        $qdata->name = 'True false question';
        $qdata->questiontext = 'The answer is true.';
        $qdata->questiontextformat = FORMAT_HTML;
        $qdata->generalfeedback = 'General feedback: You should have chosen true.';
        $qdata->generalfeedbackformat = FORMAT_HTML;
        $qdata->defaultmark = 1;
        $qdata->length = 1;
        $qdata->penalty = 1;
        $qdata->hidden = 0;
        
        $qdata->options = new stdClass();
        $qdata->options->answers = array(
                1 => new question_answer(1, 'True', 1, 'Well done!', FORMAT_HTML),
                2 => new question_answer(2, 'False', 0, 'Doh!', FORMAT_HTML)
        );
        $qdata->options->trueanswer = 1;
        $qdata->options->falseanswer = 2;
        
        // Export the question.
        $exporter = new qformat_smart();
        $exporter->questions = array(
                $qdata
        );
        $zipcontent = $exporter->exportprocess();
        
        // Open exported question as import_data.
        $import_data = $this->zip_to_import_data($zipcontent);
        
        // Test file content.
        $this->assertCount(1, $import_data->pages);
    }

}
