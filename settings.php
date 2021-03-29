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
 * Report settings
 *
 * @package    report
 * @subpackage chalmers
 * @copyright  2009 Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;
$ADMIN->add('reports', new admin_externalpage('chalmers',
    get_string('pluginheading', 'report_chalmers'), "$CFG->wwwroot/report/chalmers/index.php", 'report/chalmers:view'));
$settings->add(new admin_setting_configtext('chalmers/alertnumusers', get_string('alertnumusers', 'report_chalmers'),
get_string('configalertnumusers', 'report_chalmers'), 500, PARAM_INT));
$settings->add(new admin_setting_configtext('chalmers/alertdays', get_string('alertdays', 'report_chalmers'),
get_string('configalertdays', 'report_chalmers'), 7, PARAM_INT));
$settings->add(new admin_setting_configtext('chalmers/warningdays', get_string('warningdays', 'report_chalmers'),
get_string('configwarningdays', 'report_chalmers'), 3, PARAM_INT));