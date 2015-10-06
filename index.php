<?php
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

require_login();
admin_externalpage_setup('toolcoursearchiver');

$error          = optional_param('error', false, PARAM_RAW);

$mform = new tool_coursearchiver_step1_form(null);

if($mform->is_submitted()){
    //echo " SUBMITTED";
    if($mform->is_validated()){
        //echo " VALID";
        $formdata = $mform->get_data();

        // Data to set in the form.
        if (!empty($formdata)) {

            // Get search criteria from the first form to pass it onto the second.
            if(!empty($formdata->lastaccessenabled)){
                $formdata->searches["access"] = $formdata->access;
            }
            
            // Get search criteria from the first form to pass it onto the second.
            if(!empty($formdata->emptyonly)){
                $formdata->searches["emptyonly"] = true;
            }

            $data["formdata"] = serialize($formdata->searches);
            
            $returnurl = new moodle_url('/admin/tool/coursearchiver/step2.php', $data);
            redirect($returnurl);
        } else { // Form 1 data did not come across correctly.
            echo $OUTPUT->header();
            echo $OUTPUT->heading_with_help(get_string('coursearchiver', 'tool_coursearchiver'), 'coursearchiver', 'tool_coursearchiver');
            if (!empty($error)) {
                echo $OUTPUT->container($error, 'myformerror');   
            }
            echo $OUTPUT->container(get_string('erroremptysearch', 'tool_coursearchiver'), 'myformerror');   
            $mform->display();
            echo $OUTPUT->footer();
        }         
    } else {
        echo $OUTPUT->header();
        echo $OUTPUT->heading_with_help(get_string('coursearchiver', 'tool_coursearchiver'), 'coursearchiver', 'tool_coursearchiver');
        if (!empty($error)) {
            echo $OUTPUT->container($error, 'myformerror');   
        }
        echo $OUTPUT->container(get_string('erroremptysearch', 'tool_coursearchiver'), 'myformerror');
        $mform->display();
        echo $OUTPUT->footer();
    }
} else {
    echo $OUTPUT->header();
    echo $OUTPUT->heading_with_help(get_string('coursearchiver', 'tool_coursearchiver'), 'coursearchiver', 'tool_coursearchiver');
    if (!empty($error)) {
        echo $OUTPUT->container($error, 'myformerror');   
    }
    $mform->display();
    echo $OUTPUT->footer();
} 
