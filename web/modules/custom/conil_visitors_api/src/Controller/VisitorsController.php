<?php

namespace Drupal\conil_visitors_api\Controller;

use Drupal\Core\Controller\ControllerBase;
/**
 * Class ControllerBase.
 *
 * @package Drupal\conil_visitors_api\Controller
 */
class VisitorsController extends ControllerBase
{

	function intro()
	{
		return [
			'#title' => ' Count visitors API',
			'#markup' => 'Count visitors API module for custom rest API',
			];
	}
}
