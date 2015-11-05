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

require_login();
admin_externalpage_setup('toolcoursearchiver');

$formdata       = optional_param('formdata', false, PARAM_RAW);
$error          = optional_param('error', false, PARAM_RAW);
$selected       = optional_param_array('course_selected', array(), PARAM_INT);
$submitted      = optional_param('submit_button', false, PARAM_RAW);

if (!empty($submitted)) { // FORM 2 SUBMITTED.
    // Clean selected course array.
    $courses = array();
    foreach ($selected as $c) {
        if (!empty($c)) {
            $courses[] = $c;
        }
    }

    if ($submitted == get_string('back', 'tool_coursearchiver')) { // Button to start over has been pressed.
        $returnurl = new moodle_url('/admin/tool/coursearchiver/index.php');
        redirect($returnurl);
    }

    if (empty($courses)) { // If 0 courses are selected, show message and form again.
        $returnurl = new moodle_url('/admin/tool/coursearchiver/step2.php',
                                    array("formdata" => $formdata,
                                          "error" => get_string('nocoursesselected', 'tool_coursearchiver')));
        redirect($returnurl);
    }

    switch($submitted){
        case get_string('email', 'tool_coursearchiver'):
            $returnurl = new moodle_url('/admin/tool/coursearchiver/step3.php',
                                        array("formdata" => serialize($courses)));
            redirect($returnurl);
            break;
        case get_string('hide', 'tool_coursearchiver'):
            $returnurl = new moodle_url('/admin/tool/coursearchiver/step4.php',
                                        array("mode" => tool_coursearchiver_processor::MODE_HIDE,
                                              "formdata" => serialize($courses)));
            redirect($returnurl);
            break;
        case get_string('archive', 'tool_coursearchiver'):
            $returnurl = new moodle_url('/admin/tool/coursearchiver/step4.php',
                                        array("mode" => tool_coursearchiver_processor::MODE_ARCHIVE,
                                              "formdata" => serialize($courses)));
            redirect($returnurl);
            break;
        default:
            $returnurl = new moodle_url('/admin/tool/coursearchiver/index.php',
                                        array("error" => get_string('unknownerror', 'tool_coursearchiver')));
            redirect($returnurl);
    }

} else if (!empty($formdata)) {  // FORM 1 SUBMITTED, SHOW FORM 2.

    header('X-Accel-Buffering: no');
    echo $OUTPUT->header();
    echo $OUTPUT->heading_with_help(get_string('coursearchiver', 'tool_coursearchiver'), 'coursearchiver', 'tool_coursearchiver');

    if (!empty($error)) {
        echo $OUTPUT->container($error, 'coursearchiver_myformerror');
    }

    $data = unserialize($formdata);
    $searches = array();
    foreach ($data as $key => $value) {
        $searches["$key"] = $value;
    }

    $param = array("mode" => tool_coursearchiver_processor::MODE_COURSELIST, "searches" => $searches);
    $mform = new tool_coursearchiver_step2_form(null, array("processor_data" => $param));

    $mform->display();
    echo $OUTPUT->footer();
} else { // IN THE EVENT OF A FAILURE, JUST GO BACK TO THE BEGINNING.
    $returnurl = new moodle_url('/admin/tool/coursearchiver/index.php',
                                array("error" => get_string('unknownerror', 'tool_coursearchiver')));
    redirect($returnurl);
}