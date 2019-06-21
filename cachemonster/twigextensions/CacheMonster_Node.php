<?php
namespace Craft;

/**
 * CacheMonster twig node.
 *
 * Forked from `Cache_Node` at 2.6.2784
 *
 * @package   CacheMonster
 * @author    Josh Angell
 * @copyright Copyright (c) 2016, Supercool Ltd
 * @link      http://plugins.supercooldesign.co.uk
 */
class CacheMonster_Node extends \Twig_Node
{
	// Properties
	// =========================================================================

	/**
	 * @var int
	 */
	private static $_cacheCount = 1;

	// Public Methods
	// =========================================================================

	/**
	 * @param \Twig_Compiler $compiler
	 *
	 * @return null
	 */
	public function compile(\Twig_Compiler $compiler)
	{
		$n = static::$_cacheCount++;

		$conditions = $this->getNode('conditions');
		$ignoreConditions = $this->getNode('ignoreConditions');
		$key = $this->getNode('key');
		$durationNum = $this->getAttribute('durationNum');
		$durationUnit = $this->getAttribute('durationUnit');
		$expiration = $this->getNode('expiration');
		$global = $this->getAttribute('global') ? 'true' : 'false';

		$compiler
			->addDebugInfo($this)
			->write("\$cacheMonsterService = \Craft\craft()->cacheMonster_templateCache;\n")
			->write("\$ignoreCacheMonster{$n} = (\Craft\craft()->request->isLivePreview() || \Craft\craft()->request->getToken()");

		if ($conditions)
		{
			$compiler
				->raw(' || !(')
				->subcompile($conditions)
				->raw(')');
		}
		else if ($ignoreConditions)
		{
			$compiler
				->raw(' || (')
				->subcompile($ignoreConditions)
				->raw(')');
		}

		$compiler
			->raw(");\n")
			->write("if (!\$ignoreCacheMonster{$n}) {\n")
			->indent()
				->write("\$cacheKey{$n} = ");

		if ($key)
		{
			$compiler->subcompile($key);
		}
		else
		{
			$compiler->raw('"'.StringHelper::randomString().'"');
		}

		$compiler
				->raw(";\n")
				->write("\$cacheBody{$n} = \$cacheMonsterService->getTemplateCache(\$cacheKey{$n}, {$global});\n")
			->outdent()
			->write("} else {\n")
			->indent()
				->write("\$cacheBody{$n} = null;\n")
			->outdent()
			->write("}\n")
			->write("if (\$cacheBody{$n} === null) {\n")
			->indent()
				->write("if (!\$ignoreCacheMonster{$n}) {\n")
				->indent()
					->write("\$cacheMonsterService->startTemplateCache(\$cacheKey{$n});\n")
				->outdent()
				->write("}\n")
				->write("ob_start();\n")
				->subcompile($this->getNode('body'))
				->write("\$cacheBody{$n} = ob_get_clean();\n")
				->write("if (!\$ignoreCacheMonster{$n}) {\n")
				->indent()
					->write("\$cacheMonsterService->endTemplateCache(\$cacheKey{$n}, {$global}, ");

		if ($durationNum)
		{
			// So silly that PHP doesn't support "+1 week" http://www.php.net/manual/en/datetime.formats.relative.php

			if ($durationUnit == 'week')
			{
				if ($durationNum == 1)
				{
					$durationNum = 7;
					$durationUnit = 'days';
				}
				else
				{
					$durationUnit = 'weeks';
				}
			}

			$compiler->raw("'+{$durationNum} {$durationUnit}'");
		}
		else
		{
			$compiler->raw('null');
		}

		$compiler->raw(', ');

		if ($expiration)
		{
			$compiler->subcompile($expiration);
		}
		else
		{
			$compiler->raw('null');
		}

		$compiler
					->raw(", \$cacheBody{$n});\n")
				->outdent()
				->write("}\n")
			->outdent()
			->write("}\n")
			->write("echo \$cacheBody{$n};\n");
	}
}
