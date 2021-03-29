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
 * @copyright  2020 BeClever - Alio Ochoa Moncalián
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_chalmers\task;

defined('MOODLE_INTERNAL') || die();

class notifications extends \core\task\scheduled_task
{

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('notitask', 'report_chalmers');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        global $CFG;
        $numdays = get_config('chalmers', 'alertdays');
        $alert = false;
        require_once($CFG->dirroot . '/report/chalmers/locallib.php');
        $message = '<ul>';
        for ($i = 1; $i < $numdays + 1; $i++) {
            $date = getdayforwards('+'.$i);
            $data = get_peak_users($date);
            if (count($data) > 0) {
                $message .= '<li>'.date('d-m-Y', $date->getTimestamp()).'<ul>';
                $alert = true;
                foreach ($data as $peak) {

                    $message .= '<li>'.$peak['start'].'-'.$peak['end'].' ---> '.$peak['minusers'];
                    if ($peak['maxusers'] !== $peak['minusers']) {
                        $message .= '-'.$peak['maxusers'];
                    }
                    $message .= ' '.get_string('maxusers', 'report_chalmers');
                    $message .= '<ul>';
                    foreach ($peak['events'] as $event) {
                        $message .= '<li>';
                        if ($event->quizname !== null) {
                            $message .= '<i class="fa fa-check-square-o" aria-hidden="true"></i><a href="'
                            .$CFG->wwwroot.'/mod/quiz/view.php?id='.$event->moduleid.'">'.$event->quizname.'</a>';
                        } else {
                            $message .= '<i class="fa fa-file-text-o" aria-hidden="true"></i><a href="'
                            .$CFG->wwwroot.'/mod/assign/view.php?id='.$event->moduleid.'">'.$event->taskname.'</a>';
                        }
                        $message .= ' --> '.$event->nstudents.' <a href="'.$CFG->wwwroot
                            .'/course/view.php?id='.$event->courseid.'">'.$event->fullname.'</a>';
                        $message .= '</li>';
                    }
                    $message .= '</ul>';
                }
                $message .= '</ul></li>';

            }
        }
        $message .= '</ul>';
        if (get_config('chalmers', 'warningdays') !== 0) {
            $message .= '<div>--------------------------- '.get_string('re-warn', 'report_chalmers').' ---------------------------</div><ul>';
            $peaks = check_warnings();

            foreach ($peaks as $peak) {
                $alert = true;
                $message .= '<li>'.$peak['date'].' - '.$peak['startTime'].'-'.$peak['endTime'].' ---> '.$peak['users'];
                $message .= ' '.get_string('maxusers', 'report_chalmers');
                $message .= '<ul>';
                foreach ($peak['events'] as $event) {
                    $message .= '<li>';
                    if ($event->quizname !== null) {
                        $message .= '<i class="fa fa-check-square-o" aria-hidden="true"></i><a href="'
                            .$CFG->wwwroot.'/mod/quiz/view.php?id='.$event->moduleid.'">'.$event->quizname.'</a>';
                    } else {
                        $message .= '<i class="fa fa-file-text-o" aria-hidden="true"></i><a href="'
                            .$CFG->wwwroot.'/mod/assign/view.php?id='.$event->moduleid.'">'.$event->taskname.'</a>';
                    }
                    $message .= ' --> '.$event->nstudents.' <a href="'.$CFG->wwwroot.'/course/view.php?id='
                        .$event->courseid.'">'.$event->fullname.'</a>';
                    $message .= '</li>';
                }
                $message .= '</ul></li>';
            }
            $message .= '</ul>';
        }

        if ($alert) {
            $users = array();

            $users = array_merge($users, get_system_manager());
            $users = array_merge($users, get_system_admin());

            foreach ($users as $user) {
                $messageid = send_alert_message($user->id, get_string('peakwarning','report_chalmers'), $message);

                echo $messageid;
            }
        }
    }
}
