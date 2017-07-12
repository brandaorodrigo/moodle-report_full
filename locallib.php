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
 * report_full
 *
 * @package    report_full
 * @author     Rodrigo Brandão (rodrigobrandao.com.br)
 * @copyright  2016 Rodrigo Brandão
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function report_full_getUserFieldsByDB() {
	global $DB;
	$sql = "DESCRIBE {user}";
	$columns = $DB->get_records_sql($sql);
	$userfields = array();
	foreach($columns as $column) {
		if ($column->field != "id") {
			$userfields[$column->field] = $column->field;	
		}
	}
	return $userfields;
}

function report_full_getUserFields($id = false) {
	if ($id) {
		$field["id"] = "ID";
	}
	$field["username"] = get_string("username", "core");
	$field["password"] = get_string("password", "core");
	$field["idnumber"] = get_string("idnumber", "core");
	$field["firstname"] = get_string("firstname", "core");
	$field["lastname"] = get_string("lastname", "core");
	$field["email"] = get_string("email", "core");
	$field["institution"] = get_string("institution", "core");
	$field["department"] = get_string("department", "core");
	$field["address"] = get_string("address", "core");
	$field["city"] = get_string("city", "core");
	$field["country"] = get_string("country", "core");
	$field["lastaccesscourse"] = get_string("lastaccesscourse", "report_full");
	$field["lastaccess"] = get_string("lastaccess", "core");
	$field["lastip"] = get_string("lastip", "core");
	$field["icq"] = get_string("icq", "core");
	$field["skype"] = get_string("skype", "core");
	$field["yahoo"] = get_string("yahoo", "core");
	$field["aim"] = get_string("aim", "core");
	$field["msn"] = get_string("msn", "core");
	$field["phone1"] = get_string("phone1", "core");
	$field["phone2"] = get_string("phone2", "core");
	$field["url"] = get_string("url", "core");
	$field["deleted"] = get_string("deleted", "core");
	$field["suspended"] = get_string("suspended", "core");
	return $field;
}

function report_full_getCourseModules($courseid, $moduleids = "") {
	global $DB;
	$sql = "
	SELECT 
		id, 
		section, 
		IF(name = '' OR name IS NULL, CONCAT('Tópico ', section), name) AS name, 
		visible, 
		sequence 
	FROM 
		{course_sections} 
	WHERE 
		course = {$courseid} 
	AND 
		sequence != ''
	ORDER BY 
		section ASC
	";
	$course_sections = $DB->get_records_sql($sql);
	if ($moduleids) {
		$moduleids = " AND cm.id IN (".implode(",", $moduleids).") ";
	}
	$sql = "
	SELECT 
		cm.id, 
		cm.module, 
		m.name, 
		cm.section, 
		cm.instance, 
		cm.visible 
	FROM 
		{course_modules} AS cm 
	INNER JOIN 
		{modules} AS m 
	ON 
		m.id = cm.module 
	WHERE 
		cm.course = {$courseid} 
	AND 
		m.name != 'label' 
		{$moduleids}
	ORDER BY 
		cm.id ASC
	";
	$course_modules = $DB->get_records_sql($sql);
	$sql = "
	SELECT 
		DISTINCT m.name 
	FROM 
		{course_modules} AS cm 
	INNER JOIN 
		{modules} AS m 
	ON 
		m.id = cm.module 
	WHERE 
		cm.course = {$courseid} 
	ORDER BY 
		cm.id ASC
	";
	$search_mod = $DB->get_records_sql($sql);
	$search_modules = array();
	foreach ($search_mod as $s) {
		$search_modules[$s->name] = 1;
	}
	foreach ($search_modules as $k => $i) {
		$sql = "
		SELECT 
			id, 
			name 
		FROM 
			{{$k}} 
		WHERE 
			course = ".$courseid."
		";
		$search_modules[$k] = $DB->get_records_sql($sql);
	}
	foreach ($course_modules as $k => $m) {
		$course_modules[$k]->fullname = $search_modules[$m->name][$m->instance]->name;
	}
	foreach ($course_sections as &$s) {
		$s->sequence = explode(",", $s->sequence);
	}
	foreach ($course_sections as $key => $s) {
		foreach ($s->sequence as $key2 => $ss) {
			foreach ($course_modules as $m) {
				if ($ss == $m->id) {
					$course_sections[$key]->sequence[$key2] = $m;
				}
			}
			if (is_string($course_sections[$key]->sequence[$key2])) {
				unset($course_sections[$key]->sequence[$key2]);
			}
		}
		if (count($course_sections[$key]->sequence) == 0) {
			unset($course_sections[$key]);
		}
	}
	return $course_sections;
}

function report_full_get_getCourseGradeItems($courseid, $gradeitemids = false) {
	global $DB;
	if ($gradeitemids) {
		$gradeitemids = " AND id IN (".implode(",", $gradeitemids).") ";
	}
	$coursetotal = get_string("coursetotal", "core_grades");
	$sql = "
	SELECT
		id,
		IF(itemname IS NULL OR itemname = '', '{$coursetotal}', itemname) AS itemname
	FROM 
		{grade_items}
	WHERE
		itemtype != 'course'
	AND
		hidden = 0
	AND
		grademax > 0	
	AND
		courseid = {$courseid}
		{$gradeitemids}
	ORDER BY
		sortorder ASC
	";
	$records = $DB->get_records_sql($sql);
	$gradeitems = array();
	foreach ($records as $record) {
		$gradeitems[$record->id] = $record->itemname;
	}
	return $gradeitems;
}

function report_full_getRoles() {
	global $DB;
	$sql = "
	SELECT 
		id,
		shortname
	FROM 
		{role}
	";
	$lines = $DB->get_records_sql($sql);
	$roles = array();
	foreach ($lines as $line) {
		$roles[$line->id] = $line->shortname;
	}
	return $roles;
}

/* - - - */

function report_full_getUserIdsByRole($courseid, $role) {
	global $DB;
	$sql = "
	SELECT
		ra.id,
		ra.userid,
		r.shortname
	FROM 
		{role_assignments} AS ra
	INNER JOIN 
		{role} AS r
	ON
		r.id = ra.roleid
	WHERE 
		ra.contextid IN (
		SELECT 
			co.id AS contextid 
		FROM 
			{course} AS c 
		INNER JOIN 
			{context} AS co 
		ON 
			co.instanceid = c.id 
		WHERE 
			c.id = {$courseid}
		)
	AND
		ra.roleid = {$role}
	ORDER BY
		ra.userid
	";
	$records = $DB->get_records_sql($sql);
	$users = array();
	foreach ($records as $record) {
		$users[$record->userid] = $record->userid;
	}
	return $users;
}

function report_full_getUserGroupsByUserIds($courseid, $users) {
	global $DB;
	$userids = implode(",", $users);
	$sql = "
	SELECT
		gm.id,
		gm.userid,
		g.name
	FROM
		{groups_members} AS gm 
	INNER JOIN 
		{groups} AS g 
	ON
		g.id = gm.groupid
	WHERE 
		g.courseid = {$courseid}
	AND
		gm.userid IN ({$userids})
	ORDER BY
		gm.userid
	";
	$records = $DB->get_records_sql($sql);
	$groups = array();
	foreach ($records as $record) {
		$groups[$record->userid][] = $record->name;
	}
	return $groups;
}

function report_full_getUserFieldsByUserIds($courseid, $userids, $fields) {
	global $DB;
	$userids = implode(",", $userids);
	$cols = array("u.id");
	/* special fields */
	if ($fields) {
		foreach ($fields as &$field) {
			if ($field == "lastaccesscourse") {
				$field = "IF(ul.timeaccess IS NULL, '-', FROM_UNIXTIME(ul.timeaccess, '%d/%m/%Y %H:%i')) AS lastaccesscourse";
			}
			if ($field == "lastaccess" || $field == "firstaccess") {
				$field = "IF(u.{$field} IS NULL, '-', FROM_UNIXTIME(u.{$field}, '%d/%m/%Y %H:%i')) AS {$field}";
			}
			$cols[] = $field;
		}
	}
	$cols = implode(",", $cols);
	$sql = "
	SELECT
		{$cols}
	FROM 
		{user} AS u
	LEFT JOIN
		{user_lastaccess} AS ul
	ON
		ul.courseid = {$courseid} 
	AND 
		ul.userid = u.id
	WHERE
		u.id IN ({$userids})
	ORDER BY
		u.firstname ASC, 
		u.lastname ASC
	";
	$records = $DB->get_records_sql($sql);
	$users = array();
	foreach ($records as $record) {
		$users[$record->id] = (array)$record;
	}
	return $users;
}

function report_full_getUserGrades($courseid, $userids, $gradeitemids) {
	global $DB;
	$grades = array();
	if (count($userids) > 0 && count($gradeitemids) > 0) {
		$userids = implode(",", $userids);
		$gradeitemids = implode(",", $gradeitemids);
		$sql = "
		SELECT
			id,
			userid,
			itemid,
			finalgrade 
		FROM 
			{grade_grades} 
		WHERE 
			userid IN ({$userids})
		AND 
			itemid IN ({$gradeitemids})
		";
		$records = $DB->get_records_sql($sql);
		foreach ($records as $record) {
			$grades[$record->userid][$record->itemid] = $record->finalgrade;
		}
	}
	return $grades;
}

function report_full_getModuleAccessByUserId($courseid, $users, $moduleids) {
	global $DB;
	$userids = implode(",", $users);
	$moduleids = implode(",", $moduleids);
	$sql = "
	SELECT 
		id,
		courseid,
		userid,
		contextinstanceid,
		component,
		action,
		target,
		objectid,
		contextid,
		timecreated
	FROM
		{logstore_standard_log} 
	WHERE 
		action = 'viewed' 
	AND 
		courseid = {$courseid} 
	AND 
		userid IN ({$userids})
	AND
		contextinstanceid IN ({$moduleids})
	ORDER BY
		timecreated DESC
	";
	$records = $DB->get_records_sql($sql);
	$contextinstanceid = array();
	foreach ($records as $r) {
		$contextinstanceid[$r->userid][$r->contextinstanceid] = $r->timecreated;
	}
	return $contextinstanceid;
}

function excel_letter($v) {
	$alphabet = range("A", "Z");
	if ($v > 26) {
		$i = 0;
		$t = 26;
		while ($v > $t) {
			$t = $t + 26;
			$i++;
		}
		$h = $v - (26 * $i);
		$primeira_letra = @$alphabet[$i-1];
		$segunda_letra = @$alphabet[$h-1];
		$return = $primeira_letra.$segunda_letra;
	} else {
		$return = $alphabet[$v - 1];
	}
	return $return;
}

