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
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to table tool_coursearchiver_optout.
        $table->add_index('userid', XMLDB_INDEX_NOTUNIQUE, ['userid']);
        $table->add_index('courseid', XMLDB_INDEX_NOTUNIQUE, ['courseid']);
        $table->add_index('optouttime', XMLDB_INDEX_NOTUNIQUE, ['optouttime']);

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
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to table tool_coursearchiver_saves.
        $table->add_index('step', XMLDB_INDEX_NOTUNIQUE, ['step']);
        $table->add_index('savedate', XMLDB_INDEX_NOTUNIQUE, ['savedate']);

        // Conditionally launch create table for tool_coursearchiver_saves.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        // Monitor savepoint reached.
        upgrade_plugin_savepoint(true, 2017032900, 'tool', 'coursearchiver');
    }

    if ($oldversion < 2017110300) {
        $sql = "UPDATE {config_plugins}
                   SET name=?, value=(".$DB->sql_cast_char2int('value')." * 12)
                 WHERE plugin=? AND name=?";

        $params = ["optoutmonthssetting",
                   "tool_coursearchiver",
                   "optoutyearssetting",
                  ];
        $DB->execute($sql, $params);

        // Monitor savepoint reached.
        upgrade_plugin_savepoint(true, 2017110300, 'tool', 'coursearchiver');
    }

    if ($oldversion < 2018101700) {
        $table = new xmldb_table('tool_coursearchiver_optout');
        $field = new xmldb_field('optoutlength', XMLDB_TYPE_INTEGER, '10', null, null, null);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $sql = "UPDATE {tool_coursearchiver_optout}
                   SET optoutlength=?";

        $config = get_config('tool_coursearchiver');
        $params = [$config->optoutmonthssetting];
        $DB->execute($sql, $params);

        // Monitor savepoint reached.
        upgrade_plugin_savepoint(true, 2018101700, 'tool', 'coursearchiver');
    }

    if ($oldversion < 2018121300) {
        // Define table tool_coursearchiver_archived to be created.
        $table = new xmldb_table('tool_coursearchiver_archived');

        // Adding fields to table tool_coursearchiver_archived.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('filename', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('owners', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timetodelete', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table tool_coursearchiver_archived.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to table tool_coursearchiver_archived.
        $table->add_index('filename', XMLDB_INDEX_NOTUNIQUE, ['filename']);
        $table->add_index('owners', XMLDB_INDEX_NOTUNIQUE, ['owners']);
        $table->add_index('timetodelete', XMLDB_INDEX_NOTUNIQUE, ['timetodelete']);

        // Conditionally launch create table for tool_coursearchiver_archived.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Fill up the database with previously archived files.
        $rootpath = rtrim(get_config('tool_coursearchiver', 'coursearchiverrootpath'), "/\\");
        $archivepath = trim(str_replace(str_split(':*?"<>|'),
                                        '',
                                        get_config('tool_coursearchiver', 'coursearchiverpath')),
                            "/\\");

        if (file_exists($rootpath . '/' . $archivepath)) {
            $fileinfos = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($rootpath . '/' . $archivepath)
            );

            if (!empty($fileinfos)) {
                foreach ($fileinfos as $pathname => $fileinfo) {
                    $pathinfo = pathinfo($pathname);
                    $file = $pathinfo['basename'];
                    $path = strstr($pathinfo['dirname'], $archivepath);
                    $path = str_replace($archivepath, '', $path);
                    $path = trim($path, "/\\"); // Leaves the sub folder only.

                    if (!$fileinfo->isFile()) { // Make sure it is a file.
                        continue;
                    }

                    if (!empty($search) && (empty(strstr($file, $search)) && empty(strstr($path, $search)))) {
                        continue;
                    }

                    $record = new stdClass();
                    $record->filename     = $path . '/' . $file;
                    $record->owners       = '';
                    $record->timetodelete = '0';
                    $DB->insert_record('tool_coursearchiver_archived', $record, false);
                }
            }
        }

        // Monitor savepoint reached.
        upgrade_plugin_savepoint(true, 2018121300, 'tool', 'coursearchiver');
    }

    if ($oldversion < 2020022700) {
        $table = new xmldb_table('tool_coursearchiver_optout');
        $field = new xmldb_field('optoutlength', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'optouttime');

        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_notnull($table, $field);
        }

        $table = new xmldb_table('tool_coursearchiver_archived');
        $field = new xmldb_field('timetodelete', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'owners');

        if ($dbman->field_exists($table, $field)) {
            $index = new xmldb_index('timetodelete', XMLDB_INDEX_NOTUNIQUE, ['timetodelete']);
            if ($dbman->index_exists($table, $index)) {
                $dbman->drop_index($table, $index);
            }
            $dbman->change_field_notnull($table, $field);
            $dbman->add_index($table, $index);
        }

        // Monitor savepoint reached.
        upgrade_plugin_savepoint(true, 2020022700, 'tool', 'coursearchiver');
    }

    return true;
}
