<?php
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

require_login();
admin_externalpage_setup('toolcoursearchiver');

$formdata       = optional_param('formdata', false, PARAM_RAW);
$error          = optional_param('error', false, PARAM_RAW);
$submitted      = optional_param('submit_button', false, PARAM_RAW);
$mode           = optional_param('mode', false, PARAM_INT);
$folder         = optional_param('folder', false, PARAM_TEXT);


if(!empty($submitted) && !empty($formdata) && !empty($mode)) { // FORM 3 SUBMITTED 

    if($submitted == get_string('back', 'tool_coursearchiver')) { // Button to start over has been pressed
        $returnurl = new moodle_url('/admin/tool/coursearchiver/index.php');
        redirect($returnurl);
    }
   
    if (!empty($error)) {
        echo $OUTPUT->container($error, 'myformerror');   
    }
 
    if($submitted == get_string('confirm', 'tool_coursearchiver')){
        if (!isset($mode) || !in_array($mode, array(tool_coursearchiver_processor::MODE_HIDE, tool_coursearchiver_processor::MODE_ARCHIVE, tool_coursearchiver_processor::MODE_HIDEEMAIL, tool_coursearchiver_processor::MODE_ARCHIVEEMAIL))) {
            throw new coding_exception('Unknown process mode');
        }
 
        switch($mode){
            case tool_coursearchiver_processor::MODE_HIDEEMAIL:
            case tool_coursearchiver_processor::MODE_ARCHIVEEMAIL:
                header('X-Accel-Buffering: no');
                echo $OUTPUT->header();
                echo $OUTPUT->heading_with_help(get_string('coursearchiver', 'tool_coursearchiver'), 'coursearchiver', 'tool_coursearchiver');
    
                $selected = unserialize($formdata);
                $owners = array();
                foreach($selected as $s) {
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
                
                if(!is_array($owners) || empty($owners)) { // If 0 courses are selected, show message and form again
                    $returnurl = new moodle_url('/admin/tool/coursearchiver/step3.php', array("formdata" => $formdata, "error" => get_string('nousersselected', 'tool_coursearchiver')));
                    redirect($returnurl); 
                }
                $processor = new tool_coursearchiver_processor(array("mode" => $mode, "data" => $owners));
                $processor->execute(tool_coursearchiver_tracker::OUTPUT_HTML);
                echo $OUTPUT->footer();
                break;
            case tool_coursearchiver_processor::MODE_HIDE:
            case tool_coursearchiver_processor::MODE_ARCHIVE:
                header('X-Accel-Buffering: no');
                echo $OUTPUT->header();
                echo $OUTPUT->heading_with_help(get_string('coursearchiver', 'tool_coursearchiver'), 'coursearchiver', 'tool_coursearchiver');
    
                $courses = unserialize($formdata);
                if(!is_array($courses) || empty($courses)) { // If 0 courses are selected, show message and form again
                    $returnurl = new moodle_url('/admin/tool/coursearchiver/step2.php', array("formdata" => $formdata, "error" => get_string('nocoursesselected', 'tool_coursearchiver')));
                    redirect($returnurl); 
                }
                $processor = new tool_coursearchiver_processor(array("mode" => $mode, "data" => $courses));
                if(!empty($folder)){
                    $processor->folder = $folder;    
                }
                $processor->execute(tool_coursearchiver_tracker::OUTPUT_HTML, null);
                echo $OUTPUT->footer();            
                break;
            default:
                $returnurl = new moodle_url('/admin/tool/coursearchiver/index.php', array("error" => get_string('unknownerror', 'tool_coursearchiver')));
                redirect($returnurl);
        }
    }
     
} else if(!empty($formdata) && !empty($mode)){  // FORM 3 SUBMITTED, SHOW FORM 4      
    header('X-Accel-Buffering: no');
    echo $OUTPUT->header();
    echo $OUTPUT->heading_with_help(get_string('coursearchiver', 'tool_coursearchiver'), 'coursearchiver', 'tool_coursearchiver');
    
    if (!empty($error)) {
        echo $OUTPUT->container($error, 'myformerror');   
    }
    
    $param = array("mode" => $mode, "formdata" => $formdata);
    $mform = new tool_coursearchiver_step4_form(null, array("processor_data" => $param));

    $mform->display();
    
    echo $OUTPUT->footer();
} else { // IN THE EVENT OF A FAILURE, JUST GO BACK TO THE BEGINNING
    $returnurl = new moodle_url('/admin/tool/coursearchiver/index.php', array("error" => get_string('unknownerror', 'tool_coursearchiver')));
    redirect($returnurl);   
}