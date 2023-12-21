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
 * Delete Course after archiving.
 *
 * @package    tool_coursearchiver
 * @copyright  2015 Matthew Davidson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_course extends \core\task\adhoc_task {
    /**
     * get_name
     *
     * @return string
     */
    public function get_name() {
        return get_string('delete', 'tool_coursearchiver');
    }

    /**
     * Executes the prediction task.
     *
     * @return bool
     */
    public function execute(): bool {
        $data = $this->get_custom_data();

        mtrace(get_string('attemptdeletingcourse', 'tool_coursearchiver', $data->course));

        if (delete_course($data->course->id, false)) {
            mtrace(get_string('coursedeleted', 'tool_coursearchiver'));
            return true;
        }

        mtrace(get_string('errordeletingcourse', 'tool_coursearchiver', $data->course));
        return false;
    }
}
