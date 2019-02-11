<?php

namespace srag\Plugins\SrLpReport\Summary;

use ilLearningProgressBaseGUI;
use ilLPObjSettings;
use ilLPStatus;
use ilObject;
use ilObjectLP;
use ilSrLpReportPlugin;
use ilTemplateException;
use ilTrQuery;
use ilUtil;
use srag\CustomInputGUIs\SrLpReport\TableGUI\TableGUI;
use srag\DIC\SrLPReport\Exception\DICException;

/**
 * Class SummaryTableGUI
 *
 * @package srag\Plugins\SrLpReport\Summary
 *
 * @author  studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 */
class SummaryTableGUI extends TableGUI {

	const PLUGIN_CLASS_NAME = ilSrLpReportPlugin::class;
	const LP_STATUS_COLOR = [
		ilLPStatus::LP_STATUS_NOT_ATTEMPTED_NUM => "#ddd",
		ilLPStatus::LP_STATUS_IN_PROGRESS_NUM => "#F6D842",
		ilLPStatus::LP_STATUS_COMPLETED_NUM => "#60B060",
		ilLPStatus::LP_STATUS_FAILED => "#B06060"
	];


	/**
	 * SummaryTableGUI constructor
	 *
	 * @param object $parent
	 * @param string $parent_cmd
	 */
	public function __construct($parent, /*string*/
		$parent_cmd) {

		$this->ref_id = $_GET['ref_id'];
		$this->obj_id = ilObject::_lookupObjectId($_GET['ref_id']);
		$this->user_fields = [];

		$this->setShowRowsSelector(false);

		parent::__construct($parent, /*string*/
			$parent_cmd);
	}


	/**
	 * @inheritdoc
	 */
	protected function getColumnValue($column, /*array*/
		$row, /*bool*/
		$raw_export = false): string {
		switch ($column) {
			case "status":
				return strval($this->getLearningProgressRepresentation($row[$column], $row['obj_id'], $row["user_total"]));
				break;
			default:
				return strval(is_array($row[$column]) ? implode(", ", $row[$column]) : $row[$column]);
				break;
		}
	}


	/**
	 * @inheritdoc
	 */
	protected function getSelectableColumns2(): array {
		$cols = [];

		// default fields
		$cols["title"] = array(
			"id" => "title",
			"sort" => "title",
			"txt" => self::dic()->language()->txt("title"),
			"default" => true,
		);

		// default fields
		$cols["status"] = array(
			"id" => "status",
			"sort" => "status",
			"txt" => self::dic()->language()->txt("status"),
			"default" => true,
		);

		return $cols;
	}


	/**
	 * @inheritdoc
	 */
	protected function initData()/*: void*/ {
		$olp = ilObjectLP::getInstance(ilObject::_lookupObjId($this->ref_id));
		if ($olp->getCurrentMode() == ilLPObjSettings::LP_MODE_COLLECTION_MANUAL
			|| $olp->getCurrentMode() == ilLPObjSettings::LP_MODE_COLLECTION
			|| $olp->getCurrentMode() == ilLPObjSettings::LP_MODE_MANUAL_BY_TUTOR) {
			$collection = $olp->getCollectionInstance();
			$preselected_obj_ids[$this->obj_id][] = $this->ref_id;
			foreach ($collection->getItems() as $item => $item_info) {
				$tmp_lp = ilObjectLP::getInstance(ilObject::_lookupObjId($item_info));
				if ($tmp_lp->isActive()) {
					$preselected_obj_ids[ilObject::_lookupObjId($item_info)][] = $item_info;
				}
			}
			//$filter = $this->getCurrentFilter();
		}

		$data = ilTrQuery::getObjectsSummaryForObject($this->obj_id, $this->ref_id, ilUtil::stripSlashes($this->getOrderField()), ilUtil::stripSlashes($this->getOrderDirection()), ilUtil::stripSlashes($this->getOffset()), ilUtil::stripSlashes($this->getLimit()), [], $this->getSelectedColumns(), $preselected_obj_ids);

		foreach ($data['set'] as $key => $row) {
			$data["set"][$key]["status"] = $this->getLearningProgressJson($row["status"], $row["user_total"]);
		}

		$this->setData($data["set"]);
	}


	/**
	 * @inheritdoc
	 */
	protected function initFilterFields()/*: void*/ {

	}


	/**
	 * @inheritdoc
	 */
	protected function initTitle()/*: void*/ {

	}


	/**
	 * @inheritdoc
	 */
	protected function initId()/*: void*/ {
		$this->setId('srcrslp_summary');
		$this->setPrefix('srcrslp_summary');
	}


	/**
	 * Render status data as needed for summary list (based on grouped values)
	 *
	 * @param    array $status_data status data
	 * @param    int   $absolute    overall number of entries
	 *
	 * @return    string
	 */
	protected function getLearningProgressJson(array $status_data = NULL, $absolute = 0): string {
		self::dic()->language()->loadLanguageModule('trac');
		$json_string = "";

		foreach ($status_data as $status_number => $user_count) {
			$array_status = [];

			if ($status_number === "") {
				$status_data[ilLPStatus::LP_STATUS_NOT_ATTEMPTED_NUM] += 1;
				unset($status_data["status"][$status_number]);
			}

			$perc = round($user_count / $absolute * 100);

			$arr_status['user_count'] = $user_count;
			$arr_status['reached'] = $perc;
			$arr_status['reached_label'] = $perc . "%";
			$arr_status['label'] = ilLearningProgressBaseGUI::_getStatusText($status_number);
			$arr_status['color'] = self::LP_STATUS_COLOR[$status_number];
			$arr_status['absolute'] = $absolute;

			$json_string[] = json_encode($arr_status);
		}

		return implode(',', $json_string);
		//return json_encode($arr_status);
	}


	/**
	 * @param string $json_status
	 * @param int    $row_identifier
	 * @param int    $user_total
	 *
	 * @return string
	 * @throws DICException
	 * @throws ilTemplateException
	 */
	public function getLearningProgressRepresentation(string $json_status = "", int $row_identifier = 0, int $user_total = 0): string {
		$tpl_learning_progress_chart = self::plugin()->template("LearningProgress/chart.html", false, false);

		$tpl_learning_progress_chart->setVariable("ROW_IDENTIFIER", $row_identifier);
		$tpl_learning_progress_chart->setVariable("TOTAL", $user_total);
		$tpl_learning_progress_chart->setVariable("JSON_STATUS", $json_status);

		return self::output()->getHTML($tpl_learning_progress_chart);
	}
}
