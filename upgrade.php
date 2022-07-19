<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see https://www.gnu.org/licenses/.

/**
 * Provides the {@see xmldb_plagiarism_mcopyfind_upgrade()} function.
 *
 * @package     plagiarism_mcopyfind
 * @category    upgrade
 * @copyright   2022 Johannes Wanner<johannes.wanner@web.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Define upgrade steps to be performed to upgrade the plugin from the old version to the current one.
 *
 * @param int $oldversion Version number the plugin is being upgraded from.
 */
function xmldb_plagiarism_mcopyfind_upgrade($oldversion=0) {
    global $DB; 
    $dbman = $DB->get_manager();

    if ($oldversion < 2020092800) {
        // Here goes the code that needs to be executed.
        set_config('foo', 'bar', 'plagiarism_mcopyfind');
        //get_config()
        upgrade_plugin_savepoint(true, 2020092800, 'plagiarism', 'mcopyfind');
    }
    // if ($oldversion < 2022040803) {

    //     // Define table plagiarism_mcopyfind_msgs to be created.
    //     $table = new xmldb_table('plagiarism_mcopyfind_msgs');

    //     // Adding fields to table plagiarism_mcopyfind_msgs.
    //     $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    //     $table->add_field('message', XMLDB_TYPE_TEXT, null, null, null, null, null);
    //     $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
    //     $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

    //     // Adding keys to table plagiarism_mcopyfind_msgs.
    //     $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
    //     $table->add_key('foreign', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

    //     // Conditionally launch create table for plagiarism_mcopyfind_msgs.
    //     if (!$dbman->table_exists($table)) {
    //         $dbman->create_table($table);
    //         error_log("created table","");
    //     }else{
    //         $table = $dbman->get_table($table);
    //         $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
    //         error_log("added fields");
    //         // Adding keys to table plagiarism_mcopyfind_msgs.
    //         $table->add_key('foreign', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
    //         //$dbman->create_table($table);
    //         //$table->savepoint();
    //         $dbman->save
    //     }
    if ($oldversion < 2022041127) {

        // Define field userid to be added to plagiarism_mcopyfind_msgs.
        $table = new xmldb_table('plagiarism_mcopyfind_match');
        
        $key = new xmldb_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $field = new xmldb_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $field2 = new xmldb_field('percent', XMLDB_TYPE_INTEGER, '5', null, null, null, null, 'id');

       
        // Define field contenthashl to be added to match.
        $field3 = new xmldb_field('contenthashl', XMLDB_TYPE_CHAR, '40', null, null, null, null, 'percent');
        // Define field contenthashr to be added to match.
        $field4 = new xmldb_field('contenthashr', XMLDB_TYPE_CHAR, '40', null, null, null, null, 'contenthashl');

        // Conditionally launch add field contenthashr.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            $dbman->add_key($table, $key);
        }

        // Conditionally launch add field percent.
        if (!$dbman->field_exists($table, $field2)) {
            $dbman->add_field($table, $field2);
        }

        // Conditionally launch add field contenthashl.
        if (!$dbman->field_exists($table, $field3)) {
            $dbman->add_field($table, $field3);
        }

        // Conditionally launch add field id.
        if (!$dbman->field_exists($table, $field4)) {
            $dbman->add_field($table, $field4);
        }


        // Helloworld savepoint reached.
        upgrade_plugin_savepoint(true, 2022041127, 'plagiarism', 'mcopyfind');
    }

   return false;
}