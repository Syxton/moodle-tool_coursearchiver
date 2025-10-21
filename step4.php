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
 * Step 4(Confirmation and Action).
 *
 * @package    tool_coursearchiver
 * @copyright  2015 Matthew Davidson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_OUTPUT_BUFFERING', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

header('X-Accel-Buffering: no');
header("Content-Encoding: none"); // Force no encoding for streaming output.
header('Accept-Encoding: none'); // Force no encoding for streaming output.

require_login();
admin_externalpage_setup('toolcoursearchiver');

global $SESSION;
$formdata   = $SESSION->coursearchiver_formdata ?? optional_param('formdata', false, PARAM_TEXT);

$error      = $SESSION->coursearchiver_error ?? optional_param('coursearchiver_error', false, PARAM_TEXT);
$error      = htmlspecialchars($error, ENT_COMPAT);

$mode       = $SESSION->coursearchiver_mode ?? optional_param('coursearchiver_mode', false, PARAM_INT);
$folder     = optional_param('folder', false, PARAM_TEXT);

$submitted  = optional_param('submit_button', false, PARAM_TEXT);
$submitted  = htmlspecialchars($submitted, ENT_COMPAT);

unset($SESSION->coursearchiver_formdata);
unset($SESSION->coursearchiver_error);
unset($SESSION->coursearchiver_mode);

// FORM 4 SUBMITTED.
if (
    !empty($submitted) &&
    !empty($formdata) &&
    !empty($mode)
) {
    // Button to start over has been pressed.
    if ($submitted == htmlspecialchars(get_string('back', 'tool_coursearchiver'), ENT_COMPAT)) {
        unset($SESSION->coursearchiver_formdata);
        unset($SESSION->coursearchiver_mode);
        unset($SESSION->coursearchiver_error);
        $returnurl = new moodle_url('/admin/tool/coursearchiver/index.php');
        redirect($returnurl);
    }

    if (!empty($error)) {
        echo $OUTPUT->container($error, 'coursearchiver_myformerror');
    }

    if ($submitted == htmlspecialchars(get_string('confirm', 'tool_coursearchiver'), ENT_COMPAT)) {
        if (
            !isset($mode) ||
            !in_array(
                $mode,
                [
                    tool_coursearchiver_processor::MODE_HIDE,
                    tool_coursearchiver_processor::MODE_BACKUP,
                    tool_coursearchiver_processor::MODE_ARCHIVE,
                    tool_coursearchiver_processor::MODE_DELETE,
                    tool_coursearchiver_processor::MODE_HIDEEMAIL,
                    tool_coursearchiver_processor::MODE_ARCHIVEEMAIL,
                    tool_coursearchiver_processor::MODE_DELETEEMAIL,
                    tool_coursearchiver_processor::MODE_OPTOUT,
                ]
            )
        ) {
            throw new coding_exception('Unknown process mode');
        }

        switch ($mode) {
            case tool_coursearchiver_processor::MODE_HIDEEMAIL:
            case tool_coursearchiver_processor::MODE_ARCHIVEEMAIL:
            case tool_coursearchiver_processor::MODE_DELETEEMAIL:
                echo $OUTPUT->header();
                echo $OUTPUT->heading_with_help(
                    get_string('coursearchiver', 'tool_coursearchiver'),
                    'coursearchiver',
                    'tool_coursearchiver'
                );

                $selected = json_decode($formdata);
                $owners = [];
                foreach ($selected as $s) {
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

                if (!is_array($owners) || empty($owners)) { // If 0 courses are selected, show message and form again.
                    $SESSION->coursearchiver_formdata = $formdata;
                    $SESSION->coursearchiver_error = get_string('nousersselected', 'tool_coursearchiver');
                    $returnurl = new moodle_url('/admin/tool/coursearchiver/step3.php');
                    redirect($returnurl);
                }
                $processor = new tool_coursearchiver_processor(["mode" => $mode, "data" => $owners]);
                $processor->execute(tool_coursearchiver_tracker::OUTPUT_HTML);
                echo $OUTPUT->footer();
                break;
            case tool_coursearchiver_processor::MODE_HIDE:
            case tool_coursearchiver_processor::MODE_BACKUP:
            case tool_coursearchiver_processor::MODE_ARCHIVE:
            case tool_coursearchiver_processor::MODE_DELETE:
            case tool_coursearchiver_processor::MODE_OPTOUT:
                echo $OUTPUT->header();
                echo $OUTPUT->heading_with_help(
                    get_string('coursearchiver', 'tool_coursearchiver'),
                    'coursearchiver',
                    'tool_coursearchiver'
                );

                $courses = json_decode($formdata);
                if (!is_array($courses) || empty($courses)) { // If 0 courses are selected, show message and form again.
                    $SESSION->coursearchiver_formdata = $formdata;
                    $SESSION->coursearchiver_error = get_string('nocoursesselected', 'tool_coursearchiver');
                    $returnurl = new moodle_url('/admin/tool/coursearchiver/step2.php');
                    redirect($returnurl);
                }

                $processor = new tool_coursearchiver_processor(["mode" => $mode, "data" => $courses]);
                if (!empty($folder)) {
                    $processor->folder = $folder;
                }

                // Automatic refreshing iframe to keep sessions alive during long script execution.
                $keepalive = new moodle_url('/admin/tool/coursearchiver/keepalive.php');
                echo '<iframe style="display:none" src="' . $keepalive . '"></iframe>';

                // Execute process.
                $processor->execute(tool_coursearchiver_tracker::OUTPUT_HTML, null);

                echo $OUTPUT->footer();
                break;
            default:
                $SESSION->coursearchiver_error = get_string('unknownerror', 'tool_coursearchiver');
                $returnurl = new moodle_url('/admin/tool/coursearchiver/index.php');
                redirect($returnurl);
        }
    }
} else if (!empty($formdata) && !empty($mode)) {  // FORM 3 SUBMITTED, SHOW FORM 4.
    echo $OUTPUT->header();
    echo $OUTPUT->heading_with_help(get_string('coursearchiver', 'tool_coursearchiver'), 'coursearchiver', 'tool_coursearchiver');

    if (!empty($error)) {
        echo $OUTPUT->container($error, 'coursearchiver_myformerror');
    }

    $param = ["mode" => $mode, "formdata" => $formdata];
    $mform = new tool_coursearchiver_step4_form(null, ["processor_data" => $param]);

    $mform->display();
    echo $OUTPUT->footer();
} else { // IN THE EVENT OF A FAILURE, JUST GO BACK TO THE BEGINNING.
    $SESSION->coursearchiver_error = get_string('unknownerror', 'tool_coursearchiver');
    $returnurl = new moodle_url('/admin/tool/coursearchiver/index.php');
    redirect($returnurl);
}
