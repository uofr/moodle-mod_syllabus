<?php


defined('MOODLE_INTERNAL') || die();

$observers = array(
	array(
		'eventname' => '\mod_syllabus\event\course_module_updated',
		'callback' =>  'mod_syllabus_observer::syllabus_updated',

	),
);
