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

defined('MOODLE_INTERNAL') || die;

namespace mod_syllabus\output;

use context_module;
use mod_syllabus_external;

/**
* Mobile output class for syllabus
* 
* @package 		mod_syllabus
* @copyright 	2021 Marty Gilbert <martygilbert@gmail>
* @license 		http://www.gnu.org/copyleft/gpl.html BNU GPL v3 or later
*/
class mobile {
	
	public static function mobile_course_view($args) {
		global $OUTPUT, $USER, $DB;
		error_log ("mobile_course_view");

		$args = (object) $args;
		$cm = get_coursemodule_from_id('syllabus', $args->cmid);

		// Capabilities check.
		context = \context_module::instance($cm->id);

		require_capability ('mod/syllabus:view', $context);

		$syllabus = $DB->get_record('syllabus', array('id' => $cm->instance));
		error_log(print_r($syllabus, true));

		try {
			$syllabi = mod_syllabus_external::view_syllabus($cm->instance);
		} catch (Exception $e) {
			$issues = array();
		}
		
		$data = [ 
			$issues 
		];

		return [
			'templates' => [
				[
					'id' => 'main',
					'html' => '',
				],
			],
			'javascript' => '',
			'otherdata' => '',
			'files' => '',
		];
	}

}
