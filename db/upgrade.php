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
 * Upgrade script for tool_coursearchiver.
 *
 * @package    tool_coursearchiver
 * @copyright  2015 Matthew Davidson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the plugin.
 *
 * @param int $oldversion
 * @return bool always true
 */
function xmldb_tool_coursearchiver_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2017032900) {
        // Define table tool_coursearchiver_optout to be created.
        $table = new xmldb_table('tool_coursearchiver_optout');

        // Adding fields to table tool_coursearchiver_optout.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('optouttime', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table tool_coursearchiver_optout.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table tool_coursearchiver_optout.
        $table->add_index('userid', XMLDB_INDEX_NOTUNIQUE, array('userid'));
        $table->add_index('courseid', XMLDB_INDEX_NOTUNIQUE, array('courseid'));
        $table->add_index('optouttime', XMLDB_INDEX_NOTUNIQUE, array('optouttime'));

        // Conditionally launch create table for tool_coursearchiver_optout.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table tool_coursearchiver_saves to be created.
        $table = new xmldb_table('tool_coursearchiver_saves');

        // Adding fields to table tool_coursearchiver_saves.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('content', XMLDB_TYPE_TEXT, 'long', null, null, null, null);
        $table->add_field('step', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('savedate', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table tool_coursearchiver_saves.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table tool_coursearchiver_saves.
        $table->add_index('step', XMLDB_INDEX_NOTUNIQUE, array('step'));
        $table->add_index('savedate', XMLDB_INDEX_NOTUNIQUE, array('savedate'));

        // Conditionally launch create table for tool_coursearchiver_saves.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        // Monitor savepoint reached.
        upgrade_plugin_savepoint(true, 2017032900, 'tool', 'coursearchiver');
    } else if ($oldversion < 2017110300) {
        $sql = "UPDATE {config_plugins}
                   SET name=?, value=(".$DB->sql_cast_char2int('value')." * 12)
                 WHERE plugin=? AND name=?";

        $params = array("optoutmonthssetting",
                        "tool_coursearchiver",
                        "optoutyearssetting");
        $DB->execute($sql, $params);

        // Monitor savepoint reached.
        upgrade_plugin_savepoint(true, 2017110300, 'tool', 'coursearchiver');
    }

    return true;
}
