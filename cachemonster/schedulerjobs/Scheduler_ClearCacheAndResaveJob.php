<?php
namespace Craft;

/**
 * ClearCacheAndResaveJob
 *
 * This Job will clear cachemonster caches, then resave the element. allowing us to bypass queue times
 *
 *
 * @package   Cache Monster
 * @author    Nick Thompson
 * @copyright Copyright (c) 2017, Supercool Ltd
 * @link      https://github.com/supercool/Cache-Monster
 */

class Scheduler_ClearCacheAndResaveJob extends BaseScheduler_Job
{

	// Properties
	// =========================================================================

	/**
	 * Set this to true to allow the Job to be used in the ScheduleJob Field Type
	 *
	 * @var bool
	 */
	protected $allowedInFieldType = true;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc IScheduler_Job::run()
	 *
	 * @return bool
	 */
	public function run()
	{

		// Get the model
		$job = $this->model;

		// Get the elementId from the model settings
		$elementId = $job->settings['elementId'];

		try
		{
			// Get the element model
			$element = craft()->elements->getElementById($elementId);

			// Check there was one
			if (!$element) {
				return false;
			}

			$path = isset($element->uri) ? $element->uri : false;

			if($path) {

				// Delete the cache from the cache monster table
				craft()->db->createCommand()->delete('cachemonster_templatecaches', ['in', 'path', [$path, 'cp:' . $path, 'site:' . $path]]);

				// Purge external caches
				$externalCacheService = craft()->config->get('externalCachingService', 'cacheMonster');

				if($externalCacheService) {
					$externalCacheService = 'cacheMonster_external' . $externalCacheService;
					craft()->$externalCacheService->purgePaths([$path]);
				}

			}


			/**
			 * ALL BELOW TAKEN FROM SCHEDULER
			 */

			// Re-save the element using the Element Types save method
			// Now save it
			$elementType = craft()->elements->getElementType($element->elementType);

			if ( $element instanceof SuperCal_StatusScheduleModel )
			{
				craft()->elements->saveElement($element, false);

				return true;
			}
			elseif ($elementType->saveElement($element, false))
			{

				// Check if the element has an owner (MatrixBlock, SuperTable_Block)
				// and if so, then save that too
				if ($element instanceof MatrixBlockModel || $element instanceof SuperTable_BlockModel)
				{
					$owner = $element->getOwner();
					if ($owner)
					{
						craft()->elements->saveElement($owner, false);
					}
				}

				// Do the same for Commerce Variants
				if ($element instanceof Commerce_VariantModel)
				{
					$product = $element->getProduct();
					if ($product)
					{
						craft()->elements->saveElement($product, false);
					}
				}

				return true;
			}
			else
			{
				return false;
			}
		}
		catch (\Exception $e)
		{
			SchedulerPlugin::log(Craft::t('An exception was thrown while trying to save the element with the ID “'.$elementId.'”: '.$e->getMessage()), LogLevel::Error);
			return false;
		}

		return false;
	}

}
