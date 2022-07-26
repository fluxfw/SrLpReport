<?php

namespace srag\CommentsUI\SrLpReport\Comment;

use ilObjUser;
use srag\DIC\SrLpReport\DICTrait;
use stdClass;

/**
 * Class Repository
 *
 * @package srag\CommentsUI\SrLpReport\Comment
 *
 * @author  studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 */
final class Repository {

	use DICTrait;
	const EDIT_LIMIT_MINUTES = 5;
	/**
	 * @var self[]
	 */
	protected static $instances = [];


	/**
	 * @param string $comment_class
	 *
	 * @return self
	 */
	public static function getInstance(string $comment_class): self {
		if (!isset(self::$instances[$comment_class])) {
			self::$instances[$comment_class] = new self($comment_class);
		}

		return self::$instances[$comment_class];
	}


	/**
	 * @var string|AbstractComment
	 */
	protected $comment_class;
	/**
	 * @var bool
	 */
	protected $output_object_titles = false;


	/**
	 * Repository constructor
	 *
	 * @param string $comment_class
	 */
	private function __construct(string $comment_class) {
		$this->comment_class = $comment_class;
	}


	/**
	 * @param AbstractComment $comment
	 *
	 * @return bool
	 */
	public function canBeDeleted(AbstractComment $comment): bool {
		if (empty($comment->getId())) {
			return false;
		}

		if ($comment->isShared() || $comment->isDeleted()) {
			return false;
		}

		if ($comment->getCreatedUserId() !== intval(self::dic()->user()->getId())) {
			return false;
		}

		return true;
	}


	/**
	 * @param AbstractComment $comment
	 *
	 * @return bool
	 */
	public function canBeShared(AbstractComment $comment): bool {
		if (empty($comment->getId())) {
			return false;
		}

		if ($comment->isShared() || $comment->isDeleted()) {
			return false;
		}

		if ($comment->getCreatedUserId() !== intval(self::dic()->user()->getId())) {
			return false;
		}

		return true;
	}


	/**
	 * @param AbstractComment $comment
	 *
	 * @return bool
	 */
	public function canBeStored(AbstractComment $comment): bool {
		if (empty($comment->getId())) {
			return true;
		}

		if ($comment->isShared() || $comment->isDeleted()) {
			return false;
		}

		if ($comment->getCreatedUserId() !== intval(self::dic()->user()->getId())) {
			return false;
		}

		$time = time();

		return (($time - $comment->getCreatedTimestamp()) <= (self::EDIT_LIMIT_MINUTES * 60));
	}


	/**
	 * @param AbstractComment $comment
	 */
	public function deleteComment(AbstractComment $comment)/*: void*/ {
		if (!$this->canBeDeleted($comment)) {
			return;
		}

		$comment->setDeleted(true);

		$comment->store();
	}


	/**
	 * @return Factory
	 */
	public function factory(): Factory {
		return Factory::getInstance($this->comment_class);
	}


	/**
	 * @param int $id
	 *
	 * @return AbstractComment|null
	 */
	public function getCommentById(int $id)/*: ?Comment*/ {
		/**
		 * @var AbstractComment|null $comment
		 */

		$comment = $this->comment_class::where([ "id" => $id ])->first();

		return $comment;
	}


	/**
	 * @param int $report_obj_id
	 * @param int $report_user_id
	 *
	 * @return AbstractComment[]
	 */
	public function getCommentsForReport(int $report_obj_id, int $report_user_id): array {
		/**
		 * @var AbstractComment[] $comments
		 */

		$comments = array_values($this->comment_class::where([
			"deleted" => false,
			"report_obj_id" => $report_obj_id,
			"report_user_id" => $report_user_id
		])->orderBy("updated_timestamp", "desc")->get());

		return $comments;
	}


	/**
	 * @param int|null $report_obj_id
	 *
	 * @return AbstractComment[]
	 */
	public function getCommentsForCurrentUser(/*?int*/
		$report_obj_id = null): array {
		/**
		 * @var AbstractComment[] $comments
		 */

		$where = [
			"deleted" => false,
			"report_user_id" => self::dic()->user()->getId(),
			"is_shared" => true
		];

		if (!empty($report_obj_id)) {
			$where["report_obj_id"] = $report_obj_id;
		}

		$comments = array_values($this->comment_class::where($where)->orderBy("updated_timestamp", "desc")->get());

		return $comments;
	}


	/**
	 * @param AbstractComment $comment
	 */
	public function shareComment(AbstractComment $comment)/*: void*/ {
		if (!$this->canBeShared($comment)) {
			return;
		}

		$comment->setIsShared(true);

		$comment->store();
	}


	/**
	 * @param AbstractComment $comment
	 */
	public function storeComment(AbstractComment $comment)/*: void*/ {
		if (!$this->canBeStored($comment)) {
			return;
		}

		$time = time();

		if (empty($comment->getId())) {
			$comment->setCreatedTimestamp($time);
			$comment->setCreatedUserId(self::dic()->user()->getId());
		}

		$comment->setUpdatedTimestamp($time);
		$comment->setUpdatedUserId(self::dic()->user()->getId());

		$comment->store();
	}


	/**
	 * @param AbstractComment $comment
	 *
	 * @return stdClass
	 */
	public function toJson(AbstractComment $comment): stdClass {
		$content = $comment->getComment();

		if ($this->output_object_titles) {
			$content = self::dic()->objDataCache()->lookupTitle($comment->getReportObjId()) . "\n" . $content;
		}

		return (object)[
			"content" => $content,
			"created" => date("Y-m-d H:i:s", $comment->getCreatedTimestamp()),
			"created_by_current_user" => $this->canBeStored($comment),
			"deletable" => $this->canBeDeleted($comment),
			"fullname" => self::dic()->objDataCache()->lookupTitle($comment->getCreatedUserId()),
			"id" => $comment->getId(),
			"modified" => date("Y-m-d H:i:s", $comment->getUpdatedTimestamp()),
			"profile_picture_url" => (new ilObjUser($comment->getCreatedUserId()))->getPersonalPicturePath("big"),
			"shareable" => $this->canBeShared($comment)
		];
	}


	/**
	 * @param bool $output_object_titles
	 *
	 * @return self
	 */
	public function withOutputObjectTitles(bool $output_object_titles = false): self {
		$this->output_object_titles = $output_object_titles;

		return $this;
	}
}
