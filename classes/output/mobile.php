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
 * @package    mod_syllabus
 * @copyright  2021 Marty Gilbert <martygilbert@gmail>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_syllabus\output;

defined('MOODLE_INTERNAL') || die;


use context_module;
use mod_syllabus_external;

/**
* Mobile output class for syllabus
* 
* @package      mod_syllabus
* @copyright    2021 Marty Gilbert <martygilbert@gmail>
* @license      http://www.gnu.org/copyleft/gpl.html BNU GPL v3 or later
*/
class mobile {
    
    public static function mobile_syllabus_view($args) {
        global $OUTPUT, $USER, $DB;
        error_log ("mobile_syllabus_view");

        $args = (object) $args;
        $cm = get_coursemodule_from_id('syllabus', $args->cmid);

        // Capabilities check.
        $context = \context_module::instance($cm->id);

        require_capability ('mod/syllabus:view', $context);


        $syllabus = $DB->get_record('syllabus', array('id' => $cm->instance), '*', MUST_EXIST);
        //error_log("in mobile.php: " . print_r($syllabus, true));

        try {
            $syllabi = mod_syllabus_external::view_syllabus($cm->instance);
            //error_log("syllabi " . print_r($syllabi, true));

        } catch (Exception $e) {
            $issues = array();
        }

        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_syllabus', 'content', 0, 'sortorder DESC, id ASC', false); // TODO: this is not very efficient!!
        $file = reset($files);
        unset($files);

        $syllabus->mainfile = $file->get_filename();
        $path = '/'.$context->id.'/mod_syllabus/content/'.$syllabus->revision.$file->get_filepath().$file->get_filename();
        $fullurl = \moodle_url::make_file_url('/pluginfile.php', $path, true);
        
        $thisfile = new \stdClass;
        $thisfile->filename = $file->get_filename();
        $thisfile->fileurl      = (string) $fullurl;
        $thisfile->url      = (string) $fullurl;
        $thisfile->name     = 'Just name';
        $thisfile->timemodified = $syllabus->timemodified;
        $thisfile->size     = 66829;
        //error_log(print_r($file, true));

        $data = array( 
            'file'  => $thisfile,
        );

        //$thisfile = array_values($thisfile);

        error_log(print_r($data, true));

        return [
            'templates' => [
                [
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template('mod_syllabus/mobile_view_syllabus', $data),
                ],
            ],
            'javascript' => '',
            'otherdata' => 'test42',
            'files' => $data,
        ];
    }

}
