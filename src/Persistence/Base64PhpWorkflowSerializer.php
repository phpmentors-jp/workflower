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

class Base64PhpWorkflowSerializer extends PhpWorkflowSerializer
{
    /**
     * {@inheritdoc}
     */
    public function serialize(ProcessInstance $processInstance)
    {
        return base64_encode(parent::serialize($processInstance));
    }

    /**
     * {@inheritdoc}
     */
    public function deserialize($processInstance)
    {
        return parent::deserialize(base64_decode($processInstance));
    }
}
