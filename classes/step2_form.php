<?php
/**
 * Bulk course upload step 2 form.
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
class tool_coursearchiver_step2_form extends moodleform {

    /**
     * The standard form definiton.
     * @return void.
     */
    public function definition () {
        $mform = $this->_form;
        $mform->addElement('submit', 'submit_button', get_string('back', 'tool_coursearchiver'));
        $data  = $this->_customdata['processor_data'];

        $mform->addElement('hidden', 'formdata');
        $mform->setType('formdata', PARAM_RAW);
        $mform->setDefault('formdata', serialize($data['searches']));

        $mform->addElement('header', 'searchresultshdr', get_string('courseselector', 'tool_coursearchiver'));
        
        //do search here and display results
        $processor = new tool_coursearchiver_processor(array("mode" => $data["mode"], "data" => $data["searches"]));
        $processor->execute(tool_coursearchiver_tracker::OUTPUT_HTML, null, $mform, $this);
        
        if($processor->total > 0) {
            $buttonarray = array();        
                $buttonarray[] = &$mform->createElement('submit', 'submit_button', get_string('email', 'tool_coursearchiver'));
                $buttonarray[] = &$mform->createElement('submit', 'submit_button', get_string('hide', 'tool_coursearchiver'));
                $buttonarray[] = &$mform->createElement('submit', 'submit_button', get_string('archive', 'tool_coursearchiver'));
            $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
            $mform->closeHeaderBefore('buttonar');    
        }        

        $this->set_data($data);
    }
}
