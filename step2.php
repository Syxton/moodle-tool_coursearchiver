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
$formdata   = isset($SESSION->formdata) ? $SESSION->formdata : optional_param('formdata', false, PARAM_RAW);
$error      = isset($SESSION->error) ? $SESSION->error : optional_param('error', false, PARAM_RAW);
$resume     = isset($SESSION->resume) ? $SESSION->resume : optional_param('resume', false, PARAM_RAW);
$title      = optional_param('save_title', false, PARAM_TEXT);
$selected   = optional_param_array('course_selected', [], PARAM_INT);
$submitted  = optional_param('submit_button', false, PARAM_RAW);

unset($SESSION->formdata);
unset($SESSION->error);
unset($SESSION->resume);

tool_coursearchiver_processor::select_deselect_javascript();

if (!empty($submitted)) { // FORM 2 SUBMITTED.

    if ($submitted == get_string('save', 'tool_coursearchiver')) { // Save has been pressed.
        tool_coursearchiver_processor::save_state(2, $title, $selected);
        $SESSION->resume = true;
        $SESSION->formdata = serialize($selected);
        $SESSION->error = get_string('saved', 'tool_coursearchiver');
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
        $SESSION->formdata = $formdata;
        $SESSION->error = get_string('nocoursesselected', 'tool_coursearchiver');
        $returnurl = new moodle_url('/admin/tool/coursearchiver/step2.php');
        redirect($returnurl);
    }

    switch($submitted){
        case get_string('email', 'tool_coursearchiver'):
            $SESSION->formdata = serialize($courses);
            $returnurl = new moodle_url('/admin/tool/coursearchiver/step3.php');
            redirect($returnurl);
            break;
        case get_string('hide', 'tool_coursearchiver'):
            $SESSION->mode = tool_coursearchiver_processor::MODE_HIDE;
            $SESSION->formdata = serialize($courses);
            $returnurl = new moodle_url('/admin/tool/coursearchiver/step4.php');
            redirect($returnurl);
            break;
        case get_string('backup', 'tool_coursearchiver'):
            $SESSION->mode = tool_coursearchiver_processor::MODE_BACKUP;
            $SESSION->formdata = serialize($courses);
            $returnurl = new moodle_url('/admin/tool/coursearchiver/step4.php');
            redirect($returnurl);
            break;
        case get_string('archive', 'tool_coursearchiver'):
            $SESSION->mode = tool_coursearchiver_processor::MODE_ARCHIVE;
            $SESSION->formdata = serialize($courses);
            $returnurl = new moodle_url('/admin/tool/coursearchiver/step4.php');
            redirect($returnurl);
            break;
        case get_string('delete', 'tool_coursearchiver'):
            $SESSION->mode = tool_coursearchiver_processor::MODE_DELETE;
            $SESSION->formdata = serialize($courses);
            $returnurl = new moodle_url('/admin/tool/coursearchiver/step4.php');
            redirect($returnurl);
            break;
        case get_string('optout', 'tool_coursearchiver'):
            $SESSION->mode = tool_coursearchiver_processor::MODE_OPTOUT;
            $SESSION->formdata = serialize($courses);
            $returnurl = new moodle_url('/admin/tool/coursearchiver/step4.php');
            redirect($returnurl);
            break;
        default:
            $SESSION->error = get_string('unknownerror', 'tool_coursearchiver');
            $returnurl = new moodle_url('/admin/tool/coursearchiver/index.php');
            redirect($returnurl);
    }

} else if (!empty($formdata)) {  // FORM 1 SUBMITTED, SHOW FORM 2.
    echo $OUTPUT->header();
    echo $OUTPUT->heading_with_help(get_string('coursearchiver', 'tool_coursearchiver'), 'coursearchiver', 'tool_coursearchiver');

    if (!empty($error)) {
        echo $OUTPUT->container($error, 'coursearchiver_myformerror');
    }

    $data = unserialize($formdata);
    if (!empty($resume)) { // Resume from save point.
        $searches = $data;
        $searches["resume"] = true;
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
    $SESSION->error = get_string('unknownerror', 'tool_coursearchiver');
    $returnurl = new moodle_url('/admin/tool/coursearchiver/index.php');
    redirect($returnurl);
}
