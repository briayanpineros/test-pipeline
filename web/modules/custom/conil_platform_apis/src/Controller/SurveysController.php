<?php

namespace Drupal\conil_platform_apis\Controller;

use Drupal\Core\Controller\ControllerBase;
/**
 * Class ControllerBase.
 *
 * @package Drupal\conil_platform_apis\Controller
 */
class SurveysController extends ControllerBase
{

	function intro()
	{
		return [
			'#title' => 'Surveys from our site',
			'#markup' => 'Surveys from our site',
			];
	}
}
