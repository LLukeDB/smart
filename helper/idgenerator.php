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
 * @package qformat_smart
 * @copyright 2014 Lukas Baumann
 * @author Lukas Baumann
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later (5)
 */

class id_generator {

    private static $instance = null;

    private $idlength = 25;

    private $usedvalues;

    private function __construct() {
        $this->usedvalues = array();
    }

    public static function get_instance() {
        if (self::$instance == null) {
            self::$instance = new id_generator();
        }
        
        return self::$instance;
    }

    private function rand_char() {
        $r = rand(0, 45);
        
        if ($r <= 25) {
            $c = ord('A') + $r;
        } else {
            $c = ord('0') + ($r - 25) % 10;
        }
        
        return chr($c);
    }

    private function generate_unchecked_id() {
        $randid = '';
        for ($i = 0; $i < $this->idlength; $i++) {
            $randid .= $this->rand_char();
        }
        
        return $randid;
    }

    public function generate_id() {
        $id = '';
        do {
            $id = $this->generate_unchecked_id();
        } while (in_array($id, $this->usedvalues));
        
        array_push($this->usedvalues, $id);
        return $id;
    }

}
