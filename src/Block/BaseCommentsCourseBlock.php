<?php

namespace srag\Plugins\SrLpReport\Block;

use ilBlockGUI;
use ilSrLpReportPlugin;
use srag\CommentsUI\SrLpReport\Utils\CommentsUITrait;
use srag\DIC\SrLpReport\DICTrait;
use srag\Plugins\SrLpReport\Comment\Ctrl\CourseCtrl;
use srag\Plugins\SrLpReport\Utils\SrLpReportTrait;

/**
 * Class BaseCommentsCourseBlock
 *
 * @package srag\Plugins\SrLpReport\Block
 *
 * @author  studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 */
abstract class BaseCommentsCourseBlock extends ilBlockGUI {

	use DICTrait;
	use SrLpReportTrait;
	use CommentsUITrait;
	const PLUGIN_CLASS_NAME = ilSrLpReportPlugin::class;


	/**
	 * BaseCommentsCourseBlock constructor
	 */
	public function __construct() {
		parent::__construct();

		$this->initBlock();
	}


	/**
	 *
	 */
	protected function initBlock()/*: void*/ {
		$this->setTitle(self::dic()->language()->txt("trac_learning_progress") . " " . self::dic()->language()->txt("notes_comments"));
	}


	/**
	 *
	 */
	public function fillDataSection()/*: void*/ {
		$this->setDataSection(self::output()->getHTML(self::commentsUI()->withPlugin(self::plugin())->withCtrlClass(new CourseCtrl())));
	}
}
