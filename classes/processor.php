<?php
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
 * @copyright  2013 Frédéric Massart
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
    protected $search_criteria = array(
        "id" => "id",
        "short" => "shortname", 
        "full" => "fullname", 
        "idnum" => "idnumber",
        "access" => "timeaccess",
        "emptyonly" => "emptyonly");

    /**
     * Constructor
     *
     * @param array $options options of the process
     */
    public function __construct(array $options) {

        if (!isset($options['mode']) || !in_array($options['mode'], array(self::MODE_COURSELIST, self::MODE_GETEMAILS, self::MODE_HIDE, self::MODE_ARCHIVE, self::MODE_HIDEEMAIL, self::MODE_ARCHIVEEMAIL))) {
            throw new coding_exception('Unknown process mode');
        }

        // Force int to make sure === comparison work as expected.
        $this->mode     = (int) $options['mode'];
        $this->data     = (array) $options['data'];
        $this->reset();
    }

    /**
     * Execute the process.
     *
     * @param int tracker output type.
     * @param object $tracker the output tracker to use.
     * @param object $mform moodle_form object to use (optional)
     * @param object $form $this moodle_form object to use (optional)
     * @return void
     */
    public function execute($outputtype = tool_coursearchiver_tracker::NO_OUTPUT, $tracker = null, $mform = null, $form = null) {
        if ($this->processstarted) {
            throw new coding_exception('Process has already been started');
        }
        $this->processstarted = true;

        if (empty($tracker)) {
            $tracker = new tool_coursearchiver_tracker($outputtype, $this->mode);
        }
        
        if ($outputtype == tool_coursearchiver_tracker::OUTPUT_HTML) {
            if (!in_array($this->mode, array(self::MODE_HIDE, self::MODE_ARCHIVE, self::MODE_HIDEEMAIL, self::MODE_ARCHIVEEMAIL))) {
                if(empty($mform)){
                    throw new coding_exception('Form not given');   
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
                $courses = $this->get_courselist();
                $courselist = array();
                if(!empty($courses)) {
                    // Loop over the course array
                    $tracker->jobsize = count($courses);
                    foreach ($courses as $currentcourse) {
                        $tracker->empty = $this->is_empty_course($currentcourse->id);
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
                    }           
                }
                $tracker->finish();
                $tracker->results($this->mode, $this->total, $this->errors, $this->notices);
                return $courselist;
                break;
            case self::MODE_GETEMAILS:
                $tracker->start();    
                $courses = $this->get_courses_and_their_owners();

                if(!empty($courses)) {
                    // Loop over the course array
                    $tracker->jobsize = count($courses);
                    $return = array();
                    $unique = array();
                    foreach ($courses as $currentcourse) {
                        $tracker->output($currentcourse, true); //Output course header
                        if(!empty($currentcourse["owners"])){
                            foreach($currentcourse["owners"] as $owner) {
                                $owner->course = $currentcourse["course"]->id;
                                $tracker->output($owner);  //Output users
                                $unique[$owner->id] = $owner->id;
                                $return[] = $currentcourse["course"]->id . "_" . $owner->id;
                                $this->total++;
                            }
                        }
                        $tracker->jobsdone++;
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

                if(!empty($courses)) {
                    // Loop over the course array
                    $tracker->jobsize = count($courses);
                    foreach ($courses as $currentcourse) {
                        if($currentcourse["course"]->visible) {
                            if($this->hidecourse($currentcourse)){
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

                if(!empty($courses)) {
                    // Loop over the course array
                    $tracker->jobsize = count($courses);
                    foreach ($courses as $currentcourse) {   
                        if($this->archivecourse($currentcourse, $this->folder)){
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
            case self::MODE_HIDEEMAIL:
                $tracker->start();    
                if(!empty($this->data)) {
                    // Loop over the user array
                    $tracker->jobsize = count($this->data);

                    foreach ($this->data as $user) {
                        $info = $this->sendemail($user);
                        if($info !== false) {
                            $tracker->error = false;
                            $this->total += $info;
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
            case self::MODE_ARCHIVEEMAIL:
                $tracker->start();    

                if(!empty($this->data)) {
                    // Loop over the user array
                    $tracker->jobsize = count($this->data);
                    foreach ($this->data as $user) {   
                        $info = $this->sendemail($user);
                        if($info !== false) {
                            $tracker->error = false;
                            $this->total += $info;    
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
        $sql = 'SELECT a.id, a.email, a.firstname, a.lastname 
                    FROM {user} a WHERE 
                        a.id IN (SELECT userid 
                                    FROM {role_assignments} b WHERE 
                                        b.roleid=3 
                                        AND 
                                        b.contextid IN (SELECT c.id 
                                                            FROM {context} c WHERE 
                                                                c.contextlevel=50 
                                                                AND 
                                                                c.instanceid=:courseid))';

        foreach($this->data as $course){
            if ($this->exists($course)) {
                $owners[$course] = array("course" => get_course($course), "owners" => $DB->get_records_sql($sql, array('courseid' => $course)));    
            }
        }

        return $owners;   
    }

    /**
     * Return an array of owners and a list of each course they are owners of.
     *
     * @return array owners and an array of their courses attached
     */
    protected function get_owners_and_their_courses() {
        global $DB;
        $owners = array();
        $sql = 'SELECT a.id, a.email, a.firstname, a.lastname 
                    FROM {user} a WHERE 
                        a.id IN (SELECT userid 
                                    FROM {role_assignments} b WHERE 
                                        b.roleid=3 
                                        AND 
                                        b.contextid IN (SELECT c.id 
                                                            FROM {context} c WHERE 
                                                                c.contextlevel=50 
                                                                AND 
                                                                c.instanceid=:courseid))';
        foreach($this->data as $course){
            $params = array('courseid' => $course);
            $users = $DB->get_records_sql($sql, $params);
            foreach($users as $user){
                if(array_key_exists($user->id, $owners)){
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
        
        if(!empty($obj["course"]->visible)){
            $obj["course"]->visible = 0;
            if(!$DB->update_record('course', $obj["course"])) {
                return false;
            }     
        }
        return true;
    }

    /**
     * Return an array of owners and a list of each course they are teachers of.
     *
     * @param object $obj course obj
     * @param string $folder name of folder (optional)
     * @return bool of courses that match the search
     */
    protected function archivecourse($obj, $folder = false) {
        global $CFG;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/controller/backup_controller.class.php');

        if (empty($CFG->siteadmins)) {  // Should not happen on an ordinary site
            return false;
        } else {
            $admin = get_admin();
        }
        
        $course_to_backup = $obj["course"]->id; // Set this to one existing choice cmid in your dev site
        $user_doing_the_backup   = $admin->id; // Set this to the id of your admin accouun

        try {
            // Prepare path
            $matchers = array('/\s/', '/\//');
            $safe_short = preg_replace($matchers, '-', $obj["course"]->shortname);
            $suffix = empty($obj["course"]->idnumber) ? '-ID-'.$obj["course"]->id : '-ID-'.$obj["course"]->id.'-IDNUM-'.$obj["course"]->idnumber;
            
            $archive_file = date("Y-m-d") . "{$suffix}-{$safe_short}.zip";
            $archive_path = str_replace(str_split('\\/:*?"<>|'), '', get_config('tool_coursearchiver', 'coursearchiverpath'));
            
            // Check for custom folder
            if(!empty($folder)){
                $folder = str_replace(str_split('\\/:*?"<>|'), '', $folder);
            }
            
            // If no custom folder is given, use the current year
            if(empty($folder)){ 
                $folder = date('Y');
            }
            
            // Final full path of file
            $path = $CFG->dataroot . '/' . $archive_path . '/' . $folder;
            
            // If the path doesn't exist, make it so!
            if (!is_dir($path)) { 
                umask(0000);
                // create the directory for CourseArchival
                if (!mkdir($path, $CFG->directorypermissions, true)) {
                    throw new Exception('Archive path could not be created');
                }
                @chmod($path, $dirpermissions);
            }
            
            // Perform Backup
            $bc = new backup_controller(backup::TYPE_1COURSE, $course_to_backup, backup::FORMAT_MOODLE,
                                        backup::INTERACTIVE_NO, backup::MODE_AUTOMATED, $user_doing_the_backup);
    
            $bc->execute_plan();  // Execute backup
            $results = $bc->get_results(); // Get the file information needed
            
            $config = get_config('backup');
            $dir = $config->backup_auto_destination;

            // The backup file will have already been moved, so I have to find it.
            if (!empty($dir)) {
                // Calculate backup filename regex, ignoring the date/time/info parts that can be
                // variable, depending of languages, formats and automated backup settings.
                $filename = backup::FORMAT_MOODLE . '-' . backup::TYPE_1COURSE . '-' . $obj["course"]->id . '-';
                $regex = '#' . preg_quote($filename, '#') . '.*\.mbz#';

                // Store all the matching files into filename => timemodified array.
                $files = array();
                foreach (scandir($dir) as $file) {
                    // Skip files not matching the naming convention.
                    if (!preg_match($regex, $file, $matches)) {
                        continue;
                    }
    
                    // Read the information contained in the backup itself.
                    try {
                        $bcinfo = backup_general_helper::get_backup_information_from_mbz($dir . '/' . $file);
                    } catch (backup_helper_exception $e) {
                        throw new Exception('Error: ' . $file . ' does not appear to be a valid backup (' . $e->errorcode . ')');
                        continue;
                    }
    
                    // Make sure this backup concerns the course and site we are looking for.
                    if ($bcinfo->format === backup::FORMAT_MOODLE &&
                            $bcinfo->type === backup::TYPE_1COURSE &&
                            $bcinfo->original_course_id == $obj["course"]->id &&
                            backup_general_helper::backup_is_samesite($bcinfo)) {
                        $files[$file] = $bcinfo->backup_date;
                    }
                }
                
                // Sort by values descending (newer to older filemodified).
                arsort($files);
                foreach ($files as $filename => $backup_date) {
                    // Make sure the backup is from today
                    if(date('m/d/Y', $backup_date) == date('m/d/Y')) {
                        rename($dir . '/' . $filename, $path . '/' . $archive_file);
                    }
                    break; // Just the last backup...thanks!
                }
            } else {
                $file = $results['backup_destination'];
                if(!empty($file)) {
                    $file->copy_content_to($path . '/' . $archive_file);
                } else {
                    throw new Exception('Backup failed');
                }
            }
            
            $bc->destroy();
            unset($bc);
            
            if(file_exists($path . '/' . $archive_file)) { // Make sure file got moved
                //Remove Course
                delete_course($obj["course"]->id, false);
            } else {
                throw new Exception('Course archive file does not exist');
            }
            
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Sends an email to each owner
     *
     * @param object $obj user array with courses attached
     * @return array of courses that match the search
     */
    protected function sendemail($obj) {
        global $CFG, $DB;
    
        if (empty($CFG->siteadmins)) {  // Should not happen on an ordinary site
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
        
        $courses = $this->get_email_courses($obj, $admin);
        if(!empty($courses)) {
            $c = "";
            foreach($courses as $coursetext) {
                $c .= $coursetext;    
            }
            
            //make sure both the %to variable and the %courses variable exist in the message template
            if(!strstr($message, '%to')){
                $this->errors[] = get_string('errormissingto', 'tool_coursearchiver');
                return 0;    
            }
            
            if(!strstr($message, '%courses')){
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
            $event->contexturlname = 'Course Archiver';
            $event->replyto = $admin->email;
            
            try {
                $messageid = message_send($event);
            } catch (Exception $e) {
                $this->errors[] = get_string('errorsendingemail', 'tool_coursearchiver',$obj["user"]);
                return false;
            }
            return count($courses);
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
        
        $sql = "SELECT * FROM {course} c WHERE c.id = :courseid AND (c.id IN 
                    (SELECT course FROM {course_modules}) 
                OR c.id IN 
                    (SELECT courseid FROM {grade_categories})
                OR c.id IN
                    (SELECT courseid FROM {grade_items}))";
        
        $params['courseid'] = $courseid;
        if($DB->get_records_sql($sql, $params)){
            return false;
        } else {
            return true;
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

        $params = array(); $sql = $neveraccessed = "";
 
        foreach ($this->data as $key => $value) {
            if (!empty($value)) {
                if(!empty($this->search_criteria[$key])){
                    $truekey = $this->search_criteria[$key];
                    if($truekey == "id") {
                        $params[$truekey] = $value;
                        $sql .= " AND c.$truekey = :$truekey";
                    } else if ($truekey == "timeaccess"){
                        $params['olderthan'] = $value;
                        $sql .= " AND c.timecreated < :olderthan"; // course had to be old enough to have access
                        
                        $params[$truekey] = $value;
                        $sql .= " AND (a.$truekey <= :$truekey OR a.timeaccess IS NULL)"; // course has old or no access
                    } else if($truekey == "emptyonly") {
                        $this->emptyonly = true;
                    } else {
                        $params[$truekey] = '%' . $DB->sql_like_escape("$value") . '%';
                        $sql .= " AND " . $DB->sql_like($truekey, ":$truekey", false, false);
                    }                   
                }
            }   
        }

        $return  = $DB->get_records_sql("SELECT c.id, c.fullname, c.shortname, c.idnumber, c.visible, COALESCE(a.timeaccess,0) as lastaccess 
                                        FROM {course} c 
                                            LEFT JOIN (SELECT courseid, timeaccess FROM {user_lastaccess}) AS a ON c.id = a.courseid
                                        WHERE c.id > 1 $sql GROUP BY c.id ORDER BY lastaccess", $params);

        return $return;
    }


    /**
     * Get list of courses for email.
     *
     * @param object $obj an array of courses
     * @param object $admin is the user object for the admin user
     * 
     * @return array $courses
     */
    public function get_email_courses($obj, $admin) {
        global $DB;
        
        if($this->mode == self::MODE_HIDEEMAIL){
            $optoutsubject = 'optouthidesubject'; 
            $optoutmessage = 'optouthidemessage';            
        } else if($this->mode == self::MODE_ARCHIVEEMAIL){
            $optoutsubject = 'optoutarchivesubject'; 
            $optoutmessage = 'optoutarchivemessage';    
        }
        $courses = array();
        foreach ($obj["courses"] as $course) {
            if($this->mode == self::MODE_ARCHIVEEMAIL || $course->visible) { // Only add courses that are visible if mode is HIDEEMAIL
                $courses[] = '<div>' . $course->fullname . ' (<a href="mailto:'.$admin->email.
                '?subject='.get_string($optoutsubject, 'tool_coursearchiver').
                '&body='.str_replace("\n",'%0D%0A', get_string($optoutmessage, 'tool_coursearchiver', 'Course ID: '. $course->id . "\n" . 'Course Name: ' .$course->fullname)).
                '">Ask to opt out</a>)</div>';    
            } else { // This course is not included in the email
                $this->notices[] = get_string('noticecoursehidden', 'tool_coursearchiver', $course);
            }    
        }

        return $courses;
    }
}
