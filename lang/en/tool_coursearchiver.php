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
 * Strings for component 'tool_coursearchiver'.
 *
 * @package    tool_coursearchiver
 * @copyright  2015 Matthew Davidson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Course Archiver';

$string['access'] = 'Last accessed before';
$string['archive'] = 'Archive Courses';
$string['archiveemail'] = 'Send "Course to be Archived" Emails';
$string['archivelocation'] = 'Course archive subdirectory';
$string['back'] = 'Start Over';

$string['cannotdeletecoursenotexist'] = 'Cannot delete a course that does not exist';
$string['cli_cannot_continue'] = "\nSTOPPED: Not enough data to continue.\n";
$string['cli_question_archive'] = 'Archive and delete these {$a} courses?';
$string['cli_question_archiveemail'] = 'Send these {$a} users a "Course to be archived" email?';
$string['cli_question_hide'] = 'Hide these {$a} courses?';
$string['cli_question_hideemail'] = 'Send these {$a} users a "Course to be hidden" email?';
$string['confirm'] = 'Continue';
$string['confirmmessage'] = 'Are you sure you want to {$a}';
$string['confirmmessagearchive'] = 'archive and remove these {$a} courses?';
$string['confirmmessagearchiveemail'] = 'send an email to these {$a} course owners?';
$string['confirmmessagehide'] = 'hide these {$a} courses?';
$string['confirmmessagehideemail'] = 'send an email to these {$a} course owners?';
$string['coursearchiver'] = 'Course Archiver';
$string['coursearchiver_help'] = "Search for courses using the following criteria: match shortname, fullname, idnumber, courseid, date of last course access, or *empty courses.\n
Courses are shown as grayed out if they are already hidden.  The fullname of the course will have a line through it if the course is an *empty course.\n\n
NOTE: The last access search will only return courses that were created before the date provided.\n
NOTE: The amount of email addresses found may differ from the emails sent.  This has 2 causes. \n
   1. A course that is already hidden will not send out an email to notify the owner(s) if the course is selected to be hidden.\n
   2. A single address that shows up under multiple courses will be joined into a single email.\n\n
*empty courses are defined as 0 assignments, 0 resources, 0 categories in the gradebook, and 0 grade items in the gradebook.";
$string['coursearchiverpreview'] = 'Upload courses preview';
$string['coursearchiverresult'] = 'Upload courses results';
$string['coursedeleted'] = 'Course deleted';
$string['coursedeletionnotallowed'] = 'Course deletion is not allowed';
$string['coursefullname'] = 'Course fullname';
$string['courseid'] = 'Course ID';
$string['courseidnum'] = 'Course idnumber';
$string['courseselector'] = 'Course search results';
$string['courseshortname'] = 'Course shortname';
$string['createdbefore'] = 'Created before';
$string['email'] = 'Send Email';
$string['emailselector'] = 'Users selected to receive emails.';
$string['emptycourse'] = 'Empty Course';
$string['emptyonly'] = 'Only return empty courses';
$string['error_nocourseid'] = 'Course record did not contain an ID';
$string['errorarchivefile'] = 'Course archive file does not exist.';
$string['errorarchivepath'] = 'Archive path could not be created.';
$string['errorarchivingcourse'] = 'Course: ({$a->id}) {$a->fullname} could not be archived.';
$string['errorbackup'] = 'Backup Failed.';
$string['erroremptysearch'] = 'No search criteria given.';
$string['errorhidingcourse'] = 'Course: ({$a->id}) {$a->fullname} could not be hidden.';
$string['errorinsufficientdata'] = 'Not enough information to perform this action';
$string['errormissingcourses'] = 'The %courses variable was missing from the email template.  This is a list of the courses.';
$string['errormissingto'] = 'The %to variable was missing from the email template.  This is the name of the recipient.';
$string['errornoform'] = 'Form not given.';
$string['errornonnumericaccess'] = 'Access must be numeric (UNIX timestamp)';
$string['errornonnumericid'] = 'Course ID must be numeric';
$string['errors'] = 'Errors';
$string['errors_count'] = 'Errors: {$a}';
$string['errorsendingemail'] = 'Email to {$a->firstname} {$a->lastname} ({$a->email}) failed.';
$string['errorvalidarchive'] = 'Not a valid backup file.';
$string['hide'] = 'Hide Courses';
$string['hideemail'] = 'Send "Course to be Hidden" Emails';
$string['invalidmode'] = 'A valid mode for the tool was not given.';
$string['never'] = 'Never';
$string['nocoursesfound'] = 'The search has resulted in 0 courses found.';
$string['nocoursesselected'] = 'To perform this action you must have at least 1 course selected.';
$string['notices'] = 'Notices';
$string['noticecoursehidden'] = 'Course: ({$a->id}) {$a->fullname} was already hidden.';
$string['notices_count'] = 'Notices: {$a}';
$string['nousersfound'] = 'There are no course owners to notify';
$string['nousersselected'] = 'To perform this action you must have at least 1 user selected.';
$string['optout'] = 'Ask to opt out';

$string['outaccess'] = 'Last Access';
$string['outemail'] = 'Email';
$string['outfirstname'] = 'Firstname';
$string['outfullname'] = 'Fullname';
$string['outid'] = 'ID';
$string['outidnumber'] = 'Idnumber';
$string['outlastname'] = 'Lastname';
$string['outowners'] = 'Course Owners';
$string['outselected'] = 'Selected';
$string['outshortname'] = 'Shortname';
$string['outuse'] = 'Last Use';

$string['processarchiving'] = 'Archiving selected courses';
$string['processcomplete'] = 'Process Complete';
$string['processemailing'] = 'Sending Emails';
$string['processhiding'] = 'Hiding selected courses';
$string['processstarted'] = 'Process has already been started';

$string['results'] = 'Results';
$string['results_archive'] = 'Archived courses: {$a}';
$string['results_archiveemail'] = 'Course archive warning emails sent: {$a}';
$string['results_courselist'] = 'Courses listed: {$a}';
$string['results_getemails'] = 'Email addresses gathered: {$a}';
$string['results_hide'] = 'Hidden courses: {$a}';
$string['results_hideemail'] = 'Hidden course warning emails sent: {$a}';
$string['search'] = 'Search for courses';
$string['status'] = 'Status';
$string['unknownerror'] = 'The process has resulted in an error that requires a restart of the process.';

// SETTINGS.
$string['coursearchiver_settings'] = 'Course Archiver Settings';
$string['hidewarningemailsetting'] = 'Default Email Warning for Course Hiding';
$string['hidewarningemailsetting_help'] = 'This is the contents of an email that will be sent to all teachers of a course that is selected to be hidden.';
$string['hidewarningemailsettingdefault'] = '%to

We would like to inform you that the following Moodle course(s) that you have taught are soon to be hidden.
This means that students who are still enrolled in the course will no longer have access to the courses.  If you would like to opt out of this process for one of the following courses, please click the link beside the course you wish to opt out of.

%courses

Thank you.
';

$string['archivewarningemailsetting'] = 'Default Email Warning for Course Archival';
$string['archivewarningemailsetting_help'] = 'This is the contents of an email that will be sent to all teachers of a course that is selected to be archived.';
$string['archivewarningemailsettingdefault'] = '%to

We would like to inform you that the following Moodle course(s) that you have taught are soon to be archived.
This means that the course will be backed up in its current state and then removed from Moodle.  If you would like to opt out of this process for one of the following courses, please click the link beside the course you wish to opt out of.

%courses

Thank you.
';
$string['archivewarningsubject'] = 'Notice: Courses will be archived soon.';

$string['coursearchiverpath'] = 'Folder path to store archived courses';
$string['coursearchiverpath_help'] = 'This path is relative to the Moodle $CFG->dataroot';
$string['hidewarningsubject'] = 'Notice: Courses will be hidden soon.';
$string['optouthidesubject'] = 'Please do not hide my course';
$string['optouthidemessage'] = 'I would like to keep the following course from being hidden.

{$a}

Thank you.
';


$string['optoutarchivesubject'] = 'Please do not archive my course';
$string['optoutarchivemessage'] = 'I would like to keep the following course from being archived.

{$a}

Thank you.
';

$string['messageprovider:courseowner'] = 'Notifications from the course archival/hiding tool.';