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

namespace PHPMentors\Workflower\Persistence;

use PHPMentors\Workflower\Process\WorkflowAwareInterface;
use PHPMentors\Workflower\Workflow\ProcessInstance;

interface WorkflowSerializableInterface extends WorkflowAwareInterface
{
    /**
     * @param string|null $serializedWorkflow
     */
    public function setSerializedWorkflow($serializedWorkflow);

    /**
     * @return string|null
     */
    public function getSerializedWorkflow();

    /**
     * @return ProcessInstance
     */
    public function getProcessInstance();
}
