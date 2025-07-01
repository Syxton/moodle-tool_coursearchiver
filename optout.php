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
 * Optout page.
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

$courseid   = required_param('courseid', PARAM_INT);
$userid     = required_param('userid', PARAM_INT);
$key        = required_param('key', PARAM_ALPHANUMEXT);

$context = context_system::instance();
$PAGE->set_url(new \moodle_url('/admin/tool/coursearchiver/optout.php'));
$PAGE->navbar->add(get_string('coursearchiver', 'tool_coursearchiver'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('coursearchiver', 'tool_coursearchiver'));

echo $OUTPUT->header();
echo $OUTPUT->heading_with_help(get_string('coursearchiver', 'tool_coursearchiver'), 'coursearchiver', 'tool_coursearchiver');

// Check to see if the optout by email setting is enabled.
$config = get_config('tool_coursearchiver');
if (!$config->optoutbyemailsetting) {
    echo $OUTPUT->container(get_string('optoutbyemaildisabled', 'tool_coursearchiver'), 'coursearchiver_myformerror');
    echo $OUTPUT->footer();
    die();
}

// Check to see if the attempt is coming from a valid email.
if (!sha1(tool_coursearchiver_processor::get_coursearchiver_keyid() . $courseid . $userid) == $key) {
    echo $OUTPUT->container(get_string('error_key', 'tool_coursearchiver'), 'coursearchiver_myformerror');
    echo $OUTPUT->footer();
    die();
}

// Check to see if the course exists.
if (!$course = tool_coursearchiver_processor::optout_course($courseid, $userid)) {
    echo $OUTPUT->container(get_string('error_nocourseid', 'tool_coursearchiver'), 'coursearchiver_myformerror');
    echo $OUTPUT->footer();
    die();
}

echo $OUTPUT->container(html_writer::tag('div',
                                         get_string('course_skipped', 'tool_coursearchiver', $course),
                                         ['style' => 'margin:15px;text-align:center;font-size:1.4em;font-weight:bold']));

echo $OUTPUT->footer();
