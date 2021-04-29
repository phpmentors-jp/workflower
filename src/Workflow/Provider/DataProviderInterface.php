<?php


namespace PHPMentors\Workflower\Workflow\Provider;


use PHPMentors\Workflower\Workflow\Activity\ActivityInterface;

/**
 * @since Interface available since Release 2.0.0
 */
interface DataProviderInterface
{

    /**
     * @param ActivityInterface $activity
     * @return array
     */
    public function getParallelInstancesData(ActivityInterface $activity);

    /**
     * @param ActivityInterface $activity
     * @return array
     */
    public function getSequentialInstanceData(ActivityInterface $activity);

    /**
     * @param ActivityInterface $activity
     * @return array
     */
    public function getSingleInstanceData(ActivityInterface $activity);

    /**
     * @param ActivityInterface $activity
     * @return void
     */
    public function mergeInstancesData(ActivityInterface $activity);
}