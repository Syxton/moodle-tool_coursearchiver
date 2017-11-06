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
 * File containing processor class.
 *
 * @package    tool_coursearchiver
 * @copyright  2015 Matthew Davidson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Processor class.
 *
 * @package    tool_coursearchiver
 * @copyright  2015 Matthew Davidson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_coursearchiver_processor {

    /**
     * Only Show the Course list.
     */
    const MODE_COURSELIST = 1;

    /**
     * Get email address for the owners of selected courses.
     */
    const MODE_GETEMAILS = 2;

    /**
     * Hide courses.
     */
    const MODE_HIDE = 3;

    /**
     * Archive courses.
     */
    const MODE_ARCHIVE = 4;

    /**
     * Delete courses.
     */
    const MODE_DELETE = 7;

    /**
     * Send emails about pending course hides.
     */
    const MODE_HIDEEMAIL = 5;

    /**
     * Send emails about pending course archival.
     */
    const MODE_ARCHIVEEMAIL = 6;

    /** @var int processor mode. */
    protected $mode;

    /** @var int total processed. */
    public $total = 0;

    /** @var int total processed. */
    public $folder = false;

    /** @var int total processed. */
    public $emptyonly = false;

    /** @var int data passed into processor. */
    protected $data = array();

    /** @var array of errors. */
    protected $errors = array();

    /** @var array of notices. */
    protected $notices = array();

    /** @var bool whether the process has been started or not. */
    protected $processstarted = false;

    /** @var array list of viable search criteria. */
    protected $searchcriteria = array(
        "id" => "id",
        "short" => "shortname",
        "full" => "fullname",
        "idnum" => "idnumber",
        "teacher" => "teacher",
        "catid" => "category",
        "createdbefore" => "timecreated",
        "access" => "timeaccess",
        "emptyonly" => "emptyonly");

    /**
     * Constructor
     *
     * @param array $options options of the process
     */
    public function __construct(array $options) {

        if (!isset($options['mode']) || !in_array($options['mode'], array(self::MODE_COURSELIST,
                                                                          self::MODE_GETEMAILS,
                                                                          self::MODE_HIDE,
                                                                          self::MODE_ARCHIVE,
                                                                          self::MODE_DELETE,
                                                                          self::MODE_HIDEEMAIL,
                                                                          self::MODE_ARCHIVEEMAIL))) {
            throw new coding_exception('Unknown process mode');
        }

        // Force int to make sure === comparison work as expected.
        $this->mode     = (int)$options['mode'];
        $this->data     = (array)$options['data'];
        $this->reset();
    }

    /**
     * Execute the process.
     *
     * @param int $outputtype tracker output type.
     * @param object $tracker the output tracker to use.
     * @param object $mform moodle_form object to use (optional)
     * @param object $form $this moodle_form object to use (optional)
     * @return void
     */
    public function execute($outputtype = tool_coursearchiver_tracker::NO_OUTPUT, $tracker = null, $mform = null, $form = null) {
        if ($this->processstarted) {
            throw new coding_exception(get_string('processstarted', 'tool_coursearchiver'));
        }
        $this->processstarted = true;

        if (empty($tracker)) {
            $tracker = new tool_coursearchiver_tracker($outputtype, $this->mode);
        }

        if ($outputtype == tool_coursearchiver_tracker::OUTPUT_HTML) {
            if (!in_array($this->mode, array(self::MODE_HIDE,
                                             self::MODE_ARCHIVE,
                                             self::MODE_DELETE,
                                             self::MODE_HIDEEMAIL,
                                             self::MODE_ARCHIVEEMAIL))) {
                if (empty($mform)) {
                    throw new coding_exception(get_string('errornoform', 'tool_coursearchiver'));
                } else {
                    $tracker->form = $form;
                    $tracker->mform = $mform;
                }
            }
        }

        // We will most certainly need extra time and memory to process big files.
        core_php_time_limit::raise(0);
        raise_memory_limit(MEMORY_EXTRA);

        switch ($this->mode) {
            case self::MODE_COURSELIST:
                $tracker->start();
                if (!empty($this->data["resume"])) {
                    $courses = $this->recreate_courselist($this->data);
                } else {
                    $courses = $this->get_courselist();
                }

                $courselist = array();
                if (!empty($courses)) {
                    // Loop over the course array.
                    $tracker->jobsize = count($courses);
                    foreach ($courses as $currentcourse) {
                        $tracker->empty = $this->is_empty_course($currentcourse->id);
                        if (!$this->is_opted_out($currentcourse->id)) {
                            if ($this->emptyonly && $tracker->empty || !$this->emptyonly) {
                                $this->total++;
                                if (!empty($currentcourse->id)) {
                                    $tracker->error = false;
                                    $courselist[] = $currentcourse->id;
                                    $tracker->output($currentcourse);
                                } else {
                                    $tracker->error = true;
                                    $this->errors[] = get_string('error_nocourseid', 'tool_coursearchiver');
                                }
                                $tracker->jobsdone++;
                            } else {
                                $tracker->jobsize--;
                            }
                        } else {
                            $tracker->jobsize--;
                        }
                    }
                }
                $tracker->finish();
                $tracker->results($this->mode, $this->total, $this->errors, $this->notices);
                return $courselist;
                break;
            case self::MODE_GETEMAILS:
                $tracker->start();
                if (!empty($this->data["resume"])) {
                    $courses = $this->recreate_courseowners($this->data);
                } else {
                    $courses = $this->get_courses_and_their_owners();
                }

                if (!empty($courses)) {
                    $tracker->jobsize = count($courses);
                    $return = array();
                    $unique = array();
                    // Loop over the course array.
                    foreach ($courses as $currentcourse) {
                        if (!$this->is_opted_out($currentcourse["course"]->id)) {
                            $tracker->output($currentcourse, true); // Output course header.
                            if (!empty($currentcourse["owners"])) {
                                foreach ($currentcourse["owners"] as $owner) {
                                    $owner->course = $currentcourse["course"]->id;
                                    $tracker->output($owner);  // Output users.
                                    $unique[$owner->id] = $owner->id;
                                    $return[] = $currentcourse["course"]->id . "_" . $owner->id;
                                    $this->total++;
                                }
                            } else {
                                $tracker->jobsize--;
                            }
                            $tracker->jobsdone++;
                        } else {
                            $tracker->jobsize--;
                        }
                    }
                    $this->total = count($unique);
                    $tracker->finish();
                } else {
                    $this->errors[] = get_string('errorinsufficientdata', 'tool_coursearchiver');
                }
                $tracker->results($this->mode, $this->total, $this->errors, $this->notices);
                return $return;
                break;
            case self::MODE_HIDE:
                $tracker->start();
                $courses = $this->get_courses_and_their_owners();

                if (!empty($courses)) {
                    // Loop over the course array.
                    $tracker->jobsize = count($courses);
                    foreach ($courses as $currentcourse) {
                        if ($currentcourse["course"]->visible) {
                            if ($this->hidecourse($currentcourse)) {
                                $tracker->error = false;
                                $this->total++;
                            } else {
                                $tracker->error = true;
                                $this->errors[] = get_string('errorhidingcourse', 'tool_coursearchiver', $currentcourse["course"]);
                            }
                        }
                        $tracker->jobsdone++;
                        $tracker->output($currentcourse);
                    }
                    $tracker->finish();
                } else {
                    $tracker->jobsize = 1;
                    $tracker->jobsdone++;
                    $tracker->output(false);
                    $this->errors[] = get_string('errorinsufficientdata', 'tool_coursearchiver');
                }
                $tracker->results($this->mode, $this->total, $this->errors, $this->notices);
                break;
            case self::MODE_ARCHIVE:
                $tracker->start();
                $courses = $this->get_courses_and_their_owners();

                if (!empty($courses)) {
                    // Loop over the course array.
                    $tracker->jobsize = count($courses);
                    foreach ($courses as $currentcourse) {
                        if ($this->archivecourse($currentcourse)) {
                            $tracker->error = false;
                            $this->total++;
                        } else {
                            $tracker->error = true;
                            $this->errors[] = get_string('errorarchivingcourse', 'tool_coursearchiver', $currentcourse["course"]);
                        }
                        $tracker->jobsdone++;
                        $tracker->output($currentcourse);
                    }
                    $tracker->finish();
                } else {
                    $tracker->jobsize = 1;
                    $tracker->jobsdone++;
                    $tracker->output(false);
                    $this->errors[] = get_string('errorinsufficientdata', 'tool_coursearchiver');
                }

                $tracker->results($this->mode, $this->total, $this->errors, $this->notices);
                break;
            case self::MODE_DELETE:
                $tracker->start();
                $courses = $this->get_courses_and_their_owners();

                if (!empty($courses)) {
                    // Loop over the course array.
                    $tracker->jobsize = count($courses);
                    foreach ($courses as $currentcourse) {
                        // Remove Course.
                        if (delete_course($currentcourse["course"]->id, false)) {
                            $tracker->error = false;
                            $this->total++;
                        } else {
                            $tracker->error = true;
                            $this->errors[] = get_string('errordeletingcourse', 'tool_coursearchiver', $currentcourse["course"]);
                        }
                        $tracker->jobsdone++;
                        $tracker->output($currentcourse);
                    }
                    $tracker->finish();
                } else {
                    $tracker->jobsize = 1;
                    $tracker->jobsdone++;
                    $tracker->output(false);
                    $this->errors[] = get_string('errorinsufficientdata', 'tool_coursearchiver');
                }

                $tracker->results($this->mode, $this->total, $this->errors, $this->notices);
                break;
            case self::MODE_HIDEEMAIL:
            case self::MODE_ARCHIVEEMAIL:
                $tracker->start();
                if (!empty($this->data)) {
                    // Loop over the user array.
                    $tracker->jobsize = count($this->data);
                    foreach ($this->data as $user) {
                        if ($amountsent = $this->sendemail($user)) {
                            $tracker->error = false;
                            $this->total += $amountsent;
                        } else {
                            $tracker->error = true;
                            $this->errors[] = get_string('errorsendingemail', 'tool_coursearchiver', $user["user"]);
                        }
                        $tracker->jobsdone++;
                        $tracker->output(false);
                    }
                } else {
                    $tracker->jobsize = 1;
                    $tracker->jobsdone++;
                    $tracker->output(false);
                    $this->errors[] = get_string('errorinsufficientdata', 'tool_coursearchiver');
                }
                $tracker->finish();
                $tracker->results($this->mode, $this->total, $this->errors, $this->notices);
                break;
        }
    }

    /**
     * Return an each course and the teachers in them.
     *
     * @return array of courses and array of owners attached to it
     */
    protected function get_courses_and_their_owners() {
        global $DB;
        $owners = array();
        $role = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $sql = 'SELECT a.id, a.email, a.firstname, a.lastname
                  FROM {user} a
                 WHERE a.id IN (SELECT userid
                                 FROM {role_assignments} b
                                WHERE b.roleid = :roleid
                                      AND
                                      b.contextid IN (
                                                      SELECT c.id
                                                        FROM {context} c
                                                       WHERE c.contextlevel = 50
                                                             AND
                                                             c.instanceid = :courseid
                                                     )
                               )';

        foreach ($this->data as $course) {
            if ($this->exists($course)) {
                $owners[$course] = array('course' => get_course($course),
                                         'owners' => $DB->get_records_sql($sql, array('roleid' => $role->id,
                                                                                      'courseid' => $course)));
            }
        }

        return $owners;
    }

    /**
     * Return an each course and the teachers in them from save.
     *
     * @param object $data course object
     * @return array of courses and array of owners attached to it
     */
    protected function recreate_courseowners($data) {
        global $DB, $SITE;
        $owners = array();

        foreach ($data as $key => $value) {
            if ($key !== 'resume') {
                $d = explode("_", ltrim($value, 'x')); // Remove 'x' from unselected values.

                if ($d[0] !== 0 AND $d[0] !== $SITE->id) {
                    if (isset($owners[$d[0]])) { // Course exists in array.
                        $owners[$d[0]][$d[1]]["userid"] = $d[1];
                    } else {
                        $owners[$d[0]] = array();
                        $owners[$d[0]][$d[1]]["userid"] = $d[1];
                    }

                    if (substr($value, 0, 1) !== 'x') { // This course/user was not selected.
                        $owners[$d[0]][$d[1]]["selected"] = true;
                    } else {
                        $owners[$d[0]][$d[1]]["selected"] = false;
                    }
                }
            }
        }

        $return = array();
        foreach ($owners as $key => $value) {
            $return[$key] = array('course' => get_course($key),
                                  'owners' => array());
            foreach ($value as $users) {
                $record = $DB->get_record('user', array('id' => $users["userid"]));
                $record->selected = $users["selected"];
                $return[$key]["owners"][$users["userid"]] = $record;
            }
        }
        return $return;
    }

    /**
     * Return an array of owners and a list of each course they are owners of.
     *
     * @return array owners and an array of their courses attached
     */
    protected function get_owners_and_their_courses() {
        global $DB;
        $owners = array();
        $role = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $sql = 'SELECT a.id, a.email, a.firstname, a.lastname
                  FROM {user} a
                 WHERE a.id IN (
                                SELECT userid
                                  FROM {role_assignments} b
                                 WHERE b.roleid = :roleid
                                       AND
                                       b.contextid IN (
                                                       SELECT c.id
                                                         FROM {context} c
                                                        WHERE c.contextlevel = 50
                                                              AND
                                                              c.instanceid = :courseid
                                                        )
                               )';
        foreach ($this->data as $course) {
            $params = array('roleid' => $role->id, 'courseid' => $course);
            $users = $DB->get_records_sql($sql, $params);
            foreach ($users as $user) {
                if (array_key_exists($user->id, $owners)) {
                    if ($this->exists($course)) {
                        $temp = $owners[$user->id]['courses'];
                        $owners[$user->id]['courses'] = array_merge($temp, array($course => get_course($course)));
                    }
                } else {
                    if ($this->exists($course)) {
                        $owners[$user->id]['user'] = $user;
                        $owners[$user->id]['courses'] = array($course => get_course($course));
                    }
                }
            }
        }

        return $owners;
    }

    /**
     * Hide course.
     *
     * @param object $obj course object
     * @return bool
     */
    protected function hidecourse($obj) {
        global $DB;

        if (!empty($obj["course"]->visible)) {
            $obj["course"]->visible = 0;
            if (!$DB->update_record('course', $obj["course"])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Return an array of owners and a list of each course they are teachers of.
     *
     * @param object $obj course obj
     * @return bool of courses that match the search
     */
    protected function archivecourse($obj) {
        global $CFG;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/controller/backup_controller.class.php');

        if (empty($CFG->siteadmins)) {  // Should not happen on an ordinary site.
            return false;
        }

        $admin = get_admin();

        $coursetobackup = $obj["course"]->id; // Set this to one existing choice cmid in your dev site.
        $userdoingthebackup   = $admin->id; // Set this to the id of your admin account.

        try {
            // Prepare path.
            $matchers = array('/\s/', '/\//');
            $safeshort = preg_replace($matchers, '-', $obj["course"]->shortname);
            if (empty($obj["course"]->idnumber)) {
                $suffix = '-ID-'.$obj["course"]->id;
            } else {
                $suffix = '-ID-'.$obj["course"]->id.'-IDNUM-'.$obj["course"]->idnumber;
            }

            $archivefile = date("Y-m-d") . "{$suffix}-{$safeshort}.mbz";
            $archivepath = str_replace(str_split('\\/:*?"<>|'),
                                       '',
                                       get_config('tool_coursearchiver', 'coursearchiverpath'));

            // Check for custom folder.
            $folder = $this->get_archive_folder();

            // Final full path of file.
            $path = $CFG->dataroot . '/' . $archivepath . '/' . $folder;

            // If the path doesn't exist, make it so!
            if (!is_dir($path)) {
                umask(0000);
                // Create the directory for CourseArchival.
                if (!mkdir($path, $CFG->directorypermissions, true)) {
                    throw new Exception(get_string('errorarchivepath', 'tool_coursearchiver'));
                }
            }

            // Perform Backup.
            $bc = new backup_controller(backup::TYPE_1COURSE, $coursetobackup, backup::FORMAT_MOODLE,
                                        backup::INTERACTIVE_NO, backup::MODE_AUTOMATED, $userdoingthebackup);

            $bc->execute_plan();  // Execute backup.
            $results = $bc->get_results(); // Get the file information needed.

            $config = get_config('backup');
            $dir = $config->backup_auto_destination;
            $file = $results['backup_destination'];

            if (!empty($file)) {
                $file->copy_content_to($path . '/' . $archivefile);
            } else {
                $config = get_config('backup');
                $dir = $config->backup_auto_destination;
                if (!empty($dir)) { // The backup file will have already been moved, so I have to find it.
                    $file = $this->find_course_file($obj["course"]->id, $dir);
                    if (!empty($file)) {
                        rename($dir . '/' . $file, $path . '/' . $archivefile);
                    } else {
                        throw new Exception(get_string('errorbackup', 'tool_coursearchiver'));
                    }
                } else {
                    throw new Exception(get_string('errorbackup', 'tool_coursearchiver'));
                }
            }

            $bc->destroy();
            unset($bc);

            if (file_exists($path . '/' . $archivefile)) { // Make sure file got moved.
                // Remove Course.
                delete_course($obj["course"]->id, false);
            } else {
                throw new Exception(get_string('errorarchivefile', 'tool_coursearchiver'));
            }

        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Find and return the path to the last course archive file.
     *
     * @param int $courseid Moodle course id.
     * @param string $dir path to course archives.
     * @return string $filename name of the file path to rename.
     */
    protected function find_course_file($courseid, $dir) {
        // Calculate backup filename regex, ignoring the date/time/info parts that can be
        // variable, depending of languages, formats and automated backup settings.
        $filename = backup::FORMAT_MOODLE . '-' . backup::TYPE_1COURSE . '-' . $courseid . '-';
        $regex = '#' . preg_quote($filename, '#') . '.*\.mbz#';

        // Store all the matching files into filename => timemodified array.
        $files = array();
        foreach (scandir($dir) as $file) {
            // Skip files not matching the naming convention.
            if (!preg_match($regex, $file)) {
                continue;
            }

            // Read the information contained in the backup itself.
            try {
                $bcinfo = backup_general_helper::get_backup_information_from_mbz($dir . '/' . $file);
            } catch (backup_helper_exception $e) {
                throw new Exception('Error: ' . $file . ' ' .
                                    get_string('errorvalidarchive', 'tool_coursearchiver') .
                                    ' (' . $e->errorcode . ')');
                continue;
            }

            // Make sure this backup concerns the course and site we are looking for.
            if ($bcinfo->format === backup::FORMAT_MOODLE &&
                    $bcinfo->type === backup::TYPE_1COURSE &&
                    $bcinfo->original_course_id == $courseid &&
                    backup_general_helper::backup_is_samesite($bcinfo)) {
                $files[$file] = $bcinfo->backup_date;
            }
        }

        // Sort by values descending (newer to older filemodified).
        arsort($files);
        foreach ($files as $filename => $backupdate) {
            // Make sure the backup is from today.
            if (date('m/d/Y', $backupdate) == date('m/d/Y')) {
                return $filename;
            }
            break; // Just the last backup...thanks!
        }
        return false;
    }

    /**
     * Find and return archived course files.
     *
     * @return string of the folder name to be used.
     */
    protected function get_archive_folder() {
        if (!empty($this->folder)) {
            $this->folder = str_replace(str_split('\\/:*?"<>|'), '', $this->folder);
        } else { // If no custom folder is given, use the current year.
            $this->folder = date('Y');
        }
        return $this->folder;
    }

    /**
     * Sends an email to each owner
     *
     * @param object $obj user array with courses attached
     * @return array of courses that match the search
     */
    protected function sendemail($obj) {
        global $CFG;

        if (empty($CFG->siteadmins)) {  // Should not happen on an ordinary site.
            return false;
        } else {
            $admin = get_admin();
        }

        $config = get_config('tool_coursearchiver');

        switch ($this->mode) {
            case self::MODE_HIDEEMAIL:
                $subject = get_string('hidewarningsubject', 'tool_coursearchiver');
                $message = $config->hidewarningemailsetting;
                break;
            case self::MODE_ARCHIVEEMAIL:
                $subject = get_string('archivewarningsubject', 'tool_coursearchiver');
                $message = $config->archivewarningemailsetting;
                break;
            default:
                $this->errors[] = get_string('invalidmode', 'tool_coursearchiver');
                return false;
        }

        $courses = $this->get_email_courses($obj);
        if (!empty($courses)) {
            $c = "";
            foreach ($courses as $coursetext) {
                $c .= $coursetext;
            }

            // Make sure both the %to variable and the %courses variable exist in the message template.
            if (!strstr($message, '%to')) {
                $this->errors[] = get_string('errormissingto', 'tool_coursearchiver');
                return 0;
            }

            if (!strstr($message, '%courses')) {
                $this->errors[] = get_string('errormissingcourses', 'tool_coursearchiver');
                return 0;
            }

            $vars = array(
                '%to'    => $obj["user"]->firstname . ' ' . $obj["user"]->lastname,
                '%courses'    => $c
            );
            $message = strtr(nl2br($message), $vars);

            $event = new \core\message\message();
            $event->component = 'tool_coursearchiver';
            $event->name = 'courseowner';
            $event->userfrom = core_user::get_noreply_user();
            $event->userto = $obj["user"];
            $event->subject = $subject;
            $event->fullmessage = '';
            $event->fullmessageformat = FORMAT_MARKDOWN;
            $event->fullmessagehtml = $message;
            $event->smallmessage = $subject;
            $event->notification = '1';
            $event->contexturl = $CFG->wwwroot;
            $event->contexturlname = get_string('coursearchiver', 'tool_coursearchiver');
            $event->replyto = $admin->email;

            try {
                message_send($event);
            } catch (Exception $e) {
                $this->errors[] = get_string('errorsendingemail', 'tool_coursearchiver', $obj["user"]);
                return false;
            }
            return 1;
        } else {
            return 0;
        }
    }


    /**
     * Reset the current process.
     *
     * @return void.
     */
    public function reset() {
        $this->processstarted = false;
        $this->errors = array();
    }

    /**
     * Return whether the course is empty or not.
     *
     * @param int $courseid the course id.
     * @return bool
     */
    protected function is_empty_course($courseid) {
        global $DB;

        // THIS FUNCTION IS BEING MODULARIZED SO THAT IN THE FUTURE WE CAN
        // SELECT AT SEARCH TIME WHAT CONSTITUTES AN EMPTY COURSE.

        // Course module count.
        $modularsql = "1 < (
                            SELECT count(*)
                              FROM {course_modules}
                             WHERE course = :courseid1
                           )";
        $params['courseid1'] = $courseid;

        // Grade category count.
        $modularsql .= !empty($modularsql) ? " OR " : "";
        $modularsql .= "1 < (
                            SELECT count(*)
                              FROM {grade_categories}
                             WHERE courseid = :courseid2
                           )";
        $params['courseid2'] = $courseid;

        // Grade items count.
        $modularsql .= !empty($modularsql) ? " OR " : "";
        $modularsql .= "1 < (
                            SELECT count(*)
                              FROM {grade_items}
                             WHERE courseid = :courseid3
                           )";
        $params['courseid3'] = $courseid;

        // Check to see if course is meta child.
        $modularsql .= !empty($modularsql) ? " OR " : "";
        $modularsql .= "c.id IN (
                                SELECT customint1
                                  FROM {enrol}
                                 WHERE enrol = 'meta'
                                       AND
                                       status = 0
                                )";

        $sql = "SELECT *
                  FROM {course} c
                 WHERE c.id = :courseid
                       AND ($modularsql)";
        $params['courseid'] = $courseid;

        if ($DB->get_records_sql($sql, $params)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Return whether the course has been opted out.
     *
     * @param int $courseid the course id.
     * @return bool
     */
    protected function is_opted_out($courseid) {
        global $DB;

        $config = get_config('tool_coursearchiver');
        $months = $config->optoutmonthssetting;
        if (empty($months)) {
            $months = 24; // Fall back to 24 months.
        }

        $date = new DateTime("now", core_date::get_user_timezone_object());
        $date->modify("-$months months");
        $optouttime = $date->getTimestamp();

        $sql = "SELECT *
                  FROM {tool_coursearchiver_optout} c
                 WHERE c.courseid = :courseid
                       AND optouttime > $optouttime";
        $params['courseid'] = $courseid;

        if ($DB->get_records_sql($sql, $params)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Saves the archival process.
     *
     * @param int $stepid the step number.
     * @param string $title save state title.
     * @param array $data the form data to save.
     */
    public static function save_state($stepid, $title, $data) {
        global $DB;

        $date = new DateTime("now", core_date::get_user_timezone_object());

        $record = new stdClass();
        $record->title      = $title;
        $record->content    = serialize($data);
        $record->step       = $stepid;
        $record->savedate   = $date->getTimestamp();
        if ($DB->insert_record('tool_coursearchiver_saves', $record)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Resume progress from savestate.
     *
     * @param int $id the save id.
     */
    public static function get_save($id) {
        global $DB;

        if ($result = $DB->get_record('tool_coursearchiver_saves', array('id' => $id))) {
            return $result;
        } else {
            return false;
        }
    }

    /**
     * Retreive last 10 savestates.
     *
     * @return array Returns the last 10 savestates.
     */
    public static function get_saves() {
        global $DB;

        if ($result = $DB->get_records_select_menu('tool_coursearchiver_saves',
                                                   '', array(), 'savedate', 'id, title')) {
            $counter = 0;
            $saves = array("0" => get_string('resumeselect', 'tool_coursearchiver'));
            foreach ($result as $key => $value) {
                $saves[$key] = $value;
                $counter++;
                if ($counter >= 10) {
                    break;
                }
            }
            return $saves;
        } else {
            return array(get_string('resumenone', 'tool_coursearchiver'));
        }
    }

    /**
     * Return whether the course exists or not.
     *
     * @param int $courseid the course id to use to check if the course exists.
     * @return bool
     */
    protected function exists($courseid) {
        global $DB;

        if (!empty($courseid) || is_numeric($courseid)) {
            return $DB->record_exists('course', array('id' => $courseid));
        }
        return false;
    }

    /**
     * Get list of courses.
     *
     * @return object
     */
    public function get_courselist() {
        global $DB;

        $params = array();
        $searchsql = "";

        foreach ($this->data as $key => $value) {
            if (!empty($value)) {
                if (!empty($this->searchcriteria[$key])) {
                    $truekey = $this->searchcriteria[$key];
                    if ($truekey == "teacher") {
                        $role = $DB->get_record('role', array('shortname' => 'editingteacher'));
                        $params["roleid"] = $role->id;
                        $params["username"] = '%' . $DB->sql_like_escape("$value") . '%';
                        $params["email"] = '%' . $DB->sql_like_escape("$value") . '%';
                        $searchsql .= '
                    AND c.id IN (SELECT t.instanceid
                       FROM {context} t
                      WHERE t.contextlevel = 50
                        AND t.id IN (SELECT tc.contextid
                                       FROM {role_assignments} tc
                                       WHERE tc.roleid = :roleid
                                         AND tc.userid IN (SELECT tu.id
                                                             FROM {user} tu
                                                            WHERE ' . $DB->sql_like("tu.username", ":username", false, false) . '
                                                               OR ' . $DB->sql_like("tu.email", ":email", false, false) . '
                                                           )
                                    )
                    )';
                    } else if ($truekey == "id" || $truekey == "category") {
                        $params[$truekey] = $value;
                        $searchsql .= " AND c.$truekey = :$truekey";
                    } else if ($truekey == "timecreated") {
                        $params['createdbefore'] = $value;
                        // Course had to be created prior to this date.
                        $searchsql .= " AND c.timecreated < :createdbefore";
                        $params[$truekey] = $value;
                    } else if ($truekey == "timeaccess") {
                        $params['olderthan'] = $value;
                        // Course had to be old enough to have access.
                        $searchsql .= " AND c.timecreated < :olderthan";
                        $params[$truekey] = $value;
                        // Course has old or no access.
                        $searchsql .= " AND (a.$truekey <= :$truekey OR a.timeaccess IS NULL)";
                    } else if ($truekey == "emptyonly") {
                        $this->emptyonly = true;
                    } else {
                        $params[$truekey] = '%' . $DB->sql_like_escape("$value") . '%';
                        $searchsql .= " AND " . $DB->sql_like($truekey, ":$truekey", false, false);
                    }
                }
            }
        }

        $sql = "SELECT c.id, c.fullname, c.category, c.shortname, c.idnumber,
                       c.visible, a.timeaccess
                  FROM {course} c
             LEFT JOIN (
                        SELECT a.courseid, a.timeaccess
                          FROM {user_lastaccess} as a
                          JOIN (
                                SELECT courseid, MAX(timeaccess) as timeaccess
                                  FROM {user_lastaccess} as b
                              GROUP BY courseid
                                ) AS b ON (
                                           a.courseid = b.courseid
                                           AND
                                           a.timeaccess = b.timeaccess
                                           )
                       ) AS a ON c.id = a.courseid
                WHERE c.id > 1 $searchsql
                ORDER BY a.timeaccess";

        $return  = $DB->get_records_sql($sql, $params);

        return $return;
    }

    /**
     * Recreates list of courses from restorepoint data.
     *
     * @param array $data the saved formdata.
     * @return object
     */
    public function recreate_courselist($data) {
        global $DB, $SITE;

        if (empty($data)) {
            return false;
        }

        foreach ($data as $key => $value) {
            if ($key !== 'resume') {
                if ($value !== 0 AND $value !== $SITE->id) {
                    $courses[abs($value)]["id"] = abs($value);
                    if (empty($courses[abs($value)]["selected"])) {
                        $courses[abs($value)]["selected"] = false;
                    }
                }

                if ($value > 0) {
                    $courses[abs($value)]["selected"] = true;
                }
            }
        }

        $return = array();
        foreach ($courses as $c) {
            $course = get_course($c["id"]);
            $course->selected = $c["selected"];
            $return[] = $course;
        }

        return $return;
    }

    /**
     * Get list of courses for email.
     *
     * @param object $obj an array of courses
     * @return array $courses
     */
    public function get_email_courses($obj) {
        global $CFG;

        if ($this->mode == self::MODE_HIDEEMAIL) {
            $optoutbutton = get_string('optouthide', 'tool_coursearchiver');
        } else if ($this->mode == self::MODE_ARCHIVEEMAIL) {
            $optoutbutton = get_string('optoutarchive', 'tool_coursearchiver');
        }

        $courses = array();
        $courses[] = html_writer::start_tag('table', array('style' => 'border-collapse: collapse;',
                                                           'cellpadding' => '5'));
        $rowcolor = "#FFF";
        foreach ($obj["courses"] as $course) {
            // Create security key for each link.
            $key = sha1($CFG->dbpass . $course->id . $obj["user"]->id);

            // Only add courses that are visible if mode is HIDEEMAIL.
            if ($this->mode == self::MODE_ARCHIVEEMAIL || $course->visible) {
                $rowcolor = $rowcolor == "#FFF" ? "#EEE" : "#FFF";
                $courses[] = html_writer::tag('tr',
                                              html_writer::tag('td',
                                                   html_writer::link(new moodle_url('/course/view.php',
                                                                                    array('id' => $course->id)),
                                                                     $course->fullname)
                                              ) .
                                              html_writer::tag('td', '', array('width' => '5px')) .
                                              html_writer::tag('td',
                                                   html_writer::link(new moodle_url('/admin/tool/coursearchiver/optout.php',
                                                                                    array('courseid' => $course->id,
                                                                                          'userid' => $obj["user"]->id,
                                                                                          'key' => $key)),
                                                                     $optoutbutton)
                                              ),
                                              array('style' => 'background-color:' . $rowcolor)
                                     );
            } else { // This course is not included in the email.
                $this->notices[] = get_string('noticecoursehidden', 'tool_coursearchiver', $course);
            }
        }
        $courses[] = html_writer::end_tag('table');

        return $courses;
    }

    /**
     * Creates javascript for select/deselect.
     *
     * @return null
     */
    public function select_deselect_javascript() {
        global $PAGE;
        $PAGE->requires->js_amd_inline('
            require(["jquery"], function($) {
                $(".coursearchiver_selectall #id_toggle").click(function() {
                    var text = $(this).val().length > 0 ? $(this).val() : $(this).text().trim();
                    if("'.get_string('selectall', 'tool_coursearchiver').'" === text) {
                         $("input:checkbox").prop("checked", true);
                         $(".coursearchiver_selectall #id_toggle").val("'.get_string('deselectall', 'tool_coursearchiver').'");
                         $(".coursearchiver_selectall #id_toggle").text("'.get_string('deselectall', 'tool_coursearchiver').'");
                    }
                    else if("'.get_string('deselectall', 'tool_coursearchiver').'" === text) {
                         $("input:checkbox").prop("checked", false);
                         $(".coursearchiver_selectall #id_toggle").val("'.get_string('selectall', 'tool_coursearchiver').'");
                         $(".coursearchiver_selectall #id_toggle").text("'.get_string('selectall', 'tool_coursearchiver').'");
                    }
                });
            });
        ');
    }

    /**
     * Print opt out list.
     *
     * @return string
     */
    public function get_optoutlist() {
        global $CFG, $DB, $SITE;

        $sql = "SELECT *
                  FROM {tool_coursearchiver_optout}
                 ORDER BY optouttime";
        $optouts = $DB->get_records_sql($sql);

        $date = new DateTime("now", core_date::get_user_timezone_object());
        $now = $date->getTimestamp();

        $rowcolor = $rowcolor == "#FFF" ? "#EEE" : "#FFF";
        $courses .= html_writer::link(new moodle_url('/admin/tool/coursearchiver/index.php'),
                                                     get_string('back'));
        $courses .= html_writer::start_tag('table', array('style' => 'border-collapse: collapse;width: 100%;',
                                                          'cellpadding' => '5'));
        $courses .= html_writer::tag('tr',
                                     html_writer::tag('th',
                                                      get_string('course')) .
                                     html_writer::tag('th',
                                                      get_string('optouttime', 'tool_coursearchiver')) .
                                     html_writer::tag('th',
                                                      get_string('optoutby', 'tool_coursearchiver')) .
                                     html_writer::tag('th',
                                                      get_string('actions'),
                                                      array('width' => '100px')),
                                     array('style' => 'background-color:' . $rowcolor)
                                 );
        if ($optouts) {
            foreach ($optouts as $optout) {
                $user = $DB->get_record('user', array('id' => $optout->userid));
                $course = get_course($optout->courseid);

                // Create security key for each link.
                $key = sha1($CFG->dbpass . $course->id . $optout->userid);

                $config = get_config('tool_coursearchiver');
                if (empty($course->optoutmonthssetting)) {
                    $course->optoutmonthssetting = 24; // Fall back to 24 months.
                }

                $timesince = $now - $optout->optouttime;
                $fulloptout = $config->optoutmonthssetting * 2628000;

                $ago = floor(($fulloptout - $timesince) / 86400); // Days left of opt out.

                $rowcolor = $rowcolor == "#FFF" ? "#EEE" : "#FFF";
                $courses .= html_writer::tag('tr',
                                              html_writer::tag('td',
                                                   html_writer::link(new moodle_url('/course/view.php',
                                                                                    array('id' => $course->id)),
                                                                     $course->fullname,
                                                                     array('target' => '_blank'))
                                              ) .
                                              html_writer::tag('td',
                                                               get_string('optoutleft', 'tool_coursearchiver', $ago),
                                                               array('align' => 'center')) .
                                              html_writer::tag('td',
                                                               $user->firstname . ' ' . $user->lastname,
                                                               array('align' => 'center')) .
                                              html_writer::tag('td',
                                                   html_writer::link(new moodle_url('/admin/tool/coursearchiver/optin.php',
                                                                                    array('courseid' => $course->id,
                                                                                          'userid' => $optout->userid,
                                                                                          'key' => $key)),
                                                                     get_string('remove'),
                                                   array('target' => '_blank',
                                                         'onclick' => "this.parentElement.parentElement.style.display='none'")),
                                                               array('align' => 'center')),
                                              array('style' => 'background-color:' . $rowcolor)
                                     );
            }
        } else {
                $rowcolor = $rowcolor == "#FFF" ? "#EEE" : "#FFF";
                $courses .= html_writer::tag('tr',
                                              html_writer::tag('td',
                                                   "None Found",
                                                   array('colspan' => 4, 'align' => 'center',
                                                         'style' => 'background-color:' . $rowcolor)
                                              )
                                     );
        }
        $courses .= html_writer::end_tag('table');

        return $courses;
    }
}
