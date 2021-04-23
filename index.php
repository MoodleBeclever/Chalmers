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

require(__DIR__.'/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/tablelib.php');
admin_externalpage_setup('chalmers', '', null, '', array('pagelayout' => 'report'));
require_once('locallib.php');
require_once('form.php');

$page    = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 30, PARAM_INT);    // How many per page.
$sort    = optional_param('sort', 'timemodified', PARAM_ALPHA);
$dir     = optional_param('dir', 'DESC', PARAM_ALPHA);
$download = optional_param('download', '', PARAM_ALPHA);

raise_memory_limit(MEMORY_EXTRA);
core_php_time_limit::raise();


$PAGE->set_title(get_string('pluginname', 'report_chalmers'));

$table = new flexible_table('reprot_chalmers_flextab');
$exportfilename = 'quizz_assigns_summary';
if (!$table->is_downloading($download, $exportfilename)) {
    echo $OUTPUT->header();

    echo '<h3>'.get_string('monthquizassigns','report_chalmers').'</h3>';
    if (class_exists('core\chart_bar')) {
        $chartmonth = new core\chart_bar();
        $mlabels = get_month_days(new DateTime('now', core_date::get_server_timezone_object()));
        $quizvalues = get_all_chalmers_quiz();
        $chartquizvalues = convert_to_chartvalues($quizvalues, $mlabels);
        $taskvalues = get_all_chalmers_task();
        $charttaskvalues = convert_to_chartvalues($taskvalues, $mlabels);

        $serie1 = new core\chart_series(get_string('quizzes','report_chalmers'), $chartquizvalues);
        $serie2 = new core\chart_series(get_string('assigns','report_chalmers'), $charttaskvalues);
        $chartmonth->add_series($serie1);
        $chartmonth->add_series($serie2);
        $chartmonth->set_labels($mlabels);
        echo $OUTPUT->render($chartmonth);
    }
    $fechabuscada;
    $mform = new search_form();
    if (!$table->is_downloading()) {
        if ($fromform = $mform->get_data()) {
            // In this case you process validated data. $mform->get_data() returns data posted in form.
            $fechabuscada = new DateTime("@$fromform->searchdate");
            $fechabuscada->setTimezone(core_date::get_server_timezone_object());
            $mform->display();
        } else {
            // This branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
            // or on the first display of the form
            // set default data (if any).
            $mform->set_data($toform);
            // Displays the form.
            $mform->display();
        }
    }
    if ($fechabuscada == null) {
        $temp = new DateTime('now', core_date::get_server_timezone_object());
        $fechabuscada = new DateTime($temp->format('y-m-d'));
    }
    echo '<h3>'.$fechabuscada->format('d/m/y').'</h3>';
    if (class_exists('core\chart_line')) {
        $chart = new core\chart_line();
        $labels = get_daylabels();

        $quizfortheday = get_all_chalmers_quiz($fechabuscada);
        $chartquiz = convert_to_daychartvalues($quizfortheday, $labels, $fechabuscada);
        $taskfortheday = get_all_chalmers_task($fechabuscada);
        $charttask = convert_to_daychartvalues($taskfortheday, $labels, $fechabuscada);
        $serie1 = new core\chart_series(get_string('students','report_chalmers').' '.get_string('quizzes','report_chalmers'), $chartquiz);
        $serie2 = new core\chart_series(get_string('students','report_chalmers').' '.get_string('assigns','report_chalmers'), $charttask);

        $chart->set_smooth(true);
        $chart->add_series($serie1);
        $chart->add_series($serie2);
        $chart->set_labels($labels);
        echo $OUTPUT->render($chart);
    }
    echo $OUTPUT->heading(get_string('plugindesc', 'report_chalmers'));
}
$url = $CFG->wwwroot;

$columns;
$headers;
if (!$table->is_downloading()) {
    $columns = array('quiz', 'task', 'coursename', 'teacher', 'startdate', 'enddate', 'nstudents', 'timelimit');
    $headers = array(get_string('colquiz', 'report_chalmers'), get_string('coltask', 'report_chalmers'),
      get_string('colcoursename', 'report_chalmers'), get_string('colteacheremail', 'report_chalmers'),
      get_string('colstartdate', 'report_chalmers'), get_string('colenddate', 'report_chalmers'),
      get_string('colnstudents', 'report_chalmers'), get_string('coltimelimit', 'report_chalmers'));
} else {
    $columns = array('quiz', 'task', 'activityurl', 'coursename', 'courseurl', 'cidnumber', 'teacher',
      'startdate', 'enddate', 'nstudents', 'parturl', 'timelimit', 'attempts', 'reportadding', 'reportupdate');
    $headers = array(get_string('colquiz', 'report_chalmers'), get_string('coltask', 'report_chalmers'),
      get_string('colacurl', 'report_chalmers'), get_string('colcoursename', 'report_chalmers'),
      get_string('colcurl', 'report_chalmers'), get_string('colcidnumber', 'report_chalmers'),
      get_string('colteacheremail', 'report_chalmers'), get_string('colstartdate', 'report_chalmers'),
      get_string('colenddate', 'report_chalmers'), get_string('colnstudents', 'report_chalmers'),
      get_string('colparturl', 'report_chalmers'), get_string('coltimelimit', 'report_chalmers'),
      get_string('colattempt', 'report_chalmers'), get_string('colreportadding', 'report_chalmers'),
      get_string('colreportupdate', 'report_chalmers'));
}

$table->define_columns($columns);
$table->define_headers($headers);
$table->define_baseurl($CFG->wwwroot.'/report/chalmers/index.php');
$table->set_attribute('class', 'generaltable');
$table->set_attribute('id', 'chalmers_showingtable');

$table->pageable(true);
$table->is_downloading($download, 'test', 'chalmers');

$table->setup();

$data = get_all_chalmers_data();
$total = 0;
foreach ($data as $key => $value) {
    $courseurl;
    $activityurl;
    $attempts;
    if (!$table->is_downloading()) {
        if ($value->taskid == null) {
            $value->taskid = '-';
            $value->quizid = '<a href="'.$url.'/mod/quiz/view.php?id='.$value->moduleid.'">'.
              get_taskquiz_name ($value->quizid, 'quiz').'</a>';
        } else {
            $value->quizid = '-';
            $value->taskid = '<a href="'.$url.'/mod/assign/view.php?id='.$value->moduleid.'">'.
              get_taskquiz_name ($value->taskid, 'task').'</a>';
        }
    } else {
        if ($value->taskid == null) {
            $value->taskid = '-';
            $attempts = get_quiz_attemps($value->quizid);
            if ($attempts == 0) {
                $attempts = get_string('unlimited','report_chalmers');
            }
            $value->quizid = get_taskquiz_name ($value->quizid, 'quiz');
            $activityurl = $url . '/mod/quiz/view.php?id='.$value->moduleid;
        } else {
            $value->quizid = '-';
            $attempts = get_task_attemps($value->taskid);
            if ($attempts == 1) {
                $attempts = '1 '.get_string('attempt','report_chalmers');
            } else {
                $attempts = $attempts.' '.get_string('attempts','report_chalmers');
            }
            $value->taskid = get_taskquiz_name ($value->taskid, 'task');
            $activityurl = $url . '/mod/assign/view.php?id='.$value->moduleid;

        }
    }
    $value->startdate = new DateTime("@$value->startdate");
    $value->enddate = new DateTime("@$value->enddate");
    $value->reportaddingdate = new DateTime("@$value->reportaddingdate");
    $value->reportlastupdate = new DateTime("@$value->reportlastupdate");
    $value->startdate->setTimezone(core_date::get_server_timezone_object());
    $value->enddate->setTimezone(core_date::get_server_timezone_object());
    $value->reportaddingdate->setTimezone(core_date::get_server_timezone_object());
    $value->reportlastupdate->setTimezone(core_date::get_server_timezone_object());
    $cidnumber = get_course_idnumber($value->courseid);
    if ($value->timelimit == 0 || $value->timelimit == null) {
        $value->timelimit = '-';
    } else {
        $value->timelimit = $value->timelimit / 60;
    }
    $teachers;
    $row;
    if (!$table->is_downloading()) {
        $course = '<a href="'.$url.'/course/view.php?id='.$value->courseid.'">'.get_course_name($value->courseid).'</a>';
        $value->nstudents = '<a href="'.$url.'/user/index.php?id='.$value->courseid.'">'.$value->nstudents.'</a>';
        $teachers = get_teachersemails($value->courseid);
        $row = array('quiz' => $value->quizid, 'task' => $value->taskid, 'coursename' => $course, 'teacher' => $teachers,
          'startdate' => $value->startdate->format('d/m/Y H:i'), 'enddate' => $value->enddate->format('d/m/Y H:i'),
          'nstudents' => $value->nstudents, 'timelimit' => $value->timelimit);
    } else {
        $course = get_course_name($value->courseid);
        $courseurl = $url.'/course/view.php?id='.$value->courseid;
        $participanturl = $url.'/user/index.php?id='.$value->courseid;
        $teachers = get_teachersemails($value->courseid);
        $row = array('quiz' => $value->quizid, 'task' => $value->taskid, 'activityurl' => $activityurl,
          'coursename' => $course, 'courseurl' => $courseurl, 'cidnumber' => $cidnumber, 'teacher' => $teachers,
          'startdate' => $value->startdate->format('d/m/Y H:i'), 'enddate' => $value->enddate->format('d/m/Y H:i'),
          'nstudents' => $value->nstudents, 'parturl' => $participanturl, 'timelimit' => $value->timelimit,
          'attempts' => $attempts, 'reportadding' => $value->reportaddingdate->format('d/m/Y H:i'),
          'reportupdate' => $value->reportlastupdate->format('d/m/Y H:i'));
    }
    $total++;
    $table->add_data($row);
}
$table->pagesize(150, $total);
$table->finish_output();

if (!$table->is_downloading()) {
    echo $OUTPUT->footer();
}