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
 * TODO: File description
 *
 * @package    report
 * @subpackage chalmers
 * @copyright  2020 BeClever - Laura Crespo Carreto
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir."/formslib.php");
require_once('locallib.php');

class search_form extends moodleform {
    // Add elements to form.
    public function definition() {
        global $CFG;
        $mform = $this->_form; // Don't forget the underscore!
        $mform->addElement('date_selector', 'searchdate', get_string('displayday','report_chalmers'));
        $this->add_action_buttons(false, get_string('search','report_chalmers'));
    }
    // Custom validation should be added here.
    public function validation($data, $files) {
        global $CFG, $DB;
        $condition;
        $errors = array();
        $found = find_date($data['searchdate']);
        if ($found == 0) {
            $errors['searchdate'] = get_string('errordate','report_chalmers');
        }
        return $errors;
    }
}