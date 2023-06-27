<?php

namespace Drupal\conil_platform_apis\Controller;

use Drupal\Core\Controller\ControllerBase;
/**
 * Class ControllerBase.
 *
 * @package Drupal\conil_platform_apis\Controller
 */
class SensorsController extends ControllerBase
{

	function intro()
	{
		return [
			'#title' => 'Sensors information API',
			'#markup' => 'Sensors information API',
			];
	}
}
