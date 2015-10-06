<?php
/**
 * Messaging for Course Archiver.
 *
 * @package    tool_coursearchiver
 * @copyright  2015 Matthew Davidson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
$messageproviders = array (
    // Notify teacher that a student has submitted a quiz attempt
    'courseowner' => array (
        'capability'  => 'moodle/course:update'
    )
);
