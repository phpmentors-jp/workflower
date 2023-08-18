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

namespace PHPMentors\Workflower\Workflow\Activity;

use PHPMentors\Workflower\Workflow\Provider\DataNotFoundException;
use PHPMentors\Workflower\Workflow\Provider\ProviderNotFoundException;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class Task extends AbstractTask// implements \Serializable
{
    /**
     * {@inheritdoc}
     */
    protected function createWorkItem($data)
    {
        if ($this->isClosed()) {
            throw new UnexpectedActivityStateException(sprintf('The activity "%s" is closed.', $this->getId()));
        }

        $workItem = $this->getProcessInstance()->generateWorkItem($this);
        $workItem->setData($data);
        $this->getWorkItems()->add($workItem);

        return $workItem;
    }

    public function createWork(): void
    {
        $provider = $this->getProcessInstance()->getDataProvider();

        if ($this->isMultiInstance()) {
            // If not sequential then create parallel work items.
            // If it's sequential then create a work item and when it's completed create
            // the next one if required.
            // After completing each work item check "completionCondition" to see if we
            // cancel all remaining work items.

            if (!$provider) {
                throw new ProviderNotFoundException();
            }

            if ($this->isSequential()) {
                $this->createWorkItem($provider->getSequentialInstanceData($this));
            } else {
                // calculate how many parallel instances we have to create
                $parallelData = $provider->getParallelInstancesData($this);

                if (count($parallelData) === 0) {
                    throw new DataNotFoundException();
                }

                foreach ($parallelData as $data) {
                    $this->createWorkItem($data);
                }
            }
        } else {
            // just one work item has be to created
            $this->createWorkItem($provider ? $provider->getSingleInstanceData($this) : []);
        }
    }

    public function completeWork(): void
    {
        if ($this->isMultiInstance()) {
            $workItems = $this->getWorkItems();
            $processInstance = $this->getProcessInstance();

            $completed = $workItems->countOfCompletedInstances();
            $active = $workItems->countOfActiveInstances();

            $expression = $processInstance->getExpressionLanguage() ?: new ExpressionLanguage();
            $condition = $this->getCompletionCondition();
            $stop = false;

            if ($condition) {
                // check if we have to stop processing the other active work items
                $conditionData = [
                    'nrOfInstances' => $workItems->count(),
                    'nrOfCompletedInstances' => $completed,
                    'nrOfActiveInstances' => $active,
                ];

                $conditionData = array_merge($conditionData, $processInstance->getProcessData() ?: []);
                $stop = $expression->evaluate($condition, $conditionData);
            }

            if ($stop) {
                // we need to cancel all active work items left
                $this->cancelActiveInstances();
            } else {
                if ($this->isSequential()) {
                    $this->createWork();

                    return;
                } else {
                    if ($completed !== $active) {
                        // we have to wait until all work items are completed
                        return;
                    }
                }
            }

            // merge all instances data
            $provider = $processInstance->getDataProvider();
            $provider->mergeInstancesData($this);
        }

        // if no more instance needs to be created then end the activity
        $this->end();
    }

    /**
     * {@inheritdoc}
     */
    /*public function serialize()
    {
        return serialize([
            'id' => $this->id,
            'role' => $this->role,
            'name' => $this->name,
            'state' => $this->state,
            'defaultSequenceFlowId' => $this->defaultSequenceFlowId,
            'multiInstance' => $this->multiInstance,
            'sequential' => $this->sequential,
            'completionCondition' => $this->completionCondition,
            'workItems' => $this->workItems,
            'processInstance' => $this->processInstance,
        ]);
    }*/

    /**
     * {@inheritdoc}
     */
    /*public function unserialize($serialized)
    {
        foreach (unserialize($serialized) as $name => $value) {
            if ($name == get_parent_class($this)) {
                parent::unserialize($value);
                continue;
            }

            if (property_exists($this, $name)) {
                $this->$name = $value;
            }
        }
    }*/
}
