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
 * Creates settings and links to Rose-Hulman Course Archive tool.
 *
 * @package    tool_coursearchiver
 * @copyright  2015 Matthew Davidson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('tool_coursearchiver', get_string('coursearchiver_settings', 'tool_coursearchiver'));

    $name = new lang_string('coursearchiverrootpath', 'tool_coursearchiver');
    $description = new lang_string('coursearchiverrootpath_help', 'tool_coursearchiver');
    $default = $CFG->dataroot;
    $settings->add(new admin_setting_configtext('tool_coursearchiver/coursearchiverrootpath',
                                                $name,
                                                $description,
                                                $default));

    $name = new lang_string('coursearchiverpath', 'tool_coursearchiver');
    $description = new lang_string('coursearchiverpath_help', 'tool_coursearchiver');
    $default = 'CourseArchives';
    $settings->add(new admin_setting_configtext('tool_coursearchiver/coursearchiverpath',
                                                $name,
                                                $description,
                                                $default));

    // Default role of course owners.
    $ownernewroles = [];
    $default = [];
    $roles = role_fix_names(get_all_roles(), null, ROLENAME_ORIGINALANDSHORT);
    foreach ($roles as $role) {
        if ($role->archetype == "editingteacher") {
            $default[] = $role->id;
        }
        $ownernewroles[$role->id] = $role->localname;
    }

    // If no role was selected by default choose the first role.
    if (empty($default)) {
        $default[] = array_values($roles)[0]->id;
    }
    $name = new lang_string('ownerroleid', 'tool_coursearchiver');
    $description = new lang_string('ownerroleid_help', 'tool_coursearchiver');
    $settings->add(new admin_setting_configmultiselect('tool_coursearchiver/ownerroleid',
                                                       $name,
                                                       $description,
                                                       $default,
                                                       $ownernewroles));

    // Default email for upcoming hiding of courses.
    $name = new lang_string('hidewarningemailsetting', 'tool_coursearchiver');
    $description = new lang_string('hidewarningemailsetting_help', 'tool_coursearchiver');
    $default = get_string('hidewarningemailsettingdefault', 'tool_coursearchiver');
    $settings->add(new admin_setting_configtextarea('tool_coursearchiver/hidewarningemailsetting',
                                                    $name,
                                                    $description,
                                                    $default));

    // Default email for upcoming course archiving.
    $name = new lang_string('archivewarningemailsetting', 'tool_coursearchiver');
    $description = new lang_string('archivewarningemailsetting_help', 'tool_coursearchiver');
    $default = get_string('archivewarningemailsettingdefault', 'tool_coursearchiver');
    $settings->add(new admin_setting_configtextarea('tool_coursearchiver/archivewarningemailsetting',
                                                    $name,
                                                    $description,
                                                    $default));
    // Automatic opt out in months.
    $settings->add(new admin_setting_configtext('tool_coursearchiver/optoutmonthssetting',
                   get_string('optoutmonthssetting', 'tool_coursearchiver'),
                   get_string('optoutmonthssetting_help', 'tool_coursearchiver'), 24, PARAM_INT));

    // Archive deletion delay in days.
    $settings->add(new admin_setting_configtext('tool_coursearchiver/delaydeletesetting',
                   get_string('archivedeletesetting', 'tool_coursearchiver'),
                   get_string('archivedeletesetting_help', 'tool_coursearchiver'), 7, PARAM_INT));

    // Limit archive files shown.
    $settings->add(new admin_setting_configtext('tool_coursearchiver/archivelimit',
                   get_string('archivelimit', 'tool_coursearchiver'),
                   get_string('archivelimit_help', 'tool_coursearchiver'), 200, PARAM_INT));

    // Limit savepoints listed in select.
    $settings->add(new admin_setting_configtext('tool_coursearchiver/savelimitsetting',
    get_string('savelimitsetting', 'tool_coursearchiver'),
    get_string('savelimitsetting_help', 'tool_coursearchiver'), 10, PARAM_INT));

    // Link to Course Archiver tool.
    $ADMIN->add('courses', new admin_externalpage('toolcoursearchiver',
        get_string('coursearchiver', 'tool_coursearchiver'), "$CFG->wwwroot/$CFG->admin/tool/coursearchiver/index.php"));

    // Add the category to the admin tree.
    $ADMIN->add('tools', $settings);
}
