<?php
// This file is part of
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
 * Archive manager.
 *
 * @package    tool_coursearchiver
 * @copyright  2015 Matthew Davidson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_OUTPUT_BUFFERING', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

header('X-Accel-Buffering: no');

require_login();

$searchterm = optional_param('searchterm', "", PARAM_TEXT);
$recover    = optional_param('recover', false, PARAM_BOOL);
$emaillist    = optional_param('emaillist', false, PARAM_BOOL);
$selected   = optional_param_array('selected', [], PARAM_TEXT);

global $SESSION, $OUTPUT;

$context = context_system::instance();
$PAGE->set_url(new \moodle_url('/admin/tool/coursearchiver/archivelist.php'));
$PAGE->navbar->add(get_string('archivelist', 'tool_coursearchiver'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('coursearchiver', 'tool_coursearchiver'));

if (!empty($emaillist)) {
    tool_coursearchiver_processor::deleted_archive_emails();
}

tool_coursearchiver_processor::select_deselect_javascript();

echo $OUTPUT->header();
echo $OUTPUT->heading_with_help(get_string('coursearchiver', 'tool_coursearchiver'), 'coursearchiver', 'tool_coursearchiver');

if (!empty($selected)) {
    if (!$recover) {
        tool_coursearchiver_processor::delete_archives($selected);
    } else {
        tool_coursearchiver_processor::recover_archives($selected);
    }
}

$list = tool_coursearchiver_processor::get_archivelist(trim($searchterm), $recover);

echo $list;

echo $OUTPUT->footer();
