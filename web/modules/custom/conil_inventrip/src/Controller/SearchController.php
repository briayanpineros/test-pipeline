<?php

namespace Drupal\conil_inventrip\Controller;

use Drupal\Core\Controller\ControllerBase;
/**
 * Class ControllerBase.
 *
 * @package Drupal\conil_inventrip\Controller
 */
class SearchController extends ControllerBase
{

	function intro()
	{
		return [
			'#title' => 'Search POIS from a date API',
			'#markup' => 'Search POIS from a date API',
			];
	}
}
