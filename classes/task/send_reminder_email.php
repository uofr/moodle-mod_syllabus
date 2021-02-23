<?php
// This file is part of Moodle - http://moodle.org/
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
 * A scheduled task for forum cron.
 *
 * @package    mod_forum
 * @copyright  2021 Marty Gilbert <martygilbert@gmail>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_syllabus\task;

defined('MOODLE_INTERNAL') || die();

class send_reminder_email extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('reminderemail', 'mod_syllabus');
    }


    public function execute() {
        global $DB, $OUTPUT;

        $val = get_config('syllabus', 'remindersenabled');
        if (!$val) {
            return;
        }

        // First index is instructor; second is course->shortname.
        $coursestoprocess = array();

        $cat        = get_config('syllabus', 'uniquecatname');
        $tohidden   = get_config('syllabus', 'emailstohidden');

        if (!empty($cat)) {
            $cats = $DB->get_records('course_categories', array('name' => $cat));

            if (count($cats) == 0) {
                mtrace("No categories named $cat. Emailing admin and exiting.");
                $this->email_admin("nocatsnamed");
                return;
            } else if (count($cats) > 1) {
                mtrace("More than one category named $cat. Emailing admin and exiting.");
                $this->email_admin("morethanonecat");
                return;
            }

            $thiscat = reset($cats);
            $catid = $thiscat->id;
        } else {
            $catid = 0;
        }

        mtrace("Preparing to process category id: $catid");

        $coursecat = \core_course_category::get($catid);
        $courses = $coursecat->get_courses(array('recursive' => true, 'idonly' => true));
        //error_log(var_dump($courses));

        $now = time();
        foreach ($courses as $courseid) {
            $course = get_course($courseid);
            $syllabi = get_all_instances_in_course('syllabus', $course, null, true);

            if (count($syllabi) == 0 && $course->startdate < $now && $course->enddate > $now) {

                if ($course->visible || (!$course->visible && $tohidden)) {

                    // Get instructor(s) to notify.
                    $coursecon = \context_course::instance($course->id);
                    $teachers = get_users_by_capability($coursecon, 'moodle/backup:backupcourse', 'u.id');

                    foreach ($teachers as $teacher) {
                        $coursestoprocess[$teacher->id][$course->shortname]['name'] = $course->fullname;
                        $coursestoprocess[$teacher->id][$course->shortname]['url'] = (string) new \moodle_url('/course/view.php',
                            array('id' => $course->id));
                    }

                }
            }
        }

        $numfaculty = count($coursestoprocess);
        mtrace("Sending emails to $numfaculty faculty member(s)");

        $datestr = userdate($now, get_string('strftimedatefullshort', 'core_langconfig'));
        $docurl = get_config('syllabus', 'addsyllabuslink');
        $data = array();
        foreach ($coursestoprocess as $teacherid => $courses) {
            $data['courses'] = array_values($courses);
            $data['docurl'] = $docurl;
            $msg = $OUTPUT->render_from_template('mod_syllabus/email_reminder', $data);
            $this->email_teacher($teacherid, $msg, $datestr);
        }
    }

    private function email_teacher($teacherid, $msg, $datestr) {
        global $DB;
        $teacher = $DB->get_record('user', array('id' => $teacherid));
        $admin = get_admin();

        email_to_user($teacher, $admin, get_string('emailsubj', 'mod_syllabus') . ' - ' . $datestr . ' - ' . $teacher->username, html_to_text($msg), $msg);

    }

    private function email_admin($msg) {
        $admin = get_admin();
        email_to_user($admin, $admin, 'Invalid category in mod_syllabus admin settings', html_to_text($msg), $msg);
    }

}
