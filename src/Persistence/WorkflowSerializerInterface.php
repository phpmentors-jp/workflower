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

use PHPMentors\Workflower\Workflow\ProcessInstance;

interface WorkflowSerializerInterface
{
    /**
     * @param ProcessInstance $workflow
     *
     * @return string
     */
    public function serialize(ProcessInstance $workflow);

    /**
     * @param string $workflow
     *
     * @return ProcessInstance
     */
    public function deserialize($workflow);
}
