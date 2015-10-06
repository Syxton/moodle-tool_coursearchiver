<?php
/**
 * CLI Bulk course archive script.
 *
 * @package    tool_coursearchiver
 * @copyright  2015 Matthew Davidson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

$courseconfig = get_config('moodlecourse');

// Now get cli options.
list($options, $unrecognized) = cli_get_params(array(
    'short' => false,
    'full' => false,
    'id' => false,
    'idnum' => false,
    'help' => false,
    'access' => false,
    'mode' => false,
    'location' => false,
    'empty' => false,
    'verbose' => false,
),
array(
    's' => 'short',
    'f' => 'full',
    'i' => 'id',
    'n' => 'idnum',
    'h' => 'help',
    'a' => 'access',
    'm' => 'mode',
    'l' => 'location',
    'e' => 'empty',
    'v' => 'verbose'
));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

$help =
"\nCourse Archiver Helper:

Options:
-s, --short     Search for courses matching the Moodle course shortname
-f, --full      Search for courses matching the Moodle course fullname
-i, --id        Search for courses matching the Moodle course id
-n, --idnum     Search for courses matching the Moodle course idnumber
-a, --access    Last accessed before UNIX TIMESTAMP
-m, --mode      courselist,emaillist,hide,archive,hideemail,archiveemail
-l, --location  Folder name to store archived courses (optional)
-e, --empty     Only return empty courses
-v, --verbose   Maximum output from tool (optional)

Example:
php admin/tool/coursearchiver/cli/coursearchiver.php --short=ma101 --idnum=2012 --mode=archive --verbose
";

if (!empty($options['help'])) {
    echo $help;
    die();
}

// Confirm that the mode is valid.
$modes = array(
    'courselist' => tool_coursearchiver_processor::MODE_COURSELIST,
    'emaillist' => tool_coursearchiver_processor::MODE_GETEMAILS,
    'hideemail' => tool_coursearchiver_processor::MODE_HIDEEMAIL,
    'hide' => tool_coursearchiver_processor::MODE_HIDE,
    'archiveemail' => tool_coursearchiver_processor::MODE_ARCHIVEEMAIL,
    'archive' => tool_coursearchiver_processor::MODE_ARCHIVE
);

if (!isset($options['mode']) || empty($modes[$options['mode']])) {
    echo get_string('invalidmode', 'tool_coursearchiver')."\n";
    die();
}

$processoroptions['mode'] = $modes[$options['mode']];

if(!empty($options['id']) && !is_numeric($options['id'])) {
    echo get_string('errornonnumericid', 'tool_coursearchiver'). "\n";
    die();
}

if(!empty($options['access']) && !is_numeric($options['access'])) {
    echo get_string('errornonnumericaccess', 'tool_coursearchiver'). "\n";
    die();
}

$output = !empty($options['verbose']) ? tool_coursearchiver_tracker::OUTPUT_CLI : tool_coursearchiver_tracker::NO_OUTPUT;
$question = "";
switch ($processoroptions['mode']) {
    case tool_coursearchiver_processor::MODE_COURSELIST:
        // Show courselist and die...
        $processor = new tool_coursearchiver_processor(array("mode" => tool_coursearchiver_processor::MODE_COURSELIST, "data" => $options));
        if(!empty($options['empty'])) {
            $processor->emptyonly = true;
        }
        $processor->execute(tool_coursearchiver_tracker::OUTPUT_CLI);
        die();
    break;
    case tool_coursearchiver_processor::MODE_GETEMAILS:
        // get courses
        $processor = new tool_coursearchiver_processor(array("mode" => tool_coursearchiver_processor::MODE_COURSELIST, "data" => $options));
        if(!empty($options['empty'])) {
            $processor->emptyonly = true;
        }
        $courses = $processor->execute($output);
        
        if(!empty($courses)){
            $processor = new tool_coursearchiver_processor(array("mode" => tool_coursearchiver_processor::MODE_GETEMAILS, "data" => $courses));
            $processor->execute(tool_coursearchiver_tracker::OUTPUT_CLI);            
        } else {
            echo get_string('cli_cannot_continue', 'tool_coursearchiver');
        }
        die();
    break;
    case tool_coursearchiver_processor::MODE_HIDEEMAIL:
    case tool_coursearchiver_processor::MODE_ARCHIVEEMAIL:       
        $processor = new tool_coursearchiver_processor(array("mode" => tool_coursearchiver_processor::MODE_COURSELIST, "data" => $options));
        if(!empty($options['empty'])) {
            $processor->emptyonly = true;
        }
        $courses = $processor->execute($output);
        
        if(!empty($courses)){
            $processor = new tool_coursearchiver_processor(array("mode" => tool_coursearchiver_processor::MODE_GETEMAILS, "data" => $courses));
            $selected = $processor->execute(tool_coursearchiver_tracker::OUTPUT_CLI);
        }
        
        if(empty($courses) || empty($selected)) { // No courses matched the search or no owners exist on the courses that do match
            echo get_string('cli_cannot_continue', 'tool_coursearchiver');
            die();
        }
        
        $question = get_string('cli_question_'.$options['mode'], 'tool_coursearchiver', count($selected));
    break;
    case tool_coursearchiver_processor::MODE_HIDE:
    case tool_coursearchiver_processor::MODE_ARCHIVE:
        // Show courselist and die...
        $processor = new tool_coursearchiver_processor(array("mode" => tool_coursearchiver_processor::MODE_COURSELIST, "data" => $options));
        if(!empty($options['empty'])) {
            $processor->emptyonly = true;
        }
        $courses = $processor->execute($output);
        
        if(empty($courses)){ // No courses matched the search
            echo "\nNot enough data to continue.";
            die();
        }
        
        $question = get_string('cli_question_'.$options['mode'], 'tool_coursearchiver', count($courses));
    break;
}

// ASK FOR PERMISSION TO CONTINUE
if(!empty($question)){
    echo "\n$question  Type 'yes' to continue: ";
    $line = fgets(STDIN);
    if(trim($line) != 'yes'){
        echo "\nSTOPPED\n";
        exit;
    }

    echo "\nMoodle course archiver running ...\n\n";
}

switch ($processoroptions['mode']) {
    case tool_coursearchiver_processor::MODE_HIDE:
        $processor = new tool_coursearchiver_processor(array("mode" => $processoroptions['mode'], "data" => $courses));
        $processor->execute(tool_coursearchiver_tracker::OUTPUT_CLI);
        break;
    case tool_coursearchiver_processor::MODE_ARCHIVE:
        $processor = new tool_coursearchiver_processor(array("mode" => $processoroptions['mode'], "data" => $courses));
        if(!empty($options['location'])){
            $processor->folder = $options['location'];    
        }
        $processor->execute(tool_coursearchiver_tracker::OUTPUT_CLI);
        break;
    case tool_coursearchiver_processor::MODE_HIDEEMAIL:
    case tool_coursearchiver_processor::MODE_ARCHIVEEMAIL:
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
        $processor = new tool_coursearchiver_processor(array("mode" => $processoroptions['mode'], "data" => $owners));
        $processor->execute(tool_coursearchiver_tracker::OUTPUT_CLI);
    break;
    default:
        echo "\nFAILED TO CONTINUE";
        exit;
}

echo "\nFINISHED!";