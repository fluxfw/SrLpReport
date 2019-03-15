<?php

namespace srag\Plugins\SrLpReport\Staff\User;

use ilAdvancedSelectionListGUI;
use ilLearningProgressBaseGUI;
use ilLPStatus;
use ilMStListCourse;
use ilPublicUserProfileGUI;
use ilSelectInputGUI;
use ilTextInputGUI;
use srag\CustomInputGUIs\SrLpReport\PropertyFormGUI\PropertyFormGUI;
use srag\Plugins\SrLpReport\Staff\AbstractStaffTableGUI;

/**
 * Class UserTableGUI
 *
 * @package srag\Plugins\SrLpReport\Staff\User
 *
 * @author  studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 */
class UserTableGUI extends AbstractStaffTableGUI {

	/**
	 * @inheritdoc
	 */
	protected function getColumnValue(/*string*/
		$column, /*array*/
		$row, /*bool*/
		$raw_export = false): string {
		switch ($column) {
			case "usr_reg_status":
				$column = ilMStListCourse::getMembershipStatusText($row[$column]);
				break;

			case "usr_lp_status":
				$column = ilLearningProgressBaseGUI::_getStatusText($row[$column]);
				break;

			case "learning_progress_courses":
				if (!$raw_export) {
					$column = self::output()->getHTML(self::customInputGUIs()->learningProgressPie()->objIds()->withObjIds($row[$column])
						->withUsrId($row["usr_id"])->withId($row["crs_ref_id"]));
				} else {
					$column = "";
				}
				break;

			default:
				$column = $row[$column];
				break;
		}

		return strval($column);
	}


	/**
	 * @inheritdoc
	 */
	public function getSelectableColumns2(): array {
		$columns = [
			"crs_title" => [
				"default" => true,
				"txt" => self::dic()->language()->txt("title")
			],
			"usr_reg_status" => [
				"default" => true,
				"txt" => self::dic()->language()->txt("member_status")
			],
			"usr_lp_status" => [
				"default" => true,
				"txt" => self::dic()->language()->txt("trac_learning_progress")
			],
			"learning_progress_courses" => [
				"default" => true,
				"txt" => self::dic()->language()->txt("trac_learning_progress") . " " . self::dic()->language()->txt("courses")
			]
		];

		$no_sort = [
			"learning_progress_courses"
		];

		foreach ($columns as $id => &$column) {
			$column["id"] = $id;
			$column["default"] = ($column["default"] === true);
			$column["sort"] = (!in_array($id, $no_sort));
		}

		return $columns;
	}


	/**
	 * @inheritdoc
	 */
	protected function initColumns()/*: void*/ {
		parent::initColumns();

		$this->addColumn(self::dic()->language()->txt("actions"));
	}


	/**
	 * @inheritdoc
	 */
	protected function initData()/*: void*/ {
		$this->setExternalSorting(true);
		$this->setExternalSegmentation(true);

		$this->setDefaultOrderField("crs_title");
		$this->setDefaultOrderDirection("asc");

		$this->determineLimit();
		$this->determineOffsetAndOrder();

		$data = self::ilias()->staff()->user()->getData(self::reports()
			->getUsrId(), $this->getFilterValues(), $this->getOrderField(), $this->getOrderDirection(), $this->getOffset(), $this->getLimit());

		$this->setMaxCount($data["max_count"]);
		$this->setData($data["data"]);
	}


	/**
	 * @inheritdoc
	 */
	protected function initFilterFields()/*: void*/ {
		$this->filter_fields = [
			"crs_title" => [
				PropertyFormGUI::PROPERTY_CLASS => ilTextInputGUI::class,
				"setTitle" => $this->dic()->language()->txt("title")
			],
			"usr_reg_status" => [
				PropertyFormGUI::PROPERTY_CLASS => ilSelectInputGUI::class,
				PropertyFormGUI::PROPERTY_OPTIONS => [
					0 => self::dic()->language()->txt("trac_all"),
					ilMStListCourse::MEMBERSHIP_STATUS_REQUESTED => self::dic()->language()->txt("mst_memb_status_requested"),
					ilMStListCourse::MEMBERSHIP_STATUS_WAITINGLIST => self::dic()->language()->txt("mst_memb_status_waitinglist"),
					ilMStListCourse::MEMBERSHIP_STATUS_REGISTERED => self::dic()->language()->txt("mst_memb_status_registered")
				],
				"setTitle" => self::dic()->language()->txt("member_status")
			],
			"usr_lp_status" => [
				PropertyFormGUI::PROPERTY_CLASS => ilSelectInputGUI::class,
				PropertyFormGUI::PROPERTY_OPTIONS => [
					0 => self::dic()->language()->txt("trac_all"),
					ilLPStatus::LP_STATUS_NOT_ATTEMPTED_NUM + 1 => self::dic()->language()->txt(ilLPStatus::LP_STATUS_NOT_ATTEMPTED),
					ilLPStatus::LP_STATUS_IN_PROGRESS_NUM + 1 => self::dic()->language()->txt(ilLPStatus::LP_STATUS_IN_PROGRESS),
					ilLPStatus::LP_STATUS_COMPLETED_NUM + 1 => self::dic()->language()->txt(ilLPStatus::LP_STATUS_COMPLETED)
					//ilLPStatus::LP_STATUS_FAILED_NUM + 1 => self::dic()->language()->txt(ilLPStatus::LP_STATUS_FAILED)
				],
				"setTitle" => self::dic()->language()->txt("trac_learning_progress")
			]
		];
	}


	/**
	 * @inheritdoc
	 */
	protected function initId()/*: void*/ {
		$this->setId("srcrslp_staff_user");
	}


	/**
	 * @inheritdoc
	 */
	protected function initTitle()/*: void*/ {
		$this->setTitle(self::dic()->language()->txt("user") . " " . self::dic()->objDataCache()->lookupTitle(self::reports()->getUsrId()));
	}


	/**
	 * @inheritdoc
	 */
	protected function extendsActionsMenu(ilAdvancedSelectionListGUI $actions, array $row)/*: void*/ {

	}


	/**
	 * @return string
	 */
	public function getHTML(): string {
		return parent::getHTML() . (new ilPublicUserProfileGUI(self::reports()->getUsrId()))->getEmbeddable();
	}
}
