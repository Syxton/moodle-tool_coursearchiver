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
 * Bulk course upload step 4 form.
 *
 * @package    tool_coursearchiver
 * @copyright  2015 Matthew Davidson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Moodle form for step 4 of course archive tool
 *
 * @package    tool_coursearchiver
 * @copyright  2015 Matthew Davidson
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
        if (empty($count)) {
            $returnurl = new moodle_url('/admin/tool/coursearchiver/index.php',
                                        array("error" => get_string('unknownerror', 'tool_coursearchiver')));
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
                $returnurl = new moodle_url('/admin/tool/coursearchiver/index.php',
                                            array("error" => get_string('unknownerror', 'tool_coursearchiver')));
                redirect($returnurl);
        }

        $mform->addElement('html',
                           '<div class="coursearchiver_myformconfirm">' .
                                get_string('confirmmessage', 'tool_coursearchiver', $message) .
                           '</div>');

        if ($data["mode"] == tool_coursearchiver_processor::MODE_ARCHIVE) {
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
