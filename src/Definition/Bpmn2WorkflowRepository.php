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

namespace PHPMentors\Workflower\Definition;

use PHPMentors\Workflower\Workflow\ProcessInstance;
use PHPMentors\Workflower\Workflow\WorkflowRepositoryInterface;

class Bpmn2WorkflowRepository implements WorkflowRepositoryInterface
{
    /**
     * @var array
     */
    private $bpmn2Files = [];

    /**
     * {@inheritdoc}
     */
    public function add($processInstance): void
    {
        assert($processInstance instanceof Bpmn2File);

        $this->bpmn2Files[$processInstance->getId()] = $processInstance;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($processInstance): void
    {
        assert($processInstance instanceof Bpmn2File);

        if (array_key_exists($processInstance->getId(), $this->bpmn2Files)) {
            unset($this->bpmn2Files[$processInstance->getId()]);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return ProcessInstance
     */
    public function findById($id): ?ProcessInstance
    {
        if (!array_key_exists($id, $this->bpmn2Files)) {
            return null;
        }

        $bpmn2Reader = new Bpmn2Reader();

        return $bpmn2Reader->read($this->bpmn2Files[$id]->getFile());
    }
}
