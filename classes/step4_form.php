<?php
/**
 * Bulk course upload step 4 form.
 *
 * @package    tool_coursearchiver
 * @copyright  2015 Matthew Davidson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Specify course upload details.
 *
 * @package    tool_coursearchiver
 * @copyright  2011 Piers Harding
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_coursearchiver_step4_form extends moodleform {

    /**
     * The standard form definiton.
     * @return void.
     */
    public function definition () {
        $mform = $this->_form;
        $data  = $this->_customdata['processor_data'];

        $mform->addElement('hidden', 'formdata');
        $mform->setType('formdata', PARAM_RAW);
        $mform->setDefault('formdata', $data['formdata']);
        
        $mform->addElement('hidden', 'mode');
        $mform->setType('mode', PARAM_INT);
        $mform->setDefault('mode', $data['mode']);
        
        $count = count(unserialize($data["formdata"]));
        if(empty($count)) {
            $returnurl = new moodle_url('/admin/tool/coursearchiver/index.php', array("error" => get_string('unknownerror', 'tool_coursearchiver')));
            redirect($returnurl);    
        }
        
        switch($data["mode"]) {
            case tool_coursearchiver_processor::MODE_HIDEEMAIL:
                $message = get_string('confirmmessagehideemail', 'tool_coursearchiver', $count);
                break;
            case tool_coursearchiver_processor::MODE_ARCHIVEEMAIL:
                $message = get_string('confirmmessagearchiveemail', 'tool_coursearchiver', $count);
                break;
            case tool_coursearchiver_processor::MODE_HIDE:
                $message = get_string('confirmmessagehide', 'tool_coursearchiver', $count);
                break;
            case tool_coursearchiver_processor::MODE_ARCHIVE:
                $message = get_string('confirmmessagearchive', 'tool_coursearchiver', $count);
                break;
            default:
                $returnurl = new moodle_url('/admin/tool/coursearchiver/index.php', array("error" => get_string('unknownerror', 'tool_coursearchiver')));
                redirect($returnurl);
        }

        $mform->addElement('html', '<div class="myformconfirm">' . get_string('confirmmessage', 'tool_coursearchiver', $message) . '</div>');
        
        if($data["mode"] == tool_coursearchiver_processor::MODE_ARCHIVE) {
            $mform->addElement('text', 'folder', get_string('archivelocation', 'tool_coursearchiver'));
            $mform->setType('folder', PARAM_TEXT);
            $mform->setDefault('folder', date('Y'));
        }
        
        $buttonarray = array();        
            $buttonarray[] = &$mform->createElement('submit', 'submit_button', get_string('back', 'tool_coursearchiver'));
            $buttonarray[] = &$mform->createElement('submit', 'submit_button', get_string('confirm', 'tool_coursearchiver'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');           

        $this->set_data($data);
    }
}
