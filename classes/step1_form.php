<?php
/**
 * Bulk course upload step 1 form.
 *
 * @package    tool_coursearchiver
 * @copyright  2015 Matthew Davidson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

class tool_coursearchiver_step1_form extends moodleform {

    /**
     * The standard form definiton.
     * @return void
     */
    public function definition () {
        $mform = $this->_form;
        $mform->addElement('header', 'searchhdr', get_string('search'));
        
        $mform->addElement('text', 'searches[short]', get_string('courseshortname', 'tool_coursearchiver'));
        $mform->setType('searches[short]', PARAM_TEXT);
        $mform->setDefault('searches[short]', "");
        
        $mform->addElement('text', 'searches[full]', get_string('coursefullname', 'tool_coursearchiver'));
        $mform->setType('searches[full]', PARAM_TEXT);
        $mform->setDefault('searches[full]', "");

        $mform->addElement('text', 'searches[idnum]', get_string('courseidnum', 'tool_coursearchiver'));
        $mform->setType('searches[idnum]', PARAM_TEXT);
        $mform->setDefault('searches[idnum]', "");
        
        $mform->addElement('text', 'searches[id]', get_string('courseid', 'tool_coursearchiver'));
        $mform->setType('searches[id]', PARAM_TEXT);
        $mform->addRule('searches[id]', null, 'numeric', null, 'client');
        $mform->setDefault('searches[id]', "");
        
        $lastaccessgroup = array();
        $lastaccessgroup[] =& $mform->createElement('date_selector', 'access');
        $lastaccessgroup[] =& $mform->createElement('checkbox', 'lastaccessenabled', '', get_string('enable'));
        $mform->addGroup($lastaccessgroup, 'lastaccessgroup', get_string('access', 'tool_coursearchiver'), ' ', false);
        $mform->disabledIf('lastaccessgroup', 'lastaccessenabled');
    
        $mform->addElement('checkbox', 'emptyonly', get_string('emptyonly', 'tool_coursearchiver'));
        
        $this->add_action_buttons(false, get_string('search', 'tool_coursearchiver'));
    }
    
    // Make sure at least 1 of the search fields is not empty
    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        $searchstring = "";
        $timecode = "";

        foreach ($data["searches"] as $key => $value){
            $searchstring .= $value;
        }
        
        if(!empty($data["lastaccessenabled"])){
            $timecode = mktime(null,null,null,$data["access"]["month"], $data["access"]["day"],$data["access"]["year"]);    
        }
        $searchstring .= $timecode;

        if(!empty($data["emptyonly"])){
            $searchstring .= "emptyonly";    
        }

        if (empty($searchstring)) {
            $errors['step'] = get_string('erroremptysearch', 'tool_coursearchiver');
        }

        return $errors;
    }
}
