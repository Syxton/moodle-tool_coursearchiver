<?php
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

require_login();
admin_externalpage_setup('toolcoursearchiver');

$formdata       = optional_param('formdata', false, PARAM_RAW);
$mode           = optional_param('mode', false, PARAM_INT);
$error          = optional_param('error', false, PARAM_RAW);
$selected       = optional_param_array('user_selected', array(), PARAM_RAW);
$submitted      = optional_param('submit_button', false, PARAM_RAW);


if(!empty($submitted)) { // FORM 3 SUBMITTED 

    if($submitted == get_string('back', 'tool_coursearchiver')) { // Button to start over has been pressed
        $returnurl = new moodle_url('/admin/tool/coursearchiver/index.php');
        redirect($returnurl);       
    }
    
    //clean selected course array
    $users = array();
    foreach($selected as $c) {
        if(!empty($c)){
            $users[] = $c;       
        }
    }

    // fully develope array
    $owners = array();
    foreach($users as $s) {
        $t = explode("_", $s);
        if(count($t) == 2){ //both a course and an owner are needed
            if(array_key_exists($t[1], $owners)){
                $temp = $owners[$t[1]]['courses'];
                $owners[$t[1]]['courses'] = array_merge($temp, array($t[0] => get_course($t[0])));
            } else {
                $owners[$t[1]]['courses'] = array($t[0] => get_course($t[0]));
                $owners[$t[1]]['user'] = $DB->get_record("user", array("id" => $t[1]));
            }    
        }
    }

    if(empty($owners)) { // If 0 courses are selected, show message and form again
        $returnurl = new moodle_url('/admin/tool/coursearchiver/step3.php', array("formdata" => $formdata, "error" => get_string('nousersselected', 'tool_coursearchiver')));
        redirect($returnurl); 
    }

    switch($submitted){
        case get_string('hideemail', 'tool_coursearchiver'):
            $mode = tool_coursearchiver_processor::MODE_HIDEEMAIL;
            $returnurl = new moodle_url('/admin/tool/coursearchiver/step4.php', array("mode" => $mode, "formdata" => serialize($users)));
            redirect($returnurl);
            break;
        case get_string('archiveemail', 'tool_coursearchiver'):
            $mode = tool_coursearchiver_processor::MODE_ARCHIVEEMAIL;
            $returnurl = new moodle_url('/admin/tool/coursearchiver/step4.php', array("mode" => $mode, "formdata" => serialize($users)));
            redirect($returnurl);
            break;
        default:
            $returnurl = new moodle_url('/admin/tool/coursearchiver/index.php', array("error" => get_string('unknownerror', 'tool_coursearchiver')));
            redirect($returnurl);
    }
       
} else if(!empty($formdata)){  // FORM 2 SUBMITTED, SHOW FORM 3    
    $courses = unserialize($formdata);

    //check again to make sure courses are coming across correctly
    if(!is_array($courses) || empty($courses)){
        $returnurl = new moodle_url('/admin/tool/coursearchiver/step1.php', array("error" => get_string('nocoursesselected', 'tool_coursearchiver')));
        redirect($returnurl); 
    }
    
    header('X-Accel-Buffering: no');
    echo $OUTPUT->header();
    echo $OUTPUT->heading_with_help(get_string('coursearchiver', 'tool_coursearchiver'), 'coursearchiver', 'tool_coursearchiver');
    
    if (!empty($error)) {
        echo $OUTPUT->container($error, 'myformerror');   
    }
    
    $param = array("mode" => tool_coursearchiver_processor::MODE_GETEMAILS, "courses" => $courses);
    $mform = new tool_coursearchiver_step3_form(null, array("processor_data" => $param));

    $mform->display();
    
    echo $OUTPUT->footer();
} else { // IN THE EVENT OF A FAILURE, JUST GO BACK TO THE BEGINNING
    $returnurl = new moodle_url('/admin/tool/coursearchiver/index.php', array("error" => get_string('unknownerror', 'tool_coursearchiver')));
    redirect($returnurl);   
}