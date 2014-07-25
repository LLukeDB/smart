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

class error_logger {

    private static $instance;

    private $errorlog;

    public static function get_instance() {
        if (! self::$instance) {
            self::$instance = new error_logger(null);
        }
        return self::$instance;
    }

    private function __construct($question) {
        $this->errorlog = array();
    }

    public function log_error($errormsg) {
        array_push($this->errorlog, $errormsg);
    }

    public function get_error_log() {
        return $this->errorlog;
    }

}
