<?php
/*
 * Copyright (c) Atsuhiro Kubo <kubo@iteman.jp> and contributors,
 * All rights reserved.
 *
 * This file is part of Workflower.
 *
 * This program and the accompanying materials are made available under
 * the terms of the BSD 2-Clause License which accompanies this
 * distribution, and is available at http://opensource.org/licenses/BSD-2-Clause
 */

namespace PHPMentors\Workflower\Workflow;

use PHPMentors\Workflower\Workflow\Activity\ActivityInterface;
use PHPMentors\Workflower\Workflow\Activity\WorkItemInterface;

class ActivityLog
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
     * @param WorkItemInterface $workItem
     */
    public function __construct(WorkItemInterface $workItem)
    {
        $this->workItem = $workItem;
        $this->activity = $workItem->getActivity();
    }

    /**
     * @return ActivityInterface
     */
    public function getActivity()
    {
        return $this->activity;
    }

    /**
     * @return WorkItemInterface
     */
    public function getWorkItem()
    {
        return $this->workItem;
    }
}
