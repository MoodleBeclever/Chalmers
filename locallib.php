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
 * @subpackage user_statistics
 * @copyright  2020 BeClever - Laura Crespo Carreto
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Old way of achieving the course participant count.
function get_course_student_number ($courseid) {
    global $DB;
    $sql = "SELECT COUNT(*) AS participant
        FROM (SELECT DISTINCT ue.userid, e.courseid FROM {user_enrolments} ue, {enrol} e, {course} c
        WHERE ue.enrolid = e.id AND e.courseid=? AND c.id = e.courseid AND c.visible = 1) total;";
    $participants = $DB->get_record_sql($sql, [$courseid]);
    return $participants->participant;
}

// New way of achieving the course participant count.
function get_course_student_number2 ($courseid) {
    global $DB;
    $sql = "SELECT COUNT(*) AS participant
        FROM (SELECT DISTINCT ra.userid, c.instanceid from {role_assignments}
        ra inner join {context} c on ra.contextid=c.id where c.instanceid= ? and ra.roleid = 5) total;";
    $participants = $DB->get_record_sql($sql, [$courseid]);
    return $participants->participant;
}
// This function gets the fullname of a course when an id is provided.
function get_course_name ($id) {
    global $DB;
    $course = $DB->get_record('course', array('id' => $id), 'fullname');
    return $course->fullname;
}
// This function gets the idnumber of a course when an id is provided.
function get_course_idnumber ($id) {
    global $DB;
    $course = $DB->get_record('course', array('id' => $id), 'idnumber');
    return $course->idnumber;
}
// This function gets the name of a task when an id and the type is provided.
function get_taskquiz_name ($id, $examtype) {
    global $DB;
    if ($examtype == 'task') {
        $examtype = 'assign';
    }
    $name = $DB->get_record($examtype, array('id' => $id), 'name');
    return $name->name;
}
// This function gets the data needed to populate the table at the db with the correct format.
function get_table_insertion_data () {
    global $DB;
    $allinsertions = array();
    $quizid = get_moduleid("quiz");
    $startdate = new DateTime ('now', core_date::get_server_timezone_object());
    $enddate = new DateTime ('+30 day', core_date::get_server_timezone_object());
    $sql = "SELECT Q.id AS quizid, Q.timeopen AS startdate, Q.timeclose AS enddate, Q.timelimit AS timelimit, Q.course AS courseid, 
        CM.id AS moduleid, CM.added AS modulecreationdate
    FROM {quiz} Q INNER JOIN {course_modules} CM ON Q.id = CM.instance
    WHERE Q.course = CM.course AND CM.module = ".$quizid." AND Q.timeopen BETWEEN ? AND  ?;";
    $quizzes = $DB->get_records_sql($sql, [$startdate->getTimestamp(), $enddate->getTimestamp()]);
    foreach ($quizzes as $quiz) {
        $qinstertion = new \stdClass();
        $qinstertion->type = 'quiz';
        $qinstertion->quizid = $quiz->quizid;
        $qinstertion->startdate = $quiz->startdate;
        $qinstertion->enddate = $quiz->enddate;
        $qinstertion->timelimit = $quiz->timelimit;
        $qinstertion->courseid = $quiz->courseid;
        $qinstertion->moduleid = $quiz->moduleid;
        $qinstertion->modulecreationdate = $quiz->modulecreationdate;
        $qinstertion->nstudents = get_course_student_number2($quiz->courseid);

        $allinsertions[] = $qinstertion;
    }
    $assignid = get_moduleid("assign");
    $sql = "SELECT A.id AS taskid, A.allowsubmissionsfromdate AS startdate, A.duedate AS enddate, A.course AS courseid, 
        CM.id AS moduleid, CM.added AS modulecreationdate
        FROM {assign} A INNER JOIN {course_modules} CM ON A.id = CM.instance
        WHERE A.course = CM.course AND CM.module = ".$assignid." AND A.allowsubmissionsfromdate BETWEEN ? AND ?;";
    $tasks = $DB->get_records_sql($sql, [$startdate->getTimestamp(), $enddate->getTimestamp()]);
    foreach ($tasks as $task) {
        $tinstertion = new \stdClass();
        $tinstertion->type = 'task';
        $tinstertion->taskid = $task->taskid;
        $tinstertion->startdate = $task->startdate;
        $tinstertion->enddate = $task->enddate;
        $tinstertion->courseid = $task->courseid;
        $tinstertion->moduleid = $task->moduleid;
        $tinstertion->modulecreationdate = $task->modulecreationdate;
        $tinstertion->nstudents = get_course_student_number2($task->courseid);

        $allinsertions[] = $tinstertion;
    }
    return $allinsertions;

}
// This function gets the current date and transforms it to timestamp.
function get_todays_timestamp() {
     $now = new DateTime('now', core_date::get_server_timezone_object());
     return $now->getTimestamp();
}
// This function checks if the quiz or task is already registered on the db by providing an object.
function is_it_already_there ($exam) {
    global $DB;
    if ($exam->type == 'quiz') {
        $id = $DB->get_record('report_chalmers_exams',
        array ('quizid' => $exam->quizid, 'courseid' => $exam->courseid, 'moduleid' => $exam->moduleid), 'id');
    } else {
        $id = $DB->get_record('report_chalmers_exams',
        array ('taskid' => $exam->taskid, 'courseid' => $exam->courseid, 'moduleid' => $exam->moduleid), 'id');
    }
    return $id->id;
}
// This function checks if the info giving is equal to the db info and returns a boolean.
function needs_to_be_updated ($exam) {
    global $DB;
    $info = $DB->get_record('report_chalmers_exams', array('id' => $exam->id), 'startdate, enddate, timelimit, nstudents');
    if ($info->startdate == $exam->startdate && $info->enddate == $exam->enddate
    && $info->nstudents == $exam->nstudents && ($exam->type == 'task' || $info->timelimit == $exam->timelimit)) {
        return false;
    } else {
        return true;
    }
}
// This function extract all the information of the db within the next 30 days.
function get_all_chalmers_data () {
    global $DB;
    $now = new DateTime ('now', core_date::get_server_timezone_object());
    $today = new DateTime ($now->format('y-m-d'));
    $today->setTimezone(core_date::get_server_timezone_object());
    $sql = "SELECT * FROM {report_chalmers_exams} WHERE startdate >= ? ORDER BY startdate;";
    $alldata = $DB->get_records_sql($sql, [$today->getTimestamp()]);
    return $alldata;

}
// This function extract the quizes within a time if a date is provided it
// will return the quizes for the day if not it will return the quizes within the next 30 days.
function get_all_chalmers_quiz ($date = null) {
    global $DB;
    $alldata;
    if ($date == null) {
        $now = new DateTime ('now', core_date::get_server_timezone_object());
        $date = new DateTime ($now->format('y-m-d'));
        $sql = "SELECT * FROM {report_chalmers_exams} WHERE startdate >= ? AND taskid IS NULL;";
        $alldata = $DB->get_records_sql($sql, [$date->getTimestamp()]);
    } else {
        $date->setTime(00, 00, 00);
        $startdaystamp = $date->getTimestamp();
        $date->setTime(23, 59, 59);
        $enddaystamp = $date->getTimestamp();
        $sql = "SELECT * FROM {report_chalmers_exams} WHERE startdate >= ? AND startdate <= ? AND taskid IS NULL;";
        $alldata = $DB->get_records_sql($sql, [$startdaystamp, $enddaystamp]);
    }
    return $alldata;

}

  // This function will extract all data within the day provided.
function find_date ($fecha) {
    global $DB;
    $date = new DateTime("@$fecha");
    $date->setTimezone(core_date::get_server_timezone_object());

    $date->setTime(00, 00, 00);
    $startdaystamp = $date->getTimestamp();
    $date->setTime(23, 59, 59);
    $enddaystamp = $date->getTimestamp();
    $sql = "SELECT id FROM {report_chalmers_exams} WHERE startdate >= ? AND startdate <= ?;";
    $alldata = $DB->get_records_sql($sql, [$startdaystamp, $enddaystamp]);
    return count($alldata);
}

  // This function returns the labels for the second chart of the report.
function get_daylabels() {
    $hourlabels = array();
    $hourlabels[] = '00:00';
    $hourlabels[] = '00:30';
    $hourlabels[] = '01:00';
    $hourlabels[] = '01:30';
    $hourlabels[] = '02:00';
    $hourlabels[] = '02:30';
    $hourlabels[] = '03:00';
    $hourlabels[] = '03:30';
    $hourlabels[] = '04:00';
    $hourlabels[] = '04:30';
    $hourlabels[] = '05:00';
    $hourlabels[] = '05:30';
    $hourlabels[] = '06:00';
    $hourlabels[] = '06:30';
    $hourlabels[] = '07:00';
    $hourlabels[] = '07:30';
    $hourlabels[] = '08:00';
    $hourlabels[] = '08:30';
    $hourlabels[] = '09:00';
    $hourlabels[] = '09:30';
    $hourlabels[] = '10:00';
    $hourlabels[] = '10:30';
    $hourlabels[] = '11:00';
    $hourlabels[] = '11:30';
    $hourlabels[] = '12:00';
    $hourlabels[] = '12:30';
    $hourlabels[] = '13:00';
    $hourlabels[] = '13:30';
    $hourlabels[] = '14:00';
    $hourlabels[] = '14:30';
    $hourlabels[] = '15:00';
    $hourlabels[] = '15:30';
    $hourlabels[] = '16:00';
    $hourlabels[] = '16:30';
    $hourlabels[] = '17:00';
    $hourlabels[] = '17:30';
    $hourlabels[] = '18:00';
    $hourlabels[] = '18:30';
    $hourlabels[] = '19:00';
    $hourlabels[] = '19:30';
    $hourlabels[] = '20:00';
    $hourlabels[] = '20:30';
    $hourlabels[] = '21:00';
    $hourlabels[] = '21:30';
    $hourlabels[] = '22:00';
    $hourlabels[] = '22:30';
    $hourlabels[] = '23:00';
    $hourlabels[] = '23:30';
     return $hourlabels;
}
 // This function will format the data for the second chart of the report.
function convert_to_daychartvalues ($allvalues, $hourlabels, $searchdate) {
    $result = array();
    foreach ($hourlabels as $label) {
        $result[] = '0;id';
    }
    foreach ($allvalues as $values) {
        $checkdate = new DateTime ("@$values->startdate");
        $checkdate->setTimezone(core_date::get_server_timezone_object());
        if ($checkdate->format('d-m-Y') == $searchdate->format('d-m-Y')) {
            $hour = $checkdate->format('H');
            $min = $checkdate->format('i');
            if ($min >= 30) {
                $labelsearch = $hour . ':' . '30';
            } else {
                $labelsearch = $hour . ':' . '00';
            }
            $found = array_search($labelsearch, $hourlabels);
            if ($hourlabels[0] == $labelsearch) {
                $result[$found] = $result[$found].'/'.$values->nstudents.';'.$values->courseid;
            } else if ($found != false) {
                $result[$found] = $result[$found].'/'.$values->nstudents.';'.$values->courseid;
            }
        }
    }
    $return = array();
    foreach ($result as $toconvertunique) {
        $eachone = array();
        if ($toconvertunique == '0;id') {
            $return[] = 0;
        } else {
            $eachone = explode('/', $toconvertunique);
            $coursesunique = array();
            $nstudents;
            foreach ($eachone as $exam) {
                $part = array();
                $part = explode(';', $exam);
                if (count($coursesunique) == 0) {
                    $nstudents = $part[0];
                    $coursesunique[] = $part[1];
                } else {
                    $key = array_search($part[1], $coursesunique);
                    if ($key == false) {
                        $nstudents = $nstudents + $part[0];
                        $coursesunique[] = $part[1];
                    }
                }
            }
            $return[] = $nstudents;
        }
    }
    return $return;
}
 // This function will format the data for the first chart of the report.
function convert_to_chartvalues ($values, $labels) {
    $resultvalues = array();
    $i = 0;
    foreach ($labels as $label) {
        $resultvalues[$i] = 0;
        $i++;
    }
    foreach ($values as $value) {
        $examstartdate = new DateTime("@$value->startdate");
        $examstartdate->setTimezone(core_date::get_server_timezone_object());
        $key = $examstartdate->format('d').'-'.$examstartdate->format('m');
        $found = array_search($key, $labels);
        if ($labels[0] == $key) {
            $resultvalues[$found]++;
        } else if ($found != false) {
            $resultvalues[$found]++;
        }
    }
    return $resultvalues;
}
  // This function will provide the labels for the first chart of the report.
function get_month_days ($date) {
    $labels = array();
    $month = $date->format('m');
    $monthdays = cal_days_in_month(CAL_GREGORIAN, $month, $date->format('Y'));
    $day = $date->format('d');
    $i = 0;
    while ($i < 30) {
        if ($day <= $monthdays) {
            if (strlen($day) == 1) {
                $day = '0' . $day;
            }
            if (strlen($month) == 1) {
                $month = '0' . $month;
            }
            $labels[] = $day.'-'.$month;
            $day++;
            $i++;
        } else {
            $day = 1;
            if ($month == 12) {
                $month = 1;
                $year = $date->format('Y');
                $year++;
            } else {
                $month++;
                $year = $date->format('Y');
            }
            $monthdays = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        }
    }
    return $labels;
}
 // This function will extrat all the task, if a date is provided it
 // will retrieve the task of the day if not it will provide the task within the next 30 days.
function get_all_chalmers_task ($date = null) {
    global $DB;
    $alldata;
    if ($date == null) {
        $now = new DateTime ('now', core_date::get_server_timezone_object());
        $date = new DateTime ($now->format('y-m-d'));
        $sql = "SELECT * FROM {report_chalmers_exams} WHERE startdate >= ? AND quizid IS NULL;";
        $alldata = $DB->get_records_sql($sql, [$date->getTimestamp()]);
    } else {
        $date->setTime(00, 00, 00);
        $startdaystamp = $date->getTimestamp();
        $date->setTime(23, 59, 59);
        $enddaystamp = $date->getTimestamp();
        $sql = "SELECT * FROM {report_chalmers_exams} WHERE startdate >= ? AND startdate <= ? AND quizid IS NULL;";
        $alldata = $DB->get_records_sql($sql, [$startdaystamp, $enddaystamp]);
    }
    return $alldata;
}
// This function insert on the db the object provided.
function insert_new_rows($exams) {
    global $DB;
    foreach ($exams as $exam) {
        unset($exam->type);
        $DB->insert_record('report_chalmers_exams', $exam, false);
    }
}
// Update the db table report_chalmers_exams with the provided object.
function update_rows($exams) {
    global $DB;
    foreach ($exams as $exam) {
        unset($exam->type);
        $DB->update_record('report_chalmers_exams', $exam);
    }
}
// This function gets the email of the teachers within the course provided.
function get_teachersemails_onview($courseid) {
    global $DB;
    $sql = 'SELECT u.email AS email
        FROM {role_assignments} ra INNER JOIN {context} c ON ra.contextid=c.id INNER JOIN {user} u ON ra.userid=u.id 
        WHERE c.instanceid=? AND ra.roleid=3;';
    $teachers = $DB->get_records_sql($sql, [$courseid]);
    $result;
    $i = count($teachers);
    foreach ($teachers as $teacher) {
        if ($i == count($teachers)) {
            $result = $teacher->email;
        } else {
            $result = $result . '; ' . $teacher->email;
        }
        $i--;
    }
    return $result;
}
// This function gets the email of the teachers within the course provided.
function get_teachersemails($courseid) {
    global $DB;
    $sql = 'SELECT u.email AS email
        FROM {role_assignments} ra INNER JOIN {context} c ON ra.contextid=c.id INNER JOIN {user} u ON ra.userid=u.id 
        WHERE c.instanceid=? AND ra.roleid=3;';
    $teachers = $DB->get_records_sql($sql, [$courseid]);
    $result;
    $i = count($teachers);
    foreach ($teachers as $teacher) {
        if ($i == count($teachers)) {
            $result = $teacher->email;
        } else {
            $result = $result . ';' . $teacher->email;
        }
        $i--;
    }
    return $result;
}
// This function gets the attemp limit of a quiz.
function get_quiz_attemps ($id) {
    global $DB;
    $attempt = $DB->get_record('quiz', array('id' => $id), 'attempts');
    return $attempt->attempts;
}
// This function gets the attempt limit of a task.
function get_task_attemps ($id) {
    global $DB;
    $attempt = $DB->get_record('assign', array('id' => $id), 'attemptreopenmethod, maxattempts');
    if ($attempt->attemptreopenmethod == 'none') {
        return 1;
    }

    return (int) $attempt->maxattempts;
}
/*
function get_task_attemps ($id) {
    global $DB;
    $attempt = $DB->get_record('assign', array('id' => $id), 'attemptreopenmethod, maxattempts');
    if ($attempt->maxattempts == '-1') {
        $attempt->maxattempts = 'sin limite de';
    }
    return 'Reapertura '.$attempt->attemptreopenmethod.' '.$attempt->maxattempts.' intento';
}
*/

// This function get the day +the number given.
function getdayforwards($day) {
    $datetime = new DateTime();
    $datetime->modify($day.' day');

    return $datetime;
}

// This function constructs the alert message for the user peaks within the day configurated.
function get_peak_users($date) {
    global $DB;
    $numstudients = get_config('chalmers', 'alertnumusers');

    $alldata = array();
    for ($i = 0; $i < 24; $i++) {
        $date->setTime($i, 00, 00);
        $startdaystamp = $date->getTimestamp();
        $date->setTime($i, 59, 59);
        $enddaystamp = $date->getTimestamp();
        $sql = "SELECT count(DISTINCT u.id) AS numUsers FROM {user} u
            JOIN {user_enrolments} ue ON ue.userid = u.id
            JOIN {enrol} e ON e.id = ue.enrolid
            JOIN {report_chalmers_exams} ch ON ch.courseid = e.courseid
            WHERE ( (ch.startdate BETWEEN ? AND ? AND ch.quizid IS NOT NULL) OR ((ch.startdate BETWEEN ? AND ?)
            OR (ch.enddate BETWEEN ? AND ?)) AND ch.taskid IS NOT NULL) AND ue.status=0;";
        $users = $DB->get_records_sql($sql, [$startdaystamp, $enddaystamp ,
            $startdaystamp, $enddaystamp, $startdaystamp, $enddaystamp]);
        $users = reset($users);

        $sql = "SELECT ch.*,c.fullname,a.name AS taskname,q.name AS quizname, cat.id categoryid FROM mdl_report_chalmers_exams ch
            JOIN mdl_course c ON c.id=ch.courseid 
            LEFT JOIN {quiz} q ON q.id=ch.quizid 
            LEFT JOIN {assign} a ON a.id=ch.taskid 
            LEFT JOIN {course_categories} cat ON c.category=cat.id 
            WHERE (ch.startdate BETWEEN ? AND ? AND ch.quizid IS NOT NULL) OR ((ch.startdate BETWEEN ? AND ?) 
            OR (ch.enddate BETWEEN ? AND ?)) AND ch.taskid IS NOT NULL;";
        $data = $DB->get_records_sql($sql, [$startdaystamp, $enddaystamp, $startdaystamp,
            $enddaystamp, $startdaystamp, $enddaystamp]);

        if ($users->numusers >= $numstudients) {
            if (count($alldata) > 0) {
                $previoustime = ($i - 1).":59";
                if ($alldata[count($alldata) - 1]["end"] == $previoustime) {
                    $alldata[count($alldata) - 1]["end"] = $i.":59";
                    if ($alldata[count($alldata) - 1]["maxusers"] < $users->numusers) {
                        $alldata[count($alldata) - 1]["maxusers"] = $users->numusers;
                    }
                    if ($alldata[count($alldata) - 1]["minusers"] > $users->numusers) {
                        $alldata[count($alldata) - 1]["minusers"] = $users->numusers;
                    }
                    foreach ($data as $event) {
                        if (!in_array($event, $alldata[count($alldata) - 1]["events"])) {
                            $alldata[count($alldata) - 1]["events"][] = $event;
                        }
                    }
                } else {
                    $alldata[] = array("start" => $i.":00", "end" => $i.":59",
                        "maxusers" => $users->numusers, "minusers" => $users->numusers, "events" => $data);
                }
            } else {
                $alldata[] = array("start" => $i.":00", "end" => $i.":59",
                    "maxusers" => $users->numusers, "minusers" => $users->numusers, "events" => $data);
            }
        }
    }
    $returndata = array();
    foreach ($alldata as $peak) {
        $timestart = explode(":", $peak['start']);
        $timeend = explode(":", $peak['end']);

        $date->setTime($timestart[0], $timestart[1], 00);
        $timestart = $date->getTimestamp();
        $date->setTime($timeend[0], $timeend[1], 00);
        $timeend = $date->getTimestamp();

        $sql = "SELECT id FROM {report_chalmers_user_peaks} WHERE startdate=? AND enddate=?";
        $peaks = $DB->get_records_sql($sql, [$timestart, $timeend]);

        if (count($peaks) == 0) {
            $returndata[] = $peak;
            $dataobject = new stdClass();
            $dataobject->nstudents = $peak['maxusers'];
            $dataobject->startdate = $timestart;
            $dataobject->enddate = $timeend;
            $DB->insert_record('report_chalmers_user_peaks', $dataobject);
        }
    }
    return $returndata;
}

// This function gets managers of the platform.
function get_system_manager() {
    global $DB;

    $sql = 'SELECT u.* FROM mdl_user u JOIN mdl_role_assignments ra ON u.id=ra.userid
        JOIN mdl_context c ON ra.contextid=c.id WHERE c.contextlevel=10 AND ra.roleid = 9;';
    $data = $DB->get_records_sql($sql, []);

    $returndata = array();

    foreach ($data as $user) {
        $returndata[] = $user;
    }

    return $returndata;
}

// This function gets the admin of the platform.
function get_system_admin() {
    return get_admins();
}

// This function send the alert messages.
function send_alert_message($useridto, $subject, $fullmessage) {
    global $CFG;
    $message = new \core\message\message();
    $message->component = 'moodle';
    $message->name = 'instantmessage';
    $message->userfrom = core_user::get_noreply_user();
    $message->userto = \core_user::get_user($useridto);
    $message->subject = $subject;
    $message->fullmessage = $fullmessage;
    $message->fullmessageformat = FORMAT_MARKDOWN;
    $message->fullmessagehtml = '<p>' . $fullmessage . '</p>';
    $message->smallmessage = $fullmessage;
    $message->notification = 1;

    return message_send($message);
}

// This function checks if a warning has been already sent and if it should or not been send anyway.
function check_warnings() {
    global $DB;
    $warningdays = get_config('chalmers', 'warningdays');
    $actualdate = new DateTime();
    $limitdate = getdayforwards('+'.$warningdays);
    $sql = "SELECT * FROM {report_chalmers_user_peaks} WHERE enddate BETWEEN ? AND ? AND warned=0";
    $peaks = $DB->get_records_sql($sql, [$actualdate->getTimestamp(), $limitdate->getTimestamp()]);

    $alldata = array();

    foreach ($peaks as $peak) {
        $sql = "SELECT count(distinct u.id) AS numUsers FROM {user} u
            JOIN {user_enrolments} ue ON ue.userid = u.id
            JOIN {enrol} e ON e.id = ue.enrolid
            JOIN {report_chalmers_exams} ch ON ch.courseid = e.courseid
            where ( (ch.startdate BETWEEN ? and ? and ch.quizid is not null)
            OR ((ch.startdate BETWEEN ? and ?) or (ch.enddate BETWEEN ? and ?))
            and ch.taskid is not null) and ue.status=0;";
        $users = $DB->get_records_sql($sql, [$peak->startdate, $peak->enddate,
            $peak->startdate, $peak->enddate, $peak->startdate, $peak->enddate]);
        $users = reset($users);

        if ($users->numusers >= get_config('chalmers', 'alertnumusers')) {
            $sql = "SELECT ch.*,c.fullname,a.name as taskname,q.name as quizname, cat.id categoryid
                FROM mdl_report_chalmers_exams ch
                JOIN mdl_course c on c.id=ch.courseid
                left JOIN {quiz} q on q.id=ch.quizid
                left JOIN {assign} a on a.id=ch.taskid
                left JOIN {course_categories} cat on c.category=cat.id
                where (ch.startdate BETWEEN ? and ? and ch.quizid is not null)
                OR ((ch.startdate BETWEEN ? and ?) or (ch.enddate BETWEEN ? and ?))
                and ch.taskid is not null;";
            $data = $DB->get_records_sql($sql, [$peak->startdate, $peak->enddate,
                $peak->startdate, $peak->enddate, $peak->startdate, $peak->enddate]);

            $alldata[]['events'] = $data;

            $fecha = new DateTime();
            $fecha->setTimestamp($peak->startdate);

            $alldata[count($alldata) - 1]["date"] = $fecha->format('d-m-Y');
            $alldata[count($alldata) - 1]["startTime"] = $fecha->format('H:i');

            $fecha->setTimestamp($peak->enddate);
            $alldata[count($alldata) - 1]["endTime"] = $fecha->format('H:i');

            $alldata[count($alldata) - 1]["users"] = $users->numusers;

            $sql = "UPDATE {report_chalmers_user_peaks} SET warned = 1 WHERE id = ?";
            $DB->execute($sql, array($peak->id));
        }
    }
    return $alldata;
}
// Get module id of the provided module name
function get_moduleid($name) {
    global $DB;
    $sql = "SELECT * FROM {modules} WHERE name=".$name.";";
    $module = $DB->get_record("modules", array("name" => $name));

    return $module->id;
}