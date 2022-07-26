<?php

namespace srag\Plugins\SrLpReport\Comment\Ctrl;

use ilUIPluginRouterGUI;

/**
 * Class CourseCtrl
 *
 * @package           srag\Plugins\SrLpReport\Comment\Ctrl
 *
 * @author            studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 *
 * @ilCtrl_isCalledBy srag\Plugins\SrLpReport\Comment\Ctrl\CourseCtrl: ilUIPluginRouterGUI
 */
class CourseCtrl extends AbstractCtrl {

	/**
	 * @inheritdoc
	 */
	public function getAsyncClass(): array {
		return [
			ilUIPluginRouterGUI::class,
			self::class
		];
	}


	/**
	 * @inheritdoc
	 */
	public function getCommentsArray(int $report_obj_id, int $report_user_id): array {
		return self::comments(self::COMMENTS_CLASS_NAME)->getCommentsForCurrentUser($report_obj_id);
	}


	/**
	 * @inheritdoc
	 */
	public function getIsReadOnly(): bool {
		return true;
	}


	/**
	 * @inheritdoc
	 */
	public function createComment()/*: void*/ {

	}


	/**
	 * @inheritdoc
	 */
	public function updateComment()/*: void*/ {

	}


	/**
	 * @inheritdoc
	 */
	public function deleteComment()/*: void*/ {

	}


	/**
	 * @inheritdoc
	 */
	public function shareComment()/*: void*/ {

	}
}
