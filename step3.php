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
 * Step 3(Selected users).
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
admin_externalpage_setup('toolcoursearchiver');

global $SESSION;
$formdata   = isset($SESSION->formdata) ? $SESSION->formdata : optional_param('formdata', false, PARAM_RAW);
$mode       = isset($SESSION->mode) ? $SESSION->mode : optional_param('mode', false, PARAM_INT);
$error      = isset($SESSION->error) ? $SESSION->error : optional_param('error', false, PARAM_RAW);
$resume     = isset($SESSION->resume) ? $SESSION->resume : optional_param('resume', false, PARAM_RAW);
$title      = optional_param('save_title', false, PARAM_TEXT);
$selected   = optional_param_array('user_selected', [], PARAM_RAW);
$submitted  = optional_param('submit_button', false, PARAM_RAW);

unset($SESSION->formdata);
unset($SESSION->error);
unset($SESSION->mode);
unset($SESSION->resume);

tool_coursearchiver_processor::select_deselect_javascript();

if (!empty($submitted)) { // FORM 3 SUBMITTED.

    if ($submitted == get_string('save', 'tool_coursearchiver')) { // Save has been pressed.
        tool_coursearchiver_processor::save_state(3, $title, $selected);
        $SESSION->resume = true;
        $SESSION->formdata = serialize($selected);
        $SESSION->error = get_string('saved', 'tool_coursearchiver');
        $returnurl = new moodle_url('/admin/tool/coursearchiver/step3.php');
        redirect($returnurl);
    }

    // Clean selected users array.
    $users = [];
    foreach ($selected as $c) {
        if ($c > 0) {
            $users[] = $c;
        }
    }

    // Fully develop array.
    $owners = [];
    foreach ($users as $s) {
        $t = explode("_", $s);
        if (count($t) == 2) { // Both a course and an owner are needed.
            if (substr($t[0], 0, 1) !== 'x') { // User is selected.
                if (array_key_exists($t[1], $owners)) {
                    $temp = $owners[$t[1]]['courses'];
                    $owners[$t[1]]['courses'] = array_merge($temp, [$t[0] => get_course($t[0])]);
                } else {
                    $owners[$t[1]]['courses'] = [$t[0] => get_course($t[0])];
                    $owners[$t[1]]['user'] = $DB->get_record("user", ["id" => $t[1]]);
                }
            }
        }
    }

    if (empty($owners)) { // If 0 courses are selected, show message and form again.
        $SESSION->formdata = $formdata;
        $SESSION->error = get_string('nousersselected', 'tool_coursearchiver');
        $returnurl = new moodle_url('/admin/tool/coursearchiver/step3.php');
        redirect($returnurl);
    }

    switch($submitted){
        case get_string('hideemail', 'tool_coursearchiver'):
            $mode = tool_coursearchiver_processor::MODE_HIDEEMAIL;
            $SESSION->formdata = serialize($users);
            $SESSION->mode = $mode;
            $returnurl = new moodle_url('/admin/tool/coursearchiver/step4.php');
            redirect($returnurl);
            break;
        case get_string('archiveemail', 'tool_coursearchiver'):
            $mode = tool_coursearchiver_processor::MODE_ARCHIVEEMAIL;
            $SESSION->formdata = serialize($users);
            $SESSION->mode = $mode;
            $returnurl = new moodle_url('/admin/tool/coursearchiver/step4.php');
            redirect($returnurl);
            break;
        default:
            $SESSION->error = get_string('unknownerror', 'tool_coursearchiver');
            $returnurl = new moodle_url('/admin/tool/coursearchiver/index.php');
            redirect($returnurl);
    }

} else if (!empty($formdata)) {  // FORM 2 SUBMITTED, SHOW FORM 3.
    echo $OUTPUT->header();
    echo $OUTPUT->heading_with_help(get_string('coursearchiver', 'tool_coursearchiver'), 'coursearchiver', 'tool_coursearchiver');

    if (!empty($error)) {
        echo $OUTPUT->container($error, 'coursearchiver_myformerror');
    }

    $data = unserialize($formdata);
    if (!empty($resume)) { // Resume from save point.
        $data["resume"] = true;
    }

    // Check again to make sure courses are coming across correctly.
    if (!is_array($data) || empty($data)) {
        $SESSION->error = get_string('nocoursesselected', 'tool_coursearchiver');
        $returnurl = new moodle_url('/admin/tool/coursearchiver/step1.php');
        redirect($returnurl);
    }

    $param = ["mode" => tool_coursearchiver_processor::MODE_GETEMAILS, "courses" => $data];
    $mform = new tool_coursearchiver_step3_form(null, ["processor_data" => $param]);

    $mform->display();

    echo $OUTPUT->footer();
} else { // IN THE EVENT OF A FAILURE, JUST GO BACK TO THE BEGINNING.
    $SESSION->error = get_string('unknownerror', 'tool_coursearchiver');
    $returnurl = new moodle_url('/admin/tool/coursearchiver/index.php');
    redirect($returnurl);
}
