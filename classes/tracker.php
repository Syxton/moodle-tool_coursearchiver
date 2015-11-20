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
 * Output tracker.
 *
 * @package    tool_coursearchiver
 * @copyright  2015 Matthew Davidson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/weblib.php');

/**
 * Tracker class
 *
 * @package    tool_coursearchiver
 * @copyright  2015 Matthew Davidson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_coursearchiver_tracker {

    /**
     * Constant to output nothing.
     */
    const NO_OUTPUT = 0;

    /**
     * Constant to output HTML.
     */
    const OUTPUT_HTML = 1;

    /**
     * Constant to output for command line.
     */
    const OUTPUT_CLI = 2;

    /**
     * @var int size of job to be done.
     */
    public $jobsize = 0;

    /**
     * @var int tracks the jobs done.
     */
    public $jobsdone = 0;

    /**
     * @var int tracks the progress as percentage.
     */
    protected $progress = 0;

    /**
     * @var int chosen output mode.
     */
    protected $outputmode;

    /**
     * @var object output buffer.
     */
    protected $buffer;

    /**
     * @var object form object.
     */
    public $form;

    /**
     * @var object instance of form object.
     */
    public $mform;

    /**
     * @var int current mode.
     */
    public $mode;

    /**
     * @var bool flag for errors.
     */
    public $error = false;

    /**
     * @var book flag for empty courses.
     */
    public $empty = false;


    /**
     * @var string masks for cli output columns.
     */
    protected $maskcourses = "%-7.7s %-10.10s %-20.20s %-30.30s %-8.8s";

    /**
     * @var string masks for cli output columns.
     */
    protected $maskcourseheader = "+++ %-6.6s: %-20.20s %-37.37s";

    /**
     * @var string masks for cli output columns.
     */
    protected $maskusers = "%-16.16s %-24.24s %-30.30s";

    /**
     * Constructor.
     *
     * @param int $outputmode desired output mode.
     * @param int $mode current mode selected, defaluts to MODE_COURSELIST
     */
    public function __construct($outputmode = self::NO_OUTPUT, $mode = tool_coursearchiver_processor::MODE_COURSELIST) {
        $this->outputmode = $outputmode;
        $this->mode = $mode;
        $this->buffer = new progress_trace_buffer(new text_progress_trace());

    }


    /**
     * Output the results.
     *
     * @param int $mode process mode.
     * @param int $total amount actually done.
     * @param array $errors a list of errors.
     * @param array $notices a list of notices
     * @return void
     */
    public function results($mode, $total, $errors, $notices) {
        if ($this->outputmode == self::NO_OUTPUT) {
            return;
        }

        switch ($mode) {
            case tool_coursearchiver_processor::MODE_COURSELIST:
                $modetext = "courselist";
                break;
            case tool_coursearchiver_processor::MODE_GETEMAILS:
                $modetext = "getemails";
                break;
            case tool_coursearchiver_processor::MODE_HIDEEMAIL:
                $modetext = "hideemail";
                break;
            case tool_coursearchiver_processor::MODE_ARCHIVEEMAIL:
                $modetext = "archiveemail";
                break;
            case tool_coursearchiver_processor::MODE_HIDE:
                $modetext = "hide";
                break;
            case tool_coursearchiver_processor::MODE_ARCHIVE:
                $modetext = "archive";
                break;
            default:
                throw new Exception('Mode not given for results.');
                return;
        }

        $message = array(
            get_string('results_'.$modetext, 'tool_coursearchiver', $total),
            get_string('notices_count', 'tool_coursearchiver', count($notices)),
            get_string('errors_count', 'tool_coursearchiver', count($errors))
        );

        $buffer = new progress_trace_buffer(new text_progress_trace());
        if ($this->outputmode == self::OUTPUT_CLI) {
            $buffer->output("\n".get_string('results', 'tool_coursearchiver')."\n");
            foreach ($message as $msg) {
                $buffer->output($msg);
            }

            if (!empty($errors)) {
                $buffer->output("\n".get_string('errors', 'tool_coursearchiver')."\n");
                foreach ($errors as $error) {
                    $buffer->output($error);
                }
            }

            if (!empty($notices)) {
                $buffer->output("\n".get_string('notices', 'tool_coursearchiver')."\n");
                foreach ($notices as $notice) {
                    $buffer->output($notice);
                }
            }

        } else if ($this->outputmode == self::OUTPUT_HTML) {
            $buffer->output('<div class="coursearchiver_stats"><strong>' .
                            get_string('results', 'tool_coursearchiver') .
                            ':</strong><br />');
            foreach ($message as $msg) {
                $buffer->output($msg);
                $buffer->output('<br />');
            }

            if (!empty($errors)) {
                $buffer->output('<div class="coursearchiver_error_text"><strong>' .
                                get_string('errors', 'tool_coursearchiver') .
                                ':</strong><br />');
                foreach ($errors as $error) {
                    $buffer->output('<div>' . $error . '</div>');
                }
                $buffer->output('</div><br />');
            }

            if (!empty($notices)) {
                $buffer->output('<div class="coursearchiver_notice_text"><strong>' .
                                get_string('notices', 'tool_coursearchiver') .
                                ':</strong><br />');
                foreach ($notices as $notice) {
                    $buffer->output('<div>' . $notice . '</div>');
                }
                $buffer->output('</div><br />');
            }
            $buffer->output('</div><br />');
        }
    }

    /**
     * Start the output.
     *
     * @return void
     */
    public function start() {
        if ($this->outputmode == self::NO_OUTPUT) {
            return;
        }

        if ($this->outputmode == self::OUTPUT_CLI) {
            switch ($this->mode) {
                case tool_coursearchiver_processor::MODE_COURSELIST:
                    $this->buffer->output("\n\n" . str_repeat('-', 50));
                    $this->buffer->output("Search Results");
                    $this->buffer->output(sprintf($this->maskcourses, 'Status', 'ID', 'Shortname', 'Fullname', 'Last Use'));
                    break;
                case tool_coursearchiver_processor::MODE_GETEMAILS:
                    $this->buffer->output("\n\n" . str_repeat('-', 50));
                    $this->buffer->output("Course Owners");
                    $this->buffer->output(sprintf($this->maskusers, 'Firstname', 'Lastname', 'Email'));
                    break;
                case tool_coursearchiver_processor::MODE_HIDE:
                case tool_coursearchiver_processor::MODE_ARCHIVE:
                case tool_coursearchiver_processor::MODE_HIDEEMAIL:
                case tool_coursearchiver_processor::MODE_ARCHIVEEMAIL:
                    break;
            }
        } else if ($this->outputmode == self::OUTPUT_HTML) {

            switch ($this->mode) {
                case tool_coursearchiver_processor::MODE_COURSELIST:
                    $style = '<style>
                                .fitemtitle {
                                    width: auto !important;
                                }
                                .felement {
                                    margin: 0 !important;
                                    display: inline;
                                }
                                th .fitem {
                                    display: inline-block;
                                }
                            </style>';
                    $this->mform->addElement('html', $style .
                                                     html_writer::start_tag('table', array('style' => 'width:100%')) .
                                                         html_writer::start_tag('tr', array('style' => 'text-align:left;')) .
                                                             html_writer::tag('th',
                                                                    get_string('outselected', 'tool_coursearchiver'),
                                                                    array('style' => 'width:10%;text-align:center;')) .
                                                             html_writer::tag('th',
                                                                    get_string('outid', 'tool_coursearchiver'),
                                                                    array('style' => 'width:10%')) .
                                                            html_writer::tag('th',
                                                                    get_string('outfullname', 'tool_coursearchiver'),
                                                                    array('style' => 'width:38%')) .
                                                             html_writer::tag('th',
                                                                    get_string('outshortname', 'tool_coursearchiver'),
                                                                    array('style' => 'width:22%')) .
                                                             html_writer::tag('th',
                                                                    get_string('outidnumber', 'tool_coursearchiver'),
                                                                    array('style' => 'width:10%')) .
                                                             html_writer::tag('th',
                                                                    get_string('outaccess', 'tool_coursearchiver'),
                                                                    array('style' => 'width:10%;text-align:center;')) .
                                                         html_writer::end_tag('tr') .
                                                     html_writer::end_tag('table'));
                    break;
                case tool_coursearchiver_processor::MODE_GETEMAILS:
                    $style = '<style>
                                .fitemtitle {
                                    width: auto !important;
                                }
                                .felement {
                                    margin: 0 !important;
                                    display: inline;
                                }
                                th .fitem {
                                    display: inline-block;
                                }
                                .courseheader td {
                                    background-color: #F5F5F5;
                                    color: #7D7D7D;
                                    padding: 10px;
                                }
                            </style>';
                    $this->mform->addElement('html', $style .
                                                     html_writer::start_tag('table', array('style' => 'width:100%')) .
                                                     html_writer::start_tag('tr', array('style' => 'text-align:left;')) .
                                                     html_writer::tag('th',
                                                            get_string('outselected', 'tool_coursearchiver'),
                                                            array('style' => 'width:10%;text-align:center;')) .
                                                     html_writer::tag('th',
                                                            get_string('outemail', 'tool_coursearchiver'),
                                                            array('style' => 'width:40%')) .
                                                     html_writer::tag('th',
                                                            get_string('outfirstname', 'tool_coursearchiver'),
                                                            array('style' => 'width:15%')) .
                                                     html_writer::tag('th', get_string('outlastname', 'tool_coursearchiver')) .
                                                     html_writer::end_tag('tr') .
                                                     html_writer::end_tag('table'));
                    break;
                case tool_coursearchiver_processor::MODE_HIDE:
                    $buffer = new progress_trace_buffer(new text_progress_trace());
                    $buffer->output('<h3>Hiding selected courses</h3><div style="margin-bottom: 60px;"></div><br />');
                    $buffer->finished();
                    break;
                case tool_coursearchiver_processor::MODE_ARCHIVE:
                    $buffer = new progress_trace_buffer(new text_progress_trace());
                    $buffer->output('<h3>Archiving selected courses</h3><div style="margin-bottom: 60px;"></div><br />');
                    $buffer->finished();
                    break;
                case tool_coursearchiver_processor::MODE_HIDEEMAIL:
                case tool_coursearchiver_processor::MODE_ARCHIVEEMAIL:
                    $buffer = new progress_trace_buffer(new text_progress_trace());
                    $buffer->output('<h3>Sending Emails</h3><div style="margin-bottom: 60px;"></div><br />');
                    $buffer->finished();
                    break;
            }
        }
    }

    /**
     * Output one more line.
     *
     * @param array $data array of data dependant on the mode.
     * @param array $info extra data to display or use.
     * @return void
     */
    public function output($data, $info = false) {
        global $CFG;

        $return = 1; // By default we are returning the a single process as finished.

        if ($this->outputmode == self::NO_OUTPUT) {
            return;
        }

        if ($this->outputmode == self::OUTPUT_CLI) {
            switch ($this->mode) {
                case tool_coursearchiver_processor::MODE_COURSELIST:
                    $cliicon = empty($data->visible) ? "hide " : "show ";
                    $empty = $this->empty ? "MT " : "";
                    $this->buffer->output(sprintf($this->maskcourses,
                                                  $cliicon . $empty,
                                                  $data->id,
                                                  $data->shortname,
                                                  $data->fullname,
                                                  empty($data->timeaccess) ? "Never" : date("m/d/y", $data->timeaccess)));
                    break;
                case tool_coursearchiver_processor::MODE_GETEMAILS:
                    if ($info) {
                        $this->buffer->output("\n" . sprintf($this->maskcourseheader,
                                              'Course ',
                                              $data["course"]->shortname,
                                              $data["course"]->fullname));
                        if (empty($data["owners"])) {
                            $this->buffer->output('--- '.get_string('nousersfound', 'tool_coursearchiver').' ---');
                        }
                    } else {
                        $this->buffer->output(sprintf($this->maskusers, $data->firstname, $data->lastname, $data->email));
                    }
                    break;
                case tool_coursearchiver_processor::MODE_HIDE:
                case tool_coursearchiver_processor::MODE_ARCHIVE:
                case tool_coursearchiver_processor::MODE_HIDEEMAIL:
                case tool_coursearchiver_processor::MODE_ARCHIVEEMAIL:
                    $out = $this->get_progressbar();
                    do {
                        $this->buffer->output($out);
                        if ($this->progress == 100) {
                            return;
                        }
                        $out = $this->get_progressbar();
                    } while ($out);
                    break;
            }
        } else if ($this->outputmode == self::OUTPUT_HTML) {
            switch ($this->mode) {
                case tool_coursearchiver_processor::MODE_COURSELIST:
                    $fullname = $this->empty ? '<strike>' . $data->fullname . '</strike>' : $data->fullname;
                    $empty = $this->empty ? 'title="Empty Course"' : '';
                    $this->mform->addElement('html', html_writer::start_tag('table', array('style' => 'width:100%')) .
                                                     html_writer::start_tag('tr') .
                                                     html_writer::start_tag('td',
                                                            array('style' => 'width:10%;text-align:center;')));
                    $this->mform->addElement('advcheckbox',
                                             'course_selected[]',
                                             '',
                                             null,
                                             array('group' => 1),
                                             array(0, $data->id));
                    $this->mform->setDefault('course_selected[]', 0);
                    $this->mform->addElement('html', html_writer::end_tag('td') .
                                                     html_writer::tag('td', $data->id, array('style' => 'width:10%')) .
                                                     html_writer::tag('td',
                                                        '<a ' . $empty . ' href="' . $CFG->wwwroot . '/course/view.php?id=' .
                                                            $data->id.'">' . $fullname . '</a>',
                                                        array('style' => 'width:38%',
                                                          'class' => empty($data->visible) ? 'coursearchiver_alreadyhidden' : '')) .
                                                     html_writer::tag('td',
                                                        $data->shortname,
                                                        array('style' => 'width:22%',
                                                          'class' => empty($data->visible) ? 'coursearchiver_alreadyhidden' : '')) .
                                                     html_writer::tag('td',
                                                        $data->idnumber,
                                                        array('style' => 'width:10%',
                                                          'class' => empty($data->visible) ? 'coursearchiver_alreadyhidden' : '')) .
                                                     html_writer::tag('td',
                                                        empty($data->timeaccess) ? "Never" : date('m/d/Y', $data->timeaccess),
                                                        array('style' => 'width:10%;text-align:center',
                                                          'class' => empty($data->visible) ? 'coursearchiver_alreadyhidden' : '')) .
                                                     html_writer::end_tag('tr') .
                                                     html_writer::end_tag('table'));
                    break;
                case tool_coursearchiver_processor::MODE_GETEMAILS:
                    if ($info) {
                        $this->mform->addElement('html', '<br />' .
                                                         html_writer::start_tag('table', array('style' => 'width:100%',
                                                                                               'class' => 'courseheader')) .
                                                         html_writer::start_tag('tr') .
                                                         html_writer::tag('td',
                                                         '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$data["course"]->id.'">'.
                                                         $data["course"]->fullname . ' (' . $data["course"]->shortname . ')</a>',
                                                         array('style' => 'width:30%;text-align:left;font-weight:bold;')) .
                                                         html_writer::end_tag('tr') .
                                                         html_writer::end_tag('table'));

                        if (empty($data["owners"])) {
                            $this->mform->addElement('html', html_writer::start_tag('table', array('style' => 'width:100%')) .
                                                             html_writer::start_tag('tr') .
                                                             html_writer::tag('td',
                                                                    get_string('nousersfound', 'tool_coursearchiver'),
                                                                    array('style' => '',
                                                                          'class' => 'coursearchiver_myformerror')) .
                                                             html_writer::end_tag('tr'),
                                                             html_writer::end_tag('table'));
                        }
                    } else {
                        $this->mform->addElement('html', html_writer::start_tag('table',
                                                            array('style' => 'width:100%')) .
                                                         html_writer::start_tag('tr') .
                                                         html_writer::start_tag('td',
                                                            array('style' => 'width:10%;text-align:center;')));
                        $this->mform->addElement('advcheckbox',
                                                 'user_selected[]',
                                                 '',
                                                 null,
                                                 array('group' => 1),
                                                 array(0, $data->course . "_" . $data->id));
                        $this->mform->setDefault('user_selected[]', 1);
                        $this->mform->addElement('html', html_writer::end_tag('td') .
                                                         html_writer::tag('td',
                                                                          $data->email,
                                                                          array('style' => 'width:40%')) .
                                                         html_writer::tag('td',
                                                                          $data->firstname,
                                                                          array('style' => 'width:15%')) .
                                                         html_writer::tag('td', $data->lastname) .
                                                         html_writer::end_tag('tr') .
                                                         html_writer::end_tag('table'));
                    }
                    break;
                case tool_coursearchiver_processor::MODE_HIDE:
                case tool_coursearchiver_processor::MODE_ARCHIVE:
                case tool_coursearchiver_processor::MODE_HIDEEMAIL:
                case tool_coursearchiver_processor::MODE_ARCHIVEEMAIL:
                    $this->buffer->output($this->get_progressbar());
                    break;
            }
        }
        return $return;
    }

    /**
     * Finish the output.
     *
     * @return void
     */
    public function finish() {
        if ($this->outputmode == self::NO_OUTPUT) {
            return;
        }

        if ($this->outputmode == self::OUTPUT_CLI) {
            $this->buffer->output(str_repeat('-', 50));
        }

        if ($this->outputmode == self::OUTPUT_HTML) {
            switch ($this->mode) {
                case tool_coursearchiver_processor::MODE_COURSELIST:
                    if ($this->jobsdone > 1) {
                        $this->form->add_checkbox_controller(1);
                    } else if (empty($this->jobsize)) {
                        $this->mform->addElement('html', html_writer::start_tag('table', array('style' => 'width:100%')) .
                                                         html_writer::start_tag('tr') .
                                                         html_writer::tag('td',
                                                                        get_string('nocoursesfound', 'tool_coursearchiver'),
                                                                        array('class' => 'coursearchiver_myformerror')) .
                                                         html_writer::end_tag('tr') .
                                                         html_writer::end_tag('table'));
                    }
                    break;
                case tool_coursearchiver_processor::MODE_GETEMAILS:
                    if ($this->jobsdone > 1) {
                        $this->form->add_checkbox_controller(1);
                    } else if (empty($this->jobsize)) {
                        $this->mform->addElement('html', html_writer::start_tag('table', array('style' => 'width:100%')) .
                                                         html_writer::start_tag('tr') .
                                                         html_writer::tag('td',
                                                                        get_string('nousersfound', 'tool_coursearchiver'),
                                                                        array('class' => 'coursearchiver_myformerror')) .
                                                         html_writer::end_tag('tr') .
                                                         html_writer::end_tag('table'));
                    }
                    break;
                case tool_coursearchiver_processor::MODE_HIDE:
                case tool_coursearchiver_processor::MODE_ARCHIVE:
                case tool_coursearchiver_processor::MODE_HIDEEMAIL:
                case tool_coursearchiver_processor::MODE_ARCHIVEEMAIL:
                    $this->buffer->output('<div class="coursearchiver_completedmsg">Process Complete</div>');
                    break;
            }
        }
        $this->buffer->finished();
    }

    /**
     * Createes a progressbar to be displayed.
     *
     * @return string
     */
    protected function get_progressbar() {
        $percentage = number_format(($this->jobsdone / $this->jobsize) * 100, 0);

        if ($this->outputmode == self::OUTPUT_CLI) {
            if ($this->progress == 0) {
                $this->progress = .0001;
                return '0%';
            } else if ($percentage >= 20 && $this->progress < 20) {
                $this->progress = 20;
                return '20%    ____   ___   __  __  ____   _      _____  _____  _____ ';
            } else if ($percentage >= 40 && $this->progress < 40) {
                $this->progress = 40;
                return '40%   / ___| / _ \ |  \/  ||  _ \ | |    | ____||_   _|| ____|';
            } else if ($percentage >= 60 && $this->progress < 60) {
                $this->progress = 60;
                return '60%  | |    | | | || |\/| || |_) || |    |  _|    | |  |  _|  ';
            } else if ($percentage >= 80 && $this->progress < 80) {
                $this->progress = 80;
                return '80%  | |___ | |_| || |  | ||  __/ | |___ | |___   | |  | |___ ';
            } else if ($percentage == 100) {
                $this->progress = 100;
                return '100%  \____| \___/ |_|  |_||_|    |_____||_____|  |_|  |_____|';
            }
            return false;
        } else if ($this->outputmode == self::OUTPUT_HTML) {
            $this->progress = $percentage;
            return '
            <div class="coursearchiver_progress_bar">
                <div class="coursearchiver_bar" style="width:'.$percentage.'%;">'.$percentage.'%</div>
            </div>';
        }
    }
}
