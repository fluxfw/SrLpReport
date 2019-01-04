<?php

namespace srag\Plugins\SrCrsLpReport\ReportTableGUI;

use ilLearningProgressBaseGUI;
use ilObject;
use ilUtil;
use ilUserProfile;
use ilObjUserTracking;
use ilObjectLP;
use ilTrQuery;
use ilLPStatus;
use ilSelectInputGUI;
use ilMail;
use ilUserDefinedFields;
use ilTextInputGUI;
use \srag\CustomInputGUIs\SrTile\TableGUI\TableGUI;

/**
 * Class AbstractReportTableGUI
 *
 *
 * @author            studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 * @author            studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 *
 */
abstract class AbstractReportTableGUI extends TableGUI {

	public function __construct($parent, /*string*/
		$parent_cmd) {

		$this->course = true;
		$this->ref_id = $_GET['ref_id'];
		$this->obj_id = ilObject::_lookupObjectId($_GET['ref_id']);
		$this->user_fields = array();

		$this->setShowRowsSelector(false);
		$this->setSelectAllCheckbox('uid');

		parent::__construct($parent, /*string*/
			$parent_cmd);
	}


	protected function getColumnValue($column, /*array*/
		$row, /*bool*/
		$raw_export = false) {

		switch ($column) {
			case "status":
				return $this->getLearningProgressRepresentation($row[$column]);
				break;
			default:
				return (is_array($row[$column]) ? implode(", ", $row[$column]) : $row[$column]);
				break;
		}
	}


	/**
	 * @param int $status
	 * @param int $percentage
	 *
	 * @return string
	 */
	protected function getLearningProgressRepresentation($status = 0, $percentage = 0): string {
		switch ($status) {
			case 0:
				$path = ilLearningProgressBaseGUI::_getImagePathForStatus($status);
				$text = self::dic()->language()->txt(ilLPStatus::LP_STATUS_NOT_ATTEMPTED);
				break;
			default:
				$path = ilLearningProgressBaseGUI::_getImagePathForStatus($status);
				$text = ilLearningProgressBaseGUI::_getStatusText($status);
				break;
		}

		$representation = ilUtil::img($path, $text);
		if ($percentage > 0) {
			$representation = $representation . " " . $percentage . "%";
		}

		return $representation;
	}


	protected function initColumns()/*: void*/ {

		$this->addColumn("", "");

		foreach ($this->getSelectableColumns() as $column) {
			if ($this->isColumnSelected($column["id"])) {
				if (isset($column["icon"])) {
					$alt = self::dic()->language()->txt($column["type"]);
					$icon = '<img src="' . $column["icon"] . '" alt="' . $alt . '" />';
					$column['txt'] = $icon . ' ' . $column['txt'];
				}

				$this->addColumn($column["txt"], ($column["sort"] ? $column["id"] : NULL), "", false, "", $column["path"]);
			}
		}
	}


	protected function getSelectableColumns2() {
		$lng = self::dic()->language();

		$cols = array();

		// default fields
		$cols["login"] = array(
			"id" => "login",
			"sort" => "login",
			"txt" => $lng->txt("login"),
			"default" => true,
			"all_reports" => true
		);

		$cols["firstname"] = array(
			"id" => "firstname",
			"sort" => "firstname",
			"txt" => $lng->txt("firstname"),
			"default" => true,
			"all_reports" => true
		);

		$cols["lastname"] = array(
			"id" => "lastname",
			"sort" => "lastname",
			"txt" => $lng->txt("lastname"),
			"default" => true,
			"all_reports" => true
		);

		$user_profile = new ilUserProfile();
		$user_profile->skipGroup("preferences");
		$user_profile->skipGroup("settings");
		$user_profile->skipGroup("interests");
		$user_standard_fields = $user_profile->getStandardFields();

		foreach ($user_standard_fields as $key => $field) {
			if (self::dic()->settings()->get("usr_settings_course_export_" . $key)) {
				$cols[$key] = array(
					"id" => $key,
					"sort" => $key,
					"txt" => $lng->txt($key),
					"default" => true,
					"all_reports" => true
				);
			}
		}

		// additional defined user data fields
		$user_defined_fields = ilUserDefinedFields::_getInstance();
		//if($a_in_course)
		//{
		$user_defined_fields_for_course = $user_defined_fields->getCourseExportableFields();
		//}
		/*else
		{
			$user_defined_fields = $user_defined_fields->getGroupExportableFields();
		}*/
		foreach ($user_defined_fields_for_course as $definition) {
			if ($definition["field_type"] != UDF_TYPE_WYSIWYG) {
				$cols["udf_" . $definition["field_id"]] = array(
					"id" => "udf_" . $definition["field_id"],
					"sort" => "udf_" . $definition["field_id"],
					"txt" => $definition["field_name"],
					"default" => true,
					"all_reports" => true
				);

				$this->user_fields[] = $cols["udf_" . $definition["field_id"]];
			}
		}

		// show only if extended data was activated in lp settings
		include_once 'Services/Tracking/classes/class.ilObjUserTracking.php';
		$tracking = new ilObjUserTracking();

		/*
		if($tracking->hasExtendedData(ilObjUserTracking::EXTENDED_DATA_LAST_ACCESS))
		{
			$cols["first_access"] = array(
				"id" => "first_access",
				"txt" => $lng->txt("trac_first_access"),
				"default" => true);
			$cols["last_access"] = array(
				"id" => "last_access",
				"txt" => $lng->txt("trac_last_access"),
				"default" => true);
		}
		if($tracking->hasExtendedData(ilObjUserTracking::EXTENDED_DATA_READ_COUNT))
		{
			$cols["read_count"] = array(
				"id" => "read_count",
				"txt" => $lng->txt("trac_read_count"),
				"default" => true);
		}
		if($tracking->hasExtendedData(ilObjUserTracking::EXTENDED_DATA_SPENT_SECONDS) &&
			ilObjectLP::supportsSpentSeconds($this->type))
		{
			$cols["spent_seconds"] = array(
				"id" => "spent_seconds",
				"txt" => $lng->txt("trac_spent_seconds"),
				"default" => true);
		}*/

		/*if($this->isPercentageAvailable($this->obj_id))
		{
			$cols["percentage"] = array(
				"txt" => $lng->txt("trac_percentage"),
				"default" => true);
		}*/

		// do not show status if learning progress is deactivated

		$olp = ilObjectLP::getInstance($this->obj_id);

		if ($olp->isActive()) {

			$type = self::dic()->objDataCache()->lookupType($this->obj_id);
			$icon = ilObject::_getIcon("", "tiny", $type);

			$cols["status"] = array(
				"id" => "status",
				"sort" => "status",
				"txt" => self::dic()->language()->txt("learning_progress") . " " . ilObject::_lookupTitle($this->obj_id),
				"default" => true,
				"all_reports" => true,
				"icon" => $icon
			);
			/*$cols['status_changed'] = array(
				"id" => "status_changed",
				'txt' => $lng->txt('trac_status_changed'),
				'default' => false);*/
		}

		/*
		if(ilObjectLP::supportsMark($this->type))
		{
			$cols["mark"] = array(
				"id" => "mark",
				"txt" => $lng->txt("trac_mark"),
				"default" => true);
		}*/

		/*$cols["u_comment"] = array(
			"id" => $lng->txt("trac_comment"),
			"txt" => $lng->txt("trac_comment"),
			"default" => false);

		$cols["create_date"] = array(
			"id" => $lng->txt("create_date"),
			"txt" => $lng->txt("create_date"),
			"default" => false);*/

		return $cols;
	}


	protected function initData() {
		global $lng;

		$this->setExternalSorting(true);
		$this->setExternalSegmentation(true);
		$this->setLimit(99999999999, 99999999999);
		$this->determineOffsetAndOrder(true);

		$additional_fields = $this->getSelectedColumns();

		$check_agreement = false;

		$tr_data = ilTrQuery::getUserDataForObject($this->ref_id, ilUtil::stripSlashes($this->getOrderField()), ilUtil::stripSlashes($this->getOrderDirection()), ilUtil::stripSlashes($this->getOffset()), ilUtil::stripSlashes($this->getLimit()), $this->filter, $additional_fields, $check_agreement, $this->user_fields);

		if (count($tr_data["set"]) == 0 && $this->getOffset() > 0) {
			$this->resetOffset();
			$tr_data = ilTrQuery::getUserDataForObject($this->ref_id, ilUtil::stripSlashes($this->getOrderField()), ilUtil::stripSlashes($this->getOrderDirection()), ilUtil::stripSlashes($this->getOffset()), ilUtil::stripSlashes($this->getLimit()), $this->filter, $additional_fields, $check_agreement, $this->user_fields);
		}

		foreach ($this->user_fields as $key => $value) {
			if ($this->filter[$value['id']]) {

				foreach ($tr_data["set"] as $key => $data) {
					if ($data[$value['id']] != $this->filter[$value['id']]) {
						unset($tr_data["set"][$key]);
						$tr_data["cnt"] = $tr_data["cnt"] - 1;
					}
				}
			}
		}

		$this->setMaxCount($tr_data["cnt"]);
		$this->setData($tr_data["set"]);
	}


	protected function initFilterFields() {

		foreach ($this->getSelectableColumns2() as $key => $value) {

			if ($value['all_reports'] !== true) {
				continue;
			}

			switch ($key) {
				case "status":

					self::dic()->language()->loadLanguageModule('trac');
					$item = new ilSelectInputGUI($value['txt'], $key);
					$item->setOptions(array(
						"" => self::dic()->language()->txt("trac_all"),
						ilLPStatus::LP_STATUS_NOT_ATTEMPTED_NUM + 1 => self::dic()->language()->txt(ilLPStatus::LP_STATUS_NOT_ATTEMPTED),
						ilLPStatus::LP_STATUS_IN_PROGRESS_NUM + 1 => self::dic()->language()->txt(ilLPStatus::LP_STATUS_IN_PROGRESS),
						ilLPStatus::LP_STATUS_COMPLETED_NUM + 1 => self::dic()->language()->txt(ilLPStatus::LP_STATUS_COMPLETED),
						ilLPStatus::LP_STATUS_FAILED_NUM + 1 => self::dic()->language()->txt(ilLPStatus::LP_STATUS_FAILED)
					));
					$this->addFilterItem($item);
					$item->readFromSession();

					if ($item->getValue()) {
						$this->filter[$key] = $item->getValue();
						$this->filter["status"] --;
					}
					break;
				default:
					$item = new ilTextInputGUI($value['txt'], $key);
					$this->addFilterItem($item);
					$item->readFromSession();
					$this->filter[$key] = $item->getValue();
					break;
			}
		}
	}


	protected function initTitle() {
		// TODO: Implement initTitle() method.
	}


	/**
	 *
	 */
	protected function initCommands()/*: void*/ {
		// see ilObjCourseGUI::addMailToMemberButton()
		$mail = new ilMail(self::dic()->user()->getId());
		if (self::dic()->rbacsystem()->checkAccess("internal_mail", $mail->getMailObjectReferenceId())) {
			$this->addMultiCommand("mailselectedusers", $this->lng->txt("send_mail"));
		}
	}


	/**
	 * @param array $row
	 */
	protected function fillRow(/*array*/
		$row)/*: void*/ {
		$this->tpl->setCurrentBlock("column");

		foreach ($this->getSelectableColumns() as $column) {
			if ($this->isColumnSelected($column["id"])) {
				$column = $this->getColumnValue($column["id"], $row);

				if (!empty($column)) {
					$this->tpl->setVariable("COLUMN", $column);
				} else {
					$this->tpl->setVariable("COLUMN", " ");
				}

				$this->tpl->parseCurrentBlock();
			}
		}

		$this->tpl->setCurrentBlock("checkbox");
		$this->tpl->setVariable("CHECKBOX_POST_VAR", 'usr_id');
		$this->tpl->setVariable("ID", $row['usr_id']);
		$this->tpl->parseCurrentBlock();
	}
}

?>