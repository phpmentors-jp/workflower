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

class PhpWorkflowSerializer implements WorkflowSerializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function serialize(ProcessInstance $processInstance)
    {
        return serialize($processInstance);
    }

    /**
     * {@inheritdoc}
     */
    public function deserialize($processInstance)
    {
        return unserialize($processInstance);
    }
}
