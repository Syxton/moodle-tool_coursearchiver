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
 * Step 1 form.
 *
 * @package    tool_coursearchiver
 * @copyright  2015 Matthew Davidson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * Moodle form for step 1 of course archive tool.
 *
 * @package    tool_coursearchiver
 * @copyright  2015 Matthew Davidson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_coursearchiver_step1_form extends moodleform {

    /**
     * The standard form definiton.
     * @return void
     */
    public function definition () {
        $mform = $this->_form;
        $mform->addElement('header', 'searchhdr', get_string('search'));

        $mform->addElement('html', '<div style="float: right;">');
        $mform->addElement('html', '<a href="../../settings.php?section=tool_coursearchiver"  target="_blank">' .
            '<i class="fa fa-gear"></i> ' . get_string('coursearchiver_settings', 'tool_coursearchiver') . '</a>');
        $mform->addElement('html', '</div><div style="clear: both;"></div>');

        $mform->addElement('select',
                           'savestates',
                           get_string('resume', 'tool_coursearchiver'),
                           tool_coursearchiver_processor::get_saves());

        $mform->addElement('checkbox', 'emptyonly', get_string('emptyonly', 'tool_coursearchiver'));

        $mform->addElement('text', 'searches[id]', get_string('courseid', 'tool_coursearchiver'));
        $mform->setType('searches[id]', PARAM_TEXT);
        $mform->addRule('searches[id]', null, 'numeric', null, 'client');
        $mform->setDefault('searches[id]', "");

        $mform->addElement('text', 'searches[short]', get_string('courseshortname', 'tool_coursearchiver'));
        $mform->setType('searches[short]', PARAM_TEXT);
        $mform->setDefault('searches[short]', "");

        $mform->addElement('text', 'searches[full]', get_string('coursefullname', 'tool_coursearchiver'));
        $mform->setType('searches[full]', PARAM_TEXT);
        $mform->setDefault('searches[full]', "");

        $mform->addElement('text', 'searches[idnum]', get_string('courseidnum', 'tool_coursearchiver'));
        $mform->setType('searches[idnum]', PARAM_TEXT);
        $mform->setDefault('searches[idnum]', "");

        $mform->addElement('text', 'searches[teacher]', get_string('courseteacher', 'tool_coursearchiver'));
        $mform->setType('searches[teacher]', PARAM_TEXT);
        $mform->setDefault('searches[teacher]', "");

        $displaylist = [get_string('anycategory', 'tool_coursearchiver')];

        // Moodle < 3.6 compatibility.
        if (!class_exists('core_course_category')) {
            $displaylist += coursecat::make_categories_list('moodle/course:create');
        } else {
            $displaylist += core_course_category::make_categories_list('moodle/course:create');
        }

        $mform->addElement('select', 'searches[catid]', get_string('category', 'tool_coursearchiver'), $displaylist);
        $mform->setDefault('searches[catid]', "");

        $mform->addElement('checkbox', 'subcats', get_string('includesubcat', 'tool_coursearchiver'));
        $mform->disabledIf('subcats', 'searches[catid]', 'eq', 0);

        $mform->addElement('header', 'timecreated', get_string('createdbeforeafter', 'tool_coursearchiver'));

        $createdbefore = [];
        $createdbefore[] =& $mform->createElement('date_selector', 'createdbefore');
        $createdbefore[] =& $mform->createElement('checkbox', 'createdbeforeenabled', '', get_string('enable'));
        $mform->addGroup($createdbefore, 'createdbefore', get_string('createdbefore', 'tool_coursearchiver'), ' ', false);
        $mform->disabledIf('createdbefore', 'createdbeforeenabled');

        $createdafter = [];
        $createdafter[] =& $mform->createElement('date_selector', 'createdafter');
        $createdafter[] =& $mform->createElement('checkbox', 'createdafterenabled', '', get_string('enable'));
        $mform->addGroup($createdafter, 'createdafter', get_string('createdafter', 'tool_coursearchiver'), ' ', false);
        $mform->disabledIf('createdafter', 'createdafterenabled');

        $mform->addElement('header', 'timeaccessed', get_string('accessbeforeafter', 'tool_coursearchiver'));

        $accessbeforegroup = [];
        $accessbeforegroup[] =& $mform->createElement('date_selector', 'accessbefore');
        $accessbeforegroup[] =& $mform->createElement('checkbox', 'accessbeforeenabled', '', get_string('enable'));
        $mform->addGroup($accessbeforegroup, 'accessbeforegroup', get_string('accessbefore', 'tool_coursearchiver'), ' ', false);
        $mform->disabledIf('accessbeforegroup', 'accessbeforeenabled');

        $accessaftergroup = [];
        $accessaftergroup[] =& $mform->createElement('date_selector', 'accessafter');
        $accessaftergroup[] =& $mform->createElement('checkbox', 'accessafterenabled', '', get_string('enable'));
        $mform->addGroup($accessaftergroup, 'accessaftergroup', get_string('accessafter', 'tool_coursearchiver'), ' ', false);
        $mform->disabledIf('accessaftergroup', 'accessafterenabled');

        $mform->addElement('checkbox', 'ignadmins', get_string('ignoreadmins', 'tool_coursearchiver'));
        $mform->addElement('checkbox', 'ignsiteroles', get_string('ignoresiteroles', 'tool_coursearchiver'));

        $mform->addElement('header', 'timestarted', get_string('startend', 'tool_coursearchiver'));

        $startbeforegroup = [];
        $startbeforegroup[] =& $mform->createElement('date_selector', 'startbefore');
        $startbeforegroup[] =& $mform->createElement('checkbox', 'startbeforeenabled', '', get_string('enable'));
        $mform->addGroup($startbeforegroup, 'startbeforegroup', get_string('startbefore', 'tool_coursearchiver'), ' ', false);
        $mform->disabledIf('startbeforegroup', 'startbeforeenabled');

        $startaftergroup = [];
        $startaftergroup[] =& $mform->createElement('date_selector', 'startafter');
        $startaftergroup[] =& $mform->createElement('checkbox', 'startafterenabled', '', get_string('enable'));
        $mform->addGroup($startaftergroup, 'startaftergroup', get_string('startafter', 'tool_coursearchiver'), ' ', false);
        $mform->disabledIf('startaftergroup', 'startafterenabled');

        $endbeforegroup = [];
        $endbeforegroup[] =& $mform->createElement('date_selector', 'endbefore');
        $endbeforegroup[] =& $mform->createElement('checkbox', 'endbeforeenabled', '', get_string('enable'));
        $mform->addGroup($endbeforegroup, 'endbeforegroup', get_string('endbefore', 'tool_coursearchiver'), ' ', false);
        $mform->disabledIf('endbeforegroup', 'endbeforeenabled');

        $endaftergroup = [];
        $endaftergroup[] =& $mform->createElement('date_selector', 'endafter');
        $endaftergroup[] =& $mform->createElement('checkbox', 'endafterenabled', '', get_string('enable'));
        $mform->addGroup($endaftergroup, 'endaftergroup', get_string('endafter', 'tool_coursearchiver'), ' ', false);
        $mform->disabledIf('endaftergroup', 'endafterenabled');

        $this->add_action_buttons(false, get_string('search', 'tool_coursearchiver'));
        $this->add_action_buttons(false, get_string('optoutlist', 'tool_coursearchiver'));
        $this->add_action_buttons(false, get_string('savestatelist', 'tool_coursearchiver'));
        $this->add_action_buttons(false, get_string('archivelist', 'tool_coursearchiver'));
    }

    /**
     * Validate search form.
     *
     * @param array $data array of form field data.
     * @param array $files optional form file uploads.
     * @return void
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $searchstring = "";

        if (empty($data["savestates"])) {
            foreach ($data["searches"] as $value) {
                $searchstring .= $value;
            }

            if (!empty($data["createdbeforeenabled"])) {
                $searchstring .= "createdbefore";
            }

            if (!empty($data["createdafterenabled"])) {
                $searchstring .= "createdafter";
            }

            if (!empty($data["accessbeforeenabled"])) {
                $searchstring .= "accessbefore";
            }

            if (!empty($data["accessafterenabled"])) {
                $searchstring .= "accessafter";
            }

            if (!empty($data["startbeforeenabled"])) {
                $searchstring .= "startbefore";
            }

            if (!empty($data["startafterenabled"])) {
                $searchstring .= "startafter";
            }

            if (!empty($data["endbeforeenabled"])) {
                $searchstring .= "endbefore";
            }

            if (!empty($data["endafterenabled"])) {
                $searchstring .= "endafter";
            }

            if (!empty($data["emptyonly"])) {
                $searchstring .= "emptyonly";
            }

            if (empty($searchstring)) {
                $errors['step'] = get_string('erroremptysearch', 'tool_coursearchiver');
            }
        }
        return $errors;
    }
}
