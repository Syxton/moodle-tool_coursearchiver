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
 *  Step 2(Search Results).
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
$formdata   = $SESSION->coursearchiver_formdata ?? optional_param('formdata', false, PARAM_TEXT);

$error      = $SESSION->coursearchiver_error ?? optional_param('coursearchiver_error', false, PARAM_TEXT);
$error      = htmlspecialchars($error, ENT_COMPAT);

$resume     = $SESSION->coursearchiver_resume ?? optional_param('coursearchiver_resume', false, PARAM_TEXT);
$resume     = htmlspecialchars($resume, ENT_COMPAT);

$title      = optional_param('save_title', false, PARAM_TEXT);

$selected   = optional_param_array('course_selected', [], PARAM_INT);

$submitted  = optional_param('submit_button', false, PARAM_TEXT);
$submitted  = htmlspecialchars($submitted, ENT_COMPAT);

unset($SESSION->coursearchiver_formdata);
unset($SESSION->coursearchiver_error);
unset($SESSION->coursearchiver_resume);

tool_coursearchiver_processor::select_deselect_javascript();

if (!empty($submitted)) { // FORM 2 SUBMITTED.
    // Save has been pressed.
    if ($submitted == htmlspecialchars(get_string('save', 'tool_coursearchiver'), ENT_COMPAT)) {
        tool_coursearchiver_processor::save_state(2, $title, $selected);
        $SESSION->coursearchiver_resume = true;
        $SESSION->coursearchiver_formdata = json_encode($selected);
        $SESSION->coursearchiver_error = get_string('saved', 'tool_coursearchiver');
        $returnurl = new moodle_url('/admin/tool/coursearchiver/step2.php');
        redirect($returnurl);
    }

    // Clean selected course array.
    $courses = [];
    foreach ($selected as $c) {
        if ($c > 0) {
            $courses[] = $c;
        }
    }

    if (empty($courses)) { // If 0 courses are selected, show message and form again.
        $SESSION->coursearchiver_formdata = $formdata;
        $SESSION->coursearchiver_error = get_string('nocoursesselected', 'tool_coursearchiver');
        $returnurl = new moodle_url('/admin/tool/coursearchiver/step2.php');
        redirect($returnurl);
    }

    switch ($submitted) {
        case htmlspecialchars(get_string('email', 'tool_coursearchiver'), ENT_COMPAT):
            $SESSION->coursearchiver_formdata = json_encode($courses);
            $returnurl = new moodle_url('/admin/tool/coursearchiver/step3.php');
            redirect($returnurl);
            break;
        case htmlspecialchars(get_string('hide', 'tool_coursearchiver'), ENT_COMPAT):
            $SESSION->coursearchiver_mode = tool_coursearchiver_processor::MODE_HIDE;
            $SESSION->coursearchiver_formdata = json_encode($courses);
            $returnurl = new moodle_url('/admin/tool/coursearchiver/step4.php');
            redirect($returnurl);
            break;
        case htmlspecialchars(get_string('backup', 'tool_coursearchiver'), ENT_COMPAT):
            $SESSION->coursearchiver_mode = tool_coursearchiver_processor::MODE_BACKUP;
            $SESSION->coursearchiver_formdata = json_encode($courses);
            $returnurl = new moodle_url('/admin/tool/coursearchiver/step4.php');
            redirect($returnurl);
            break;
        case htmlspecialchars(get_string('archive', 'tool_coursearchiver'), ENT_COMPAT):
            $SESSION->coursearchiver_mode = tool_coursearchiver_processor::MODE_ARCHIVE;
            $SESSION->coursearchiver_formdata = json_encode($courses);
            $returnurl = new moodle_url('/admin/tool/coursearchiver/step4.php');
            redirect($returnurl);
            break;
        case htmlspecialchars(get_string('delete', 'tool_coursearchiver'), ENT_COMPAT):
            $SESSION->coursearchiver_mode = tool_coursearchiver_processor::MODE_DELETE;
            $SESSION->coursearchiver_formdata = json_encode($courses);
            $returnurl = new moodle_url('/admin/tool/coursearchiver/step4.php');
            redirect($returnurl);
            break;
        case htmlspecialchars(get_string('optout', 'tool_coursearchiver'), ENT_COMPAT):
            $SESSION->coursearchiver_mode = tool_coursearchiver_processor::MODE_OPTOUT;
            $SESSION->coursearchiver_formdata = json_encode($courses);
            $returnurl = new moodle_url('/admin/tool/coursearchiver/step4.php');
            redirect($returnurl);
            break;
        default:
            $SESSION->coursearchiver_error = get_string('unknownerror', 'tool_coursearchiver');
            $returnurl = new moodle_url('/admin/tool/coursearchiver/index.php');
            redirect($returnurl);
    }
} else if (!empty($formdata)) {  // FORM 1 SUBMITTED, SHOW FORM 2.
    echo $OUTPUT->header();
    echo $OUTPUT->heading_with_help(get_string('coursearchiver', 'tool_coursearchiver'), 'coursearchiver', 'tool_coursearchiver');

    if (!empty($error)) {
        echo $OUTPUT->container($error, 'coursearchiver_myformerror');
    }

    $data = json_decode($formdata);
    if (!empty($resume)) { // Resume from save point.
        $searches = $data;
        $searches->resume = true;
    } else {
        $searches = [];
        foreach ($data as $key => $value) {
            $searches["$key"] = $value;
        }
    }


    $param = ["mode" => tool_coursearchiver_processor::MODE_COURSELIST, "searches" => $searches];
    $mform = new tool_coursearchiver_step2_form(null, ["processor_data" => $param]);

    $mform->display();
    echo $OUTPUT->footer();
} else { // IN THE EVENT OF A FAILURE, JUST GO BACK TO THE BEGINNING.
    $SESSION->coursearchiver_error = get_string('unknownerror', 'tool_coursearchiver');
    $returnurl = new moodle_url('/admin/tool/coursearchiver/index.php');
    redirect($returnurl);
}
