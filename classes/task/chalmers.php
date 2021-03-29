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

namespace report_chalmers\task;

defined('MOODLE_INTERNAL') || die();

class chalmers extends \core\task\scheduled_task
{

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('task', 'report_chalmers');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        global $CFG;
        require_once($CFG->dirroot . '/report/chalmers/locallib.php');
        $alltoinsert = get_table_insertion_data();
        $toinster = array();
        $toupdate = array();
        foreach ($alltoinsert as $insertion) {
            $id = is_it_already_there($insertion);
            if ($id == null) {
                $insertion->reportaddingdate = get_todays_timestamp();
                $insertion->reportlastupdate = get_todays_timestamp();
                $toinster[] = $insertion;
            } else {
                $insertion->id = $id;
                if (needs_to_be_updated($insertion)) {
                    $insertion->reportlastupdate = get_todays_timestamp();
                    $toupdate[] = $insertion;
                }
            }
        }
        if (count($toinster) > 0) {
            insert_new_rows($toinster);
        }
        if (count($toupdate) > 0) {
            update_rows($toupdate);
        }
    }
}
