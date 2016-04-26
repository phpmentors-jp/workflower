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

namespace PHPMentors\Workflower\Definition;

use PHPMentors\DomainKata\Entity\EntityInterface;
use PHPMentors\Workflower\Workflow\WorkflowRepositoryInterface;

class Bpmn2WorkflowRepository implements WorkflowRepositoryInterface
{
    /**
     * @var array
     */
    private $bpmn2Files = array();

    /**
     * {@inheritdoc}
     */
    public function add(EntityInterface $entity)
    {
        assert($entity instanceof Bpmn2File);

        $this->bpmn2Files[$entity->getId()] = $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function remove(EntityInterface $entity)
    {
        assert($entity instanceof Bpmn2File);

        if (array_key_exists($entity->getId(), $this->bpmn2Files)) {
            unset($this->bpmn2Files[$entity->getId()]);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return Workflow
     */
    public function findById($id)
    {
        if (!array_key_exists($id, $this->bpmn2Files)) {
            return null;
        }

        $bpmn2Reader = new Bpmn2Reader();

        return $bpmn2Reader->read($this->bpmn2Files[$id]->getFile());
    }
}
