<?php
namespace Craft;

/**
 * Class CacheMonsterTwigExtension
 *
 * @package   CacheMonster
 * @author    Josh Angell
 * @copyright Copyright (c) 2016, Supercool Ltd
 * @link      http://plugins.supercooldesign.co.uk
 */

use Twig_Extension;

class CacheMonsterTwigExtension extends \Twig_Extension
{

	// Public Methods
	// =========================================================================


	// public function initRuntime(\Twig_Environment $environment)
	// {
	// 	$environment->setBaseTemplateClass('Craft\CacheMonster_BaseTemplate');
	// }

	/**
	 * Returns the name of the extension.
	 *
	 * @return string The extension name
	 */
	public function getName()
	{
		return 'cachemonster';
	}

	/**
	 * Returns the token parser instances to add to the existing list.
	 *
	 * @return array An array of Twig_TokenParserInterface or Twig_TokenParserBrokerInterface instances
	 */
	public function getTokenParsers()
	{
		return array(
			new CacheMonster_TokenParser(),
		);
	}

}
