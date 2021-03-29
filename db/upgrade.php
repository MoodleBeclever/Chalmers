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

function xmldb_report_chalmers_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    // Add a new column newcol to the mdl_myqtype_options.
    if ($oldversion < 2021012800) {
        $table = new xmldb_table('local_chalmers_user_peaks');
        // Conditionally launch add field connecteduserfive.
        // Nombredelplugin savepoint reached.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('nstudents', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('startdate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('enddate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('warned', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2021012800, 'report', 'chalmers');
    }
    if ($oldversion < 2021032600) {
        // Define table local_chalmers_exams to be renamed to NEWNAMEGOESHERE.
        $table = new xmldb_table('local_chalmers_exams');

        // Launch rename table for local_chalmers_exams.
        $dbman->rename_table($table, 'report_chalmers_exams');

        // Define table local_chalmers_user_peaks to be renamed to NEWNAMEGOESHERE.
        $table = new xmldb_table('local_chalmers_user_peaks');

        // Launch rename table for local_chalmers_user_peaks.
        $dbman->rename_table($table, 'report_chalmers_user_peaks');

        // Chalmers savepoint reached.
        upgrade_plugin_savepoint(true, 2021032600, 'report', 'chalmers');
    }
    return true;
}