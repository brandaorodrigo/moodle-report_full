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
ini_set("max_execution_time", 2000);

require_once(dirname(__FILE__) . "/../../config.php");
require_once(dirname(__FILE__) . "/locallib.php");
$courseid = required_param("id", PARAM_INT);
$course = $DB->get_record("course", array("id" => $courseid), "*", MUST_EXIST);
require_login($course);
$coursecontext = context_course::instance($course->id);
require_capability("moodle/course:viewhiddenuserfields", $coursecontext);
$userfields = report_full_getUserFields();
$sections = report_full_getCourseModules($course->id);
$gradeitems = report_full_get_getCourseGradeItems($course->id);
$roles = report_full_getRoles();
$PAGE->set_url("/report/full/index.php", array("id" => $course->id));
$PAGE->set_title($course->shortname .": ". get_string("full", "report_full"));
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string("generate", "report_full")." (".$course->shortname.")");
?>
<?php if (isset($_GET["error"])) : ?><div>Nenhum usuário encontrado para gerar relatório</div><?php endif ?>
<form method="post" class="mform" action="download.php">
<input type="hidden" name="id" value="<?php echo $course->id ?>">
<section>
<fieldset class="clearfix collapsible" id="id_general">
<legend class="ftoggler"><a href="#" class="fheader" role="button" aria-controls="id_general" aria-expanded="true">Relatório completo</a></legend>
<div class="fcontainer clearfix" id="yui_3_17_2_1_1487779627975_268">

	<div id="fitem_id_numsectionscol52" class="fitem fitem_fselect">
		<div class="fitemtitle"><label for="role">Tipo de inscrição</label></div>
		<div class="felement fselect">
			<select name="role">
			<?php foreach ($roles as $id => $role) : ?>
				<option value="<?php echo $id ?>"><?php echo get_string($role, "report_full") ?></option>
			<?php endforeach ?>
			</select>
		</div>
	</div>
	<div id="fitem_id_numsectionscol52" class="fitem fitem_fselect">
		<div class="fitemtitle"><label for="role">Campos do usuário</label></div>
		<div class="felement fselect">
			<select name="userfieldids[]" multiple="multiple" size="10">
			<?php foreach ($userfields as $id => $userfield) : ?>
				<option value="<?php echo $id ?>"><?php echo $userfield ?></option>
			<?php endforeach ?>
			</select>
		</div>
	</div>
	<div id="fitem_id_numsectionscol52" class="fitem fitem_fselect">
		<div class="fitemtitle"><label for="role">Grupos</label></div>
		<div class="felement fselect">
			<input type="checkbox" name="groups" value="1"> Retornar grupos que o usuário faz parte.
		</div>
	</div>
	<div id="fitem_id_numsectionscol52" class="fitem fitem_fselect">
		<div class="fitemtitle"><label for="role">Recursos e atividades acessados</label></div>
		<div class="felement fselect">
		<select name="moduleids[]" multiple="multiple" size="10">
			<?php foreach ($sections as $section) : ?>
				<optgroup label="<?php echo $section->name ?>">
				<?php foreach ($section->sequence as $module) : ?>
					<?php if ($module->fullname) : ?>
						<option value="<?php echo $module->id ?>"><?php echo $module->fullname ?></option>
					<?php endif ?>
				<?php endforeach ?>
				</optgroup>
			<?php endforeach; ?>
		</select>
		</div>
	</div>
	<div id="fitem_id_numsectionscol52" class="fitem fitem_fselect">
		<div class="fitemtitle"><label for="role">Item de nota</label></div>
		<div class="felement fselect">
			<select name="gradeitemids[]" multiple="multiple" size="10">
			<?php foreach ($gradeitems as $gradeitemid => $gradeitem) : ?>
				<option value="<?php echo $gradeitemid ?>"><?php echo $gradeitem ?></option>
			<?php endforeach ?>
			</select>
		</div>
	</div>
</div>
</fieldset>

<fieldset class="hidden">
	<div id="fgroup_id_buttonar" class="fitem fitem_actionbuttons fitem_fgroup ">
		<div class="felement fgroup">
			<input type="submit" value="<?php echo get_string("generate", "report_full") ?>">
		</div>
	</div>
</fieldset>

</section>
</form>
<?php
echo $OUTPUT->footer();
