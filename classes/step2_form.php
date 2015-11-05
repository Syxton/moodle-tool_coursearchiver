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
 * Step 2 form.
 *
 * @package    tool_coursearchiver
 * @copyright  2015 Matthew Davidson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Moodle form for step 2 of course archive tool
 *
 * @package    tool_coursearchiver
 * @copyright  2015 Matthew Davidson
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

        // Do search here and display results.
        $processor = new tool_coursearchiver_processor(array("mode" => $data["mode"], "data" => $data["searches"]));
        $processor->execute(tool_coursearchiver_tracker::OUTPUT_HTML, null, $mform, $this);

        if ($processor->total > 0) {
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
