<?php

namespace srag\CustomInputGUIs\SrLpReport\LearningProgressPie;

/**
 * Class CountLearningProgressPie
 *
 * @package srag\CustomInputGUIs\SrLpReport\LearningProgressPie
 *
 * @author  studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 */
class CountLearningProgressPie extends AbstractLearningProgressPie {

	/**
	 * @var int[]
	 */
	protected $count = [];


	/**
	 * @param array $count
	 *
	 * @return self
	 */
	public function withCount(array $count): self {
		$this->count = $count;

		return $this;
	}


	/**
	 * @inheritdoc
	 */
	protected function parseData(): array {
		if (count($this->count) > 0) {
			return $this->count;
		} else {
			return [];
		}
	}


	/**
	 * @inheritdoc
	 */
	protected function getCount(): int {
		return count($this->count);
	}
}
