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

namespace tool_coursearchiver\privacy;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\writer;
use core_privacy\local\request\userlist;


/**
 * Privacy Subsystem for tool_coursearchiver implementing null_provider.
 *
 * @copyright  2015 Matthew Davidson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\request\data_provider, \core_privacy\local\metadata\provider {
    /**
     * Returns metadata about this plugin's privacy policy.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'tool_coursearchiver_optout',
            [
                'userid' => 'privacy:metadata:tool_coursearchiver_optout:userid',
            ],
            'privacy:metadata:tool_coursearchiver_optout_user'
        );
        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the given user.
     *
     * @param int $userid the userid to search.
     * @return contextlist the contexts in which data is contained.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new \core_privacy\local\request\contextlist();
        $contextlist->add_user_context($userid);
        $contextlist->add_system_context();
        return $contextlist;
    }

    /**
     * Gets the list of users who have data with a context.
     *
     * @param userlist $userlist the userlist containing users who have data in this context.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        // If current context is system, all users are contained within, get all users.
        if ($context->contextlevel == CONTEXT_SYSTEM) {
            $sql = " SELECT *
                       FROM {tool_coursearchiver_optout}";
            $userlist->add_from_sql('userid', $sql, []);
        }
    }

    /**
     * Exports all data stored in provided contexts for user.
     *
     * @param approved_contextlist $contextlist the list of contexts to export for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist as $context) {
            // If not in system context, exit loop.
            if ($context->contextlevel == CONTEXT_SYSTEM) {
                $parentclass = [];

                // Get records for user ID.
                $rows = $DB->get_records('tool_coursearchiver_optout', ['userid' => $userid]);
                if (count($rows) > 0) {
                    $i = 0;
                    foreach ($rows as $row) {
                        $parentclass[$i]['userid'] = $row->userid;
                        $i++;
                    }
                }

                writer::with_context($context)->export_data(
                    [get_string('privacy:metadata:tool_coursearchiver', 'tool_coursearchiver')],
                    (object)$parentclass
                );
            }
        }
    }

    /**
     * Deletes data for all users in context.
     *
     * @param context $context The context to delete for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
    }

    /**
     * Deletes all data in all provided contexts for user.
     *
     * @param approved_contextlist $contextlist the list of contexts to delete for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
    }
}
