<?php

namespace srag\CustomInputGUIs\SrCrsLpReport;

use srag\CustomInputGUIs\SrCrsLpReport\ProgressMeter\Implementation\Factory as ProgressMeterFactory;
use srag\DIC\SrCrsLpReport\DICTrait;

/**
 * Class CustomInputGUIs
 *
 * @package srag\CustomInputGUIs\SrCrsLpReport
 *
 * @author  studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 *
 * @internal
 */
final class CustomInputGUIs {

	use DICTrait;
	/**
	 * @var self
	 */
	protected static $instance = NULL;


	/**
	 * @return self
	 */
	public static function getInstance(): self {
		if (self::$instance === NULL) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * @return ProgressMeterFactory
	 */
	public function progressMeter() {
		return new ProgressMeterFactory();
	}
}