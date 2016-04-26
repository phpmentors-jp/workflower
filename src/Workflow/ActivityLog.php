<?php
/*
 * Copyright (c) KUBO Atsuhiro <kubo@iteman.jp> and contributors,
 * All rights reserved.
 *
 * This file is part of Workflower.
 *
 * This program and the accompanying materials are made available under
 * the terms of the BSD 2-Clause License which accompanies this
 * distribution, and is available at http://opensource.org/licenses/BSD-2-Clause
 */

namespace PHPMentors\Workflower\Workflow;

use PHPMentors\DomainKata\Entity\EntityInterface;
use PHPMentors\Workflower\Workflow\Activity\ActivityInterface;
use PHPMentors\Workflower\Workflow\Activity\WorkItemInterface;

class ActivityLog implements EntityInterface
{
    /**
     * @var ActivityInterface
     */
    private $activity;

    /**
     * @var WorkItemInterface
     */
    private $workItem;

    /**
     * @param ActivityInterface $activity
     */
    public function __construct(ActivityInterface $activity)
    {
        $this->activity = $activity;
    }

    /**
     * @return ActivityInterface
     */
    public function getActivity()
    {
        return $this->activity;
    }

    /**
     * @param WorkItemInterface $workItem
     */
    public function setWorkItem(WorkItemInterface $workItem)
    {
        $this->workItem = $workItem;
    }

    /**
     * @return WorkItemInterface
     */
    public function getWorkItem()
    {
        return $this->workItem;
    }
}
