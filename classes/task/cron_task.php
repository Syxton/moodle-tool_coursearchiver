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
 * A scheduled task for coursearchive cron.
 *
 * @package    tool_coursearchiver
 * @copyright  2015 Matthew Davidson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_coursearchiver\task;


/**
 * Cron task class.
 *
 * @package    tool_coursearchiver
 * @copyright  2015 Matthew Davidson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cron_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('crontask', 'tool_coursearchiver');
    }

    /**
     * Run forum cron.
     */
    public function execute() {
        global $DB;

        $rootpath = rtrim(get_config('tool_coursearchiver', 'coursearchiverrootpath'), "/\\");
        $archivepath = trim(str_replace(str_split(':*?"<>|'),
                                        '',
                                        get_config('tool_coursearchiver', 'coursearchiverpath')),
                            "/\\");

        $sql = 'SELECT *
                  FROM {tool_coursearchiver_archived}
                 WHERE timetodelete > 0 AND timetodelete <= :timetodelete';

        if ($markedfordeletion = $DB->get_records_sql($sql, ['timetodelete' => time()])) {
            foreach ($markedfordeletion as $fileinfo) {
                $file = $rootpath . '/' . $archivepath . '/' . $fileinfo->filename;
                if (file_exists($file)) {
                    if (unlink($file)) { // Delete file.
                        $DB->delete_records('tool_coursearchiver_archived', ['id' => $fileinfo->id]);
                        mtrace($file . ' deleted');
                    } else {
                        mtrace('!!! FAILED TO DELETE: ' . $file);
                    }
                }
            }
        }
    }
}
