<?php


defined('MOODLE_INTERNAL') || die();

class mod_syllabus_observer {

	public static function syllabus_updated(\mod_syllabus\event\course_module_updated $event) {
		error_log(print_r($event, true));
		error_log("IN THE OBSERVER!!!");
	}

}
