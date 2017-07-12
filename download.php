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

ini_set("memory_limit","-1");
ini_set("max_execution_time", 3600);

require_once(dirname(__FILE__) . "/../../config.php");
require_once(dirname(__FILE__) . "/locallib.php");
require_once dirname(__FILE__)."/phpexcel/Classes/PHPExcel.php";

$courseid = required_param("id", PARAM_INT);

$course = $DB->get_record("course", array("id" => $courseid), "*", MUST_EXIST);
require_login($course);

$coursecontext = context_course::instance($course->id);
require_capability("moodle/course:viewhiddenuserfields", $coursecontext);

$users = array();
$userfieldids = array();
$col_groups = array();
$groups = array();
$col_gradeitemids = array();
$gradeitemids = array();
$col_moduleids = array();
$moduleids = array();

$role = @$_POST["role"];
$userfieldids = @$_POST["userfieldids"];
$groups = @$_POST["groups"];
$moduleids = @$_POST["moduleids"];
$gradeitemids = @$_POST["gradeitemids"];

if (!$role) {
	exit(header("Location: index.php?id={$courseid}"));
}

$users  = report_full_getUserIdsByRole($course->id, $role);

if (!$users) {
	exit(header("Location: index.php?id={$courseid}&error=1"));
}

if (count($userfieldids)) {
	$col_userfieldids = report_full_getUserFields(true);
	$userfieldids = report_full_getUserFieldsByUserIds($course->id, $users, $userfieldids);
}

if (count($groups)) {
	$total_groups = 1;
	$groups = report_full_getUserGroupsByUserIds($course->id, $users);
	foreach ($groups as $group) {
		if (count($group) > $total_groups) {
			$total_groups = count($group);
		}
	}
	$col_groups = array();
	for ($i = 1; $i <= $total_groups; $i++) {
		$col_groups[] = "group".$i;
	}
}

if (count($gradeitemids)) {
	$col_gradeitemids = report_full_get_getCourseGradeItems($course->id, $gradeitemids);
	$gradeitemids = report_full_getUserGrades($course->id, $users, $gradeitemids);
}

if (count($moduleids)) {
	$col_sections = report_full_getCourseModules($course->id, $moduleids);
	foreach ($col_sections as $section) {
		foreach ($section->sequence as $s) {
			$col_moduleids[$s->id] = $section->name.": ".$s->fullname;
		}
	}
	$moduleids = report_full_getModuleAccessByUserId($course->id, $users, $moduleids);
}

foreach ($users as $id => &$u) {
	$u = new stdClass();
	@$u->userfield = $userfieldids[$id];
	@$u->group = $groups[$id];
	@$u->module = $moduleids[$id];
	@$u->grade = $gradeitemids[$id];
}

$objPHPExcel = new PHPExcel();

$col = 1;
$rol = 1;

foreach (current($userfieldids) as $key => $userfield) {
	$objPHPExcel->setActiveSheetIndex(0)->setCellValue(excel_letter($col).$rol, $col_userfieldids[$key]);
	$col++;
}
foreach ($col_groups as $group) {
	$objPHPExcel->setActiveSheetIndex(0)->setCellValue(excel_letter($col).$rol, $group);
	$col++;
}
foreach ($col_moduleids as $module) {
	$objPHPExcel->setActiveSheetIndex(0)->setCellValue(excel_letter($col).$rol, $module);
	$col++;
}
foreach ($col_gradeitemids as $gradeitem) {
	$objPHPExcel->setActiveSheetIndex(0)->setCellValue(excel_letter($col).$rol, $gradeitem);
	$col++;
}

$rol++;

foreach ($users as $u) {
	$col = 1;
	foreach ($u->userfield as $uu) {
		$objPHPExcel->setActiveSheetIndex(0)->setCellValue(excel_letter($col).$rol, $uu);
		$col++;
	}
	for ($i = 0; $i < $total_groups; $i++) {
		$objPHPExcel->setActiveSheetIndex(0)->setCellValue(excel_letter($col).$rol, @$u->group[$i]);
		$col++;
	}
	foreach ($col_moduleids as $key => $module) {
		$objPHPExcel->setActiveSheetIndex(0)->setCellValue(excel_letter($col).$rol, (isset($u->module[$key]) ? date("d/m/Y H:i", $u->module[$key]) : "-"));
		$col++;
	}
	foreach ($col_gradeitemids as $key => $gradeitem) {
		$objPHPExcel->setActiveSheetIndex(0)->setCellValue(excel_letter($col).$rol, @$u->grade[$key]);
		$col++;
	}
	$rol++;
}

$objPHPExcel->getActiveSheet()->setTitle("report");

$objPHPExcel->setActiveSheetIndex(0);

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment;filename='moodle_report_all_".date("YmdHi").".xls'");
header("Cache-Control: max-age=0");
header("Cache-Control: max-age=1");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
header("Cache-Control: cache, must-revalidate");
header("Pragma: public");

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, "Excel5");
$objWriter->save("php://output");

exit();

?>
<style>
body { margin: 0; padding: 0; }
table { font-size: 10px; font-family: courier; padding: 0; margin: 0; border: 0; border-collapse: collapse;}
table td, table th { white-space:nowrap; border-collapse: collapse; border: 1px solid #ddd; padding: 4px; }
table th { background: #333; color: #fff; }
</style>
<table>
	<tr>
		<th>id</th>
		<?php foreach (current($userfieldids) as $key => $userfield) : ?>
		<th><?php echo $col_userfieldids[$key] ?></th>
		<?php endforeach ?>
		<?php foreach ($col_groups as $group) : ?>
		<th><?php echo $group ?>
		<?php endforeach ?>
		<?php foreach ($col_moduleids as $module) : ?>
		<th><?php echo $module ?></th>
		<?php endforeach ?>
		<?php foreach ($col_gradeitemids as $gradeitem) : ?>
		<th><?php echo $gradeitem ?></th>
		<?php endforeach ?>
	</tr>
	<?php foreach ($users as $u) : ?>
	<tr>
		<?php foreach ($u->userfield as $uu) : ?>
		<td><?php echo $uu ?></td>
		<?php endforeach ?>
		<?php for ($i = 0; $i < $total_groups; $i++) : ?>
		<td><?php echo @$u->group[$i] ?></td>
		<?php endfor ?>
		<?php foreach ($col_moduleids as $key => $module) : ?>
		<td><?php echo isset($u->module[$key]) ? date("d/m/Y H:i", $u->module[$key]) : "-" ?></td>
		<?php endforeach ?>
		<?php foreach ($col_gradeitemids as $key => $gradeitem) : ?>
		<td><?php echo @$u->grade[$key] ?></td>
		<?php endforeach ?>
		</tr>
	<?php endforeach ?>
</table>
<?php

ini_set("memory_limit","128M");
ini_set("max_execution_time", 30);

exit();
