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
list($options, $unrecognized) = cli_get_params(['short' => false,
                                                'full' => false,
                                                'id' => false,
                                                'idnum' => false,
                                                'teacher' => false,
                                                'catid' => false,
                                                'subcats' => false,
                                                'help' => false,
                                                'createdbefore' => false,
                                                'createdafter' => false,
                                                'accessbefore' => false,
                                                'accessafter' => false,
                                                'startbefore' => false,
                                                'startafter' => false,
                                                'endbefore' => false,
                                                'endafter' => false,
                                                'mode' => false,
                                                'location' => false,
                                                'empty' => false,
                                                'ignadmins' => false,
                                                'ignsiteroles' => false,
                                                'verbose' => false,
                                               ],
                                               ['s' => 'short',
                                                'f' => 'full',
                                                'i' => 'id',
                                                'n' => 'idnum',
                                                'h' => 'help',
                                                't' => 'teacher',
                                                'c' => 'catid',
                                                'r' => 'subcats',
                                                'b' => 'createdbefore',
                                                'a' => 'createdafter',
                                                'B' => 'accessbefore',
                                                'A' => 'accessafter',
                                                'o' => 'startbefore',
                                                'O' => 'startafter',
                                                'd' => 'endbefore',
                                                'D' => 'endafter',
                                                'i' => 'ignadmins',
                                                'I' => 'ignsiteroles',
                                                'm' => 'mode',
                                                'l' => 'location',
                                                'e' => 'empty',
                                                'v' => 'verbose',
                                               ]);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

$help = "\nCourse Archiver Helper:

Options:
-s, --short             Search for courses matching the Moodle course shortname
-f, --full              Search for courses matching the Moodle course fullname
-i, --id                Search for courses matching the Moodle course id
-n, --idnum             Search for courses matching the Moodle course idnumber
-c, --catid             Search for courses matching the Moodle category id
-r, --subcats           Recursively search the course categories
-t, --teacher           Search for courses that have teacher with matching username or email
-b, --createdbefore     Course created before UNIX TIMESTAMP
-a, --createdafter      Course created after UNIX TIMESTAMP
-B, --accessbefore      Last accessed before UNIX TIMESTAMP
-A, --accessafter       Last accessed after UNIX TIMESTAMP
-o, --startbefore       Course starts before UNIX TIMESTAMP
-O, --startafter        Course starts after UNIX TIMESTAMP
-d, --endbefore         Course ends before UNIX TIMESTAMP
-D, --endafter          Course ends after UNIX TIMESTAMP
-i, --ignadmins         Ignore admin account accesses
-I, --ignsiteroles      Ignore site role accesses
-m, --mode              courselist,emaillist,hide,backup,archive,delete,hideemail,archiveemail
-l, --location          Folder name to store archived courses (optional)
-e, --empty             Only return empty courses
-v, --verbose           Maximum output from tool (optional)

Example:
php admin/tool/coursearchiver/cli/coursearchiver.php --short=ma101 --idnum=2012 --mode=archive --verbose --ignadmins
";

if (!empty($options['help'])) {
    echo $help;
    die();
}

// Confirm that the mode is valid.
$modes = ['courselist' => tool_coursearchiver_processor::MODE_COURSELIST,
          'emaillist' => tool_coursearchiver_processor::MODE_GETEMAILS,
          'hideemail' => tool_coursearchiver_processor::MODE_HIDEEMAIL,
          'hide' => tool_coursearchiver_processor::MODE_HIDE,
          'archiveemail' => tool_coursearchiver_processor::MODE_ARCHIVEEMAIL,
          'backup' => tool_coursearchiver_processor::MODE_BACKUP,
          'archive' => tool_coursearchiver_processor::MODE_ARCHIVE,
          'delete' => tool_coursearchiver_processor::MODE_DELETE,
          'optout' => tool_coursearchiver_processor::MODE_OPTOUT,
];

if (!isset($options['mode']) || empty($modes[$options['mode']])) {
    echo get_string('invalidmode', 'tool_coursearchiver')."\n";
    die();
}

$processoroptions['mode'] = $modes[$options['mode']];

if (!empty($options['id']) && !is_numeric($options['id'])) {
    echo get_string('errornonnumericid', 'tool_coursearchiver'). "\n";
    die();
}

if (!empty($options['createdbefore']) && !is_numeric($options['createdbefore'])) {
    echo get_string('errornonnumerictimestamp', 'tool_coursearchiver'). "\n";
    die();
}

if (!empty($options['createdafter']) && !is_numeric($options['createdafter'])) {
    echo get_string('errornonnumerictimestamp', 'tool_coursearchiver'). "\n";
    die();
}

if (!empty($options['accessbefore']) && !is_numeric($options['accessbefore'])) {
    echo get_string('errornonnumerictimestamp', 'tool_coursearchiver'). "\n";
    die();
}

if (!empty($options['accessafter']) && !is_numeric($options['accessafter'])) {
    echo get_string('errornonnumerictimestamp', 'tool_coursearchiver'). "\n";
    die();
}

if (!empty($options['startbefore']) && !is_numeric($options['startbefore'])) {
    echo get_string('errornonnumerictimestamp', 'tool_coursearchiver'). "\n";
    die();
}

if (!empty($options['startafter']) && !is_numeric($options['startafter'])) {
    echo get_string('errornonnumerictimestamp', 'tool_coursearchiver'). "\n";
    die();
}

if (!empty($options['endbefore']) && !is_numeric($options['endbefore'])) {
    echo get_string('errornonnumerictimestamp', 'tool_coursearchiver'). "\n";
    die();
}

if (!empty($options['endafter']) && !is_numeric($options['endafter'])) {
    echo get_string('errornonnumerictimestamp', 'tool_coursearchiver'). "\n";
    die();
}

$output = !empty($options['verbose']) ? tool_coursearchiver_tracker::OUTPUT_CLI : tool_coursearchiver_tracker::NO_OUTPUT;
$question = "";
switch ($processoroptions['mode']) {
    case tool_coursearchiver_processor::MODE_COURSELIST:
        // Show courselist and die...
        $processor = new tool_coursearchiver_processor(["mode" => tool_coursearchiver_processor::MODE_COURSELIST,
                                                        "data" => $options,
                                                       ]);
        if (!empty($options['empty'])) {
            $processor->emptyonly = true;
        }
        $processor->execute(tool_coursearchiver_tracker::OUTPUT_CLI);
        die();
    break;
    case tool_coursearchiver_processor::MODE_GETEMAILS:
        $processor = new tool_coursearchiver_processor(["mode" => tool_coursearchiver_processor::MODE_COURSELIST,
                                                        "data" => $options,
                                                       ]);
        if (!empty($options['empty'])) {
            $processor->emptyonly = true;
        }
        $courses = $processor->execute($output);

        if (!empty($courses)) {
            $processor = new tool_coursearchiver_processor(["mode" => tool_coursearchiver_processor::MODE_GETEMAILS,
                                                            "data" => $courses,
                                                           ]);
            $processor->execute(tool_coursearchiver_tracker::OUTPUT_CLI);
        } else {
            echo get_string('cli_cannot_continue', 'tool_coursearchiver');
        }
        die();
    break;
    case tool_coursearchiver_processor::MODE_HIDEEMAIL:
    case tool_coursearchiver_processor::MODE_ARCHIVEEMAIL:
        $processor = new tool_coursearchiver_processor(["mode" => tool_coursearchiver_processor::MODE_COURSELIST,
                                                        "data" => $options,
                                                       ]);
        if (!empty($options['empty'])) {
            $processor->emptyonly = true;
        }
        $courses = $processor->execute($output);

        if (!empty($courses)) {
            $processor = new tool_coursearchiver_processor(["mode" => tool_coursearchiver_processor::MODE_GETEMAILS,
                                                            "data" => $courses,
                                                           ]);
            $selected = $processor->execute(tool_coursearchiver_tracker::OUTPUT_CLI);
        }

        // No courses matched the search or no owners exist on the courses that do match.
        if (empty($courses) || empty($selected)) {
            echo get_string('cli_cannot_continue', 'tool_coursearchiver');
            die();
        }

        $question = get_string('cli_question_'.$options['mode'], 'tool_coursearchiver', count($selected));
    break;
    case tool_coursearchiver_processor::MODE_HIDE:
    case tool_coursearchiver_processor::MODE_BACKUP:
    case tool_coursearchiver_processor::MODE_ARCHIVE:
    case tool_coursearchiver_processor::MODE_DELETE:
    case tool_coursearchiver_processor::MODE_OPTOUT:
        // Show courselist and die...
        $processor = new tool_coursearchiver_processor(["mode" => tool_coursearchiver_processor::MODE_COURSELIST,
                                                        "data" => $options,
                                                       ]);
        if (!empty($options['empty'])) {
            $processor->emptyonly = true;
        }
        $courses = $processor->execute($output);

        if (empty($courses)) { // No courses matched the search.
            echo "\nNot enough data to continue.";
            die();
        }

        $question = get_string('cli_question_'.$options['mode'], 'tool_coursearchiver', count($courses));
    break;
}

// ASK FOR PERMISSION TO CONTINUE.
if (!empty($question)) {
    echo "\n$question  Type 'yes' to continue: ";
    $line = fgets(STDIN);
    if (trim($line) != 'yes') {
        echo "\nSTOPPED\n";
        exit;
    }

    echo "\nMoodle course archiver running ...\n\n";
}

switch ($processoroptions['mode']) {
    case tool_coursearchiver_processor::MODE_HIDE:
    case tool_coursearchiver_processor::MODE_DELETE:
    case tool_coursearchiver_processor::MODE_OPTOUT:
        $processor = new tool_coursearchiver_processor(["mode" => $processoroptions['mode'], "data" => $courses]);
        $processor->execute(tool_coursearchiver_tracker::OUTPUT_CLI);
        break;
    case tool_coursearchiver_processor::MODE_BACKUP:
    case tool_coursearchiver_processor::MODE_ARCHIVE:
        $processor = new tool_coursearchiver_processor(["mode" => $processoroptions['mode'], "data" => $courses]);
        if (!empty($options['location'])) {
            $processor->folder = $options['location'];
        }
        $processor->execute(tool_coursearchiver_tracker::OUTPUT_CLI);
        break;
    case tool_coursearchiver_processor::MODE_HIDEEMAIL:
    case tool_coursearchiver_processor::MODE_ARCHIVEEMAIL:
        $owners = [];
        foreach ($selected as $s) {
            $t = explode("_", $s);
            if (count($t) == 2) { // Both a course and an owner are needed.
                if (array_key_exists($t[1], $owners)) {
                    $temp = $owners[$t[1]]['courses'];
                    $owners[$t[1]]['courses'] = array_merge($temp, [$t[0] => get_course($t[0])]);
                } else {
                    $owners[$t[1]]['courses'] = [$t[0] => get_course($t[0])];
                    $owners[$t[1]]['user'] = $DB->get_record("user", ["id" => $t[1]]);
                }
            }
        }
        $processor = new tool_coursearchiver_processor(["mode" => $processoroptions['mode'], "data" => $owners]);
        $processor->execute(tool_coursearchiver_tracker::OUTPUT_CLI);
    break;
    default:
        echo "\nFAILED TO CONTINUE";
        exit;
}

echo "\nFINISHED!";
