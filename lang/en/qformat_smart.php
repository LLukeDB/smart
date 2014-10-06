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

$string['unsupportedquestiontype'] = 'Frage "{$a->questionname}" ({$a->questiontype})';
$string['formatting_error'] = 'Formatting <i>{$a->formatting}</i> in question {$a->questionnum} "{$a->questiontitle}": ';
$string['missingextension'] = 'The following necessary php extension is not installed: {$a}';
$string['filenotreadable'] = 'The file could not be read!';
$string['filenotvalid'] = 'The file is not a valid .notebook-file!';
$string['shortanswer_input'] = 'Pupils insert their answers here';
$string['packingfailed'] = 'File archive could not be created!';
$string['savingfailed'] = 'The file "{$a}" could not be saved!';
$string['loadingfailed'] = 'The file "{$a}" could not be opened!';
$string['parsingfailed'] = 'The text "{$a->text}" of question "{$a->questionname}" could not be parsed!';
$string['noparserfound'] = 'A parser for the textformat "{$a}" could not be found!';
$string['pluginname'] = 'SMART Notebook Format';
$string['pluginname_help'] = 'Plugin for exporting questions in the SMART notebook format.';
$string['pluginname_link'] = 'qformat/smart';

$string['formatting_error'] = 'Question {$a->questionnum} "{$a->questiontitle}": {$a->formatting}';
$string['formatting_log_heading'] = '<p>The following questions and formattings have been omitted because they are not supported:</p>';

$string['a'] = 'hyperlink';
$string['table'] = 'table';
$string['pre'] = 'preformatted text';
$string['address'] = 'address';
$string['h1'] = 'heading';
$string['h2'] = 'heading';
$string['h3'] = 'heading';
$string['h4'] = 'heading';
$string['h5'] = 'heading';
$string['h6'] = 'heading';
$string['sub'] = 'subscript text';
$string['sup'] = 'superscript text';
$string['img'] = 'picture';