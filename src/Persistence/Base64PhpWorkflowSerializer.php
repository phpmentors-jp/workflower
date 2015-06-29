<?php
/*
 * Copyright (c) 2015 KUBO Atsuhiro <kubo@iteman.jp>,
 * All rights reserved.
 *
 * This file is part of Workflower.
 *
 * This program and the accompanying materials are made available under
 * the terms of the BSD 2-Clause License which accompanies this
 * distribution, and is available at http://opensource.org/licenses/BSD-2-Clause
 */

namespace PHPMentors\Workflower\Persistence;

use PHPMentors\Workflower\Workflow\Workflow;

class Base64PhpWorkflowSerializer extends PhpWorkflowSerializer
{
    /**
     * {@inheritDoc}
     */
    public function serialize(Workflow $workflow)
    {
        return base64_encode(parent::serialize($workflow));
    }

    /**
     * {@inheritDoc}
     */
    public function deserialize($workflow)
    {
        return parent::deserialize(base64_decode($workflow));
    }
}
