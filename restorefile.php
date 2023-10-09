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

define('NO_OUTPUT_BUFFERING', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/filelib.php');

header('X-Accel-Buffering: no');

require_login();

$filename   = required_param('filename', PARAM_TEXT);
$filepath   = required_param('filepath', PARAM_PATH);
$contextid  = required_param('contextid', PARAM_INT);
$download    = optional_param('download', false, PARAM_BOOL);

global $CFG, $DB;

$rootpath = rtrim(get_config('tool_coursearchiver', 'coursearchiverrootpath'), "/\\");
$archivepath = trim(str_replace(str_split(':*?"<>|'),
                                '',
                                get_config('tool_coursearchiver', 'coursearchiverpath')),
                    "/\\");

$archive = $rootpath . '/' . $archivepath . '/' . $filepath . '/' . $filename;
if (file_exists($archive)) {
    $context = context_course::instance(SITEID);
    $fs = get_file_storage();
    $fileinfo = ['contextid' => $context->id,
                 'component' => 'backup',
                 'filearea' => 'course',
                 'itemid' => 0,
                 'filepath' => '/',
                 'filename' => $filename,
                 'timecreated' => time(),
                 'timemodified' => time(),
                ];

    $fsfile = $fs->get_file($fileinfo['contextid'],
                           $fileinfo['component'],
                           $fileinfo['filearea'],
                           $fileinfo['itemid'],
                           $fileinfo['filepath'],
                           $fileinfo['filename']);
    if (!$fsfile) {
        $fsfile = $fs->create_file_from_pathname($fileinfo, $archive);
    }

    if ($download) {
        // Read contents.
        if ($fsfile) {
            send_file($fsfile, $filename, 0, true);
        } else {
            $reset = new moodle_url('/admin/tool/coursearchiver/index.php');
            redirect($reset);
        }
    } else {
        $params['action'] = 'choosebackupfile';
        $params['filename'] = $filename;
        $params['filepath'] = $fsfile->get_filepath();
        $params['component'] = $fsfile->get_component();
        $params['filearea'] = $fsfile->get_filearea();
        $params['filecontextid'] = $fsfile->get_contextid();
        $params['contextid'] = context_system::instance()->id;
        $params['itemid'] = $fsfile->get_itemid();
        $restoreurl = new moodle_url('/backup/restorefile.php', $params);
        redirect($restoreurl);
    }
} else {
    $reset = new moodle_url('/admin/tool/coursearchiver/index.php');
    redirect($reset);
}
