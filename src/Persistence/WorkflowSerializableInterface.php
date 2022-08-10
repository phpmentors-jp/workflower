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

namespace PHPMentors\Workflower\Persistence;

use PHPMentors\Workflower\Process\WorkflowAwareInterface;

interface WorkflowSerializableInterface extends OperationInterface, WorkflowAwareInterface
{
    /**
     * @param  string  $serializedWorkflow
     */
    public function setSerializedWorkflow($serializedWorkflow);

    /**
     * @return string
     */
    public function getSerializedWorkflow();

    /**
     * @return Workflow
     */
    public function getWorkflow();
}
