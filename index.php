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
 * Step 1(Search form).
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
$error  = isset($SESSION->error) ? $SESSION->error : optional_param('error', false, PARAM_RAW);
$submitted  = optional_param('submitbutton', false, PARAM_RAW);

// View optouts list button.
if (!empty($submitted)) {
    if ($submitted == get_string('optoutlist', 'tool_coursearchiver')) {
        $returnurl = new moodle_url('/admin/tool/coursearchiver/optoutlist.php');
        redirect($returnurl);
    }
}

unset($SESSION->error);

$mform = new tool_coursearchiver_step1_form(null);

if ($mform->is_submitted()) {
    if ($mform->is_validated()) {
        $formdata = $mform->get_data();

        // Data to set in the form.
        if (!empty($formdata)) {
            // Get savestate data.
            if (!empty($formdata->savestates)) {
                $formdata->searches["savestates"] = $formdata->savestates;
                if ($save = tool_coursearchiver_processor::get_save($formdata->savestates)) {
                    $SESSION->formdata = $save->content;
                    $SESSION->resume = true;
                    $returnurl = new moodle_url('/admin/tool/coursearchiver/step'.$save->step.'.php');
                    redirect($returnurl);
                } else {
                    $SESSION->error = get_string('unknownerror', 'tool_coursearchiver');
                    $returnurl = new moodle_url('/admin/tool/coursearchiver/index.php');
                    redirect($returnurl);
                }
            }

            // Get search criteria from the first form to pass it onto the second.
            if (!empty($formdata->createdbeforeenabled)) {
                $formdata->searches["createdbefore"] = $formdata->createdbefore;
            }

            if (!empty($formdata->lastaccessenabled)) {
                $formdata->searches["access"] = $formdata->access;
            }

            // Get search criteria from the first form to pass it onto the second.
            if (!empty($formdata->emptyonly)) {
                $formdata->searches["emptyonly"] = true;
            }

            $SESSION->formdata = serialize($formdata->searches);
            $returnurl = new moodle_url('/admin/tool/coursearchiver/step2.php');
            redirect($returnurl);
        } else { // Form 1 data did not come across correctly.
            echo $OUTPUT->header();
            echo $OUTPUT->heading_with_help(get_string('coursearchiver', 'tool_coursearchiver'),
                                            'coursearchiver',
                                            'tool_coursearchiver');
            if (!empty($error)) {
                echo $OUTPUT->container($error, 'coursearchiver_myformerror');
            }
            echo $OUTPUT->container(get_string('erroremptysearch', 'tool_coursearchiver'), 'coursearchiver_myformerror');
            $mform->display();
            echo $OUTPUT->footer();
        }
    } else {
        echo $OUTPUT->header();
        echo $OUTPUT->heading_with_help(get_string('coursearchiver', 'tool_coursearchiver'),
                                        'coursearchiver',
                                        'tool_coursearchiver');
        if (!empty($error)) {
            echo $OUTPUT->container($error, 'coursearchiver_myformerror');
        }
        echo $OUTPUT->container(get_string('erroremptysearch', 'tool_coursearchiver'), 'coursearchiver_myformerror');
        $mform->display();
        echo $OUTPUT->footer();
    }
} else {
    echo $OUTPUT->header();
    echo $OUTPUT->heading_with_help(get_string('coursearchiver', 'tool_coursearchiver'), 'coursearchiver', 'tool_coursearchiver');
    if (!empty($error)) {
        echo $OUTPUT->container($error, 'coursearchiver_myformerror');
    }
    $mform->display();
    echo $OUTPUT->footer();
}