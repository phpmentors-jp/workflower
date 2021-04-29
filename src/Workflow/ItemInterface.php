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

/**
 * @since Interface available since Release 2.0.0
 */
interface ItemInterface
{
    /**
     * @return int|string
     */
    public function getId();

    /**
     * @param ProcessInstanceInterface $processInstance
     *
     * @return void
     */
    public function setProcessInstance(ProcessInstanceInterface $processInstance);

    /**
     * @return ProcessInstanceInterface|null
     */
    public function getProcessInstance();

    /**
     * @param ActivityInterface $activity
     */
    public function setActivity(ActivityInterface $activity);

    /**
     * @return ActivityInterface
     */
    public function getActivity();

    /**
     * @return string
     */
    public function getState();

    /**
     * @return void
     */
    public function cancel();
}
