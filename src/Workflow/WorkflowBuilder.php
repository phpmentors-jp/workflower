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

use PHPMentors\Workflower\Workflow\Activity\SendTask;
use PHPMentors\Workflower\Workflow\Activity\ServiceTask;
use PHPMentors\Workflower\Workflow\Activity\Task;
use PHPMentors\Workflower\Workflow\Connection\SequenceFlow;
use PHPMentors\Workflower\Workflow\Element\ConditionalInterface;
use PHPMentors\Workflower\Workflow\Element\TransitionalInterface;
use PHPMentors\Workflower\Workflow\Event\EndEvent;
use PHPMentors\Workflower\Workflow\Event\StartEvent;
use PHPMentors\Workflower\Workflow\Gateway\ExclusiveGateway;
use PHPMentors\Workflower\Workflow\Gateway\ParallelGateway;
use PHPMentors\Workflower\Workflow\Participant\Role;
use Symfony\Component\ExpressionLanguage\Expression;

class WorkflowBuilder
{
    /**
     * @var array
     */
    private $endEvents = [];

    /**
     * @var array
     */
    private $exclusiveGateways = [];

    /**
     * @var array
     *
     * @since 2.0.0
     */
    private $parallelGateways = [];

    /**
     * @var array
     */
    private $roles = [];

    /**
     * @var array
     */
    private $sequenceFlows = [];

    /**
     * @var array
     */
    private $startEvents = [];

    /**
     * @var array
     */
    private $tasks = [];

    /**
     * @var array
     *
     * @since 1.2.0
     */
    private $serviceTasks = [];

    /**
     * @var array
     *
     * @since 1.3.0
     */
    private $sendTasks = [];

    /**
     * @var string
     */
    private $workflowId;

    /**
     * @var string
     */
    private $workflowName;

    /**
     * @var array
     */
    private $defaultableFlowObjects = [];

    /**
     * @param int|string $workflowId
     * @param int|string $workflowName
     */
    public function __construct($workflowId = null, $workflowName = null)
    {
        $this->workflowId = $workflowId;
        $this->workflowName = $workflowName;
    }

    /**
     * @param int|string $id
     */
    public function setWorkflowId($id)
    {
        $this->workflowId = $id;
    }

    /**
     * @param string $name
     */
    public function setWorkflowName($name)
    {
        $this->workflowName = $name;
    }

    /**
     * @param string $id
     * @param string $participant
     * @param string $name
     */
    public function addEndEvent($id, $participant, $name = null)
    {
        $this->endEvents[$id] = [$participant, $name];
    }

    /**
     * @param int|string $id
     * @param string     $participant
     * @param string     $name
     * @param int|string $defaultSequenceFlow
     */
    public function addExclusiveGateway($id, $participant, $name = null, $defaultSequenceFlow = null)
    {
        $this->exclusiveGateways[$id] = [$participant, $name];

        if ($defaultSequenceFlow !== null) {
            $this->defaultableFlowObjects[$defaultSequenceFlow] = $id;
        }
    }

    /**
     * @param int|string $id
     * @param string     $participant
     * @param string     $name
     */
    public function addParallelGateway($id, string $participant, string $name = null): void
    {
        $this->parallelGateways[$id] = [$participant, $name];
    }

    /**
     * @param int|string $id
     * @param string     $name
     */
    public function addRole($id, $name = null)
    {
        $this->roles[$id] = [$name];
    }

    /**
     * @param string     $source
     * @param string     $destination
     * @param int|string $id
     * @param string     $name
     * @param string     $condition
     */
    public function addSequenceFlow($source, $destination, $id = null, $name = null, $condition = null)
    {
        static $i = 0;

        if ($id === null) {
            $id = $source.'.'.$destination.$i;
            ++$i;
        }

        $this->sequenceFlows[$id] = [$source, $destination, $name, $condition];
    }

    /**
     * @param int|string $id
     * @param string     $participant
     * @param string     $name
     * @param int|string $defaultSequenceFlow
     */
    public function addStartEvent($id, $participant, $name = null, $defaultSequenceFlow = null)
    {
        $this->startEvents[$id] = [$participant, $name, $defaultSequenceFlow];

        if ($defaultSequenceFlow !== null) {
            $this->defaultableFlowObjects[$defaultSequenceFlow] = $id;
        }
    }

    /**
     * @param string     $id
     * @param string     $participant
     * @param string     $name
     * @param int|string $defaultSequenceFlow
     */
    public function addTask($id, $participant, $name = null, $defaultSequenceFlow = null)
    {
        $this->tasks[$id] = [$participant, $name];

        if ($defaultSequenceFlow !== null) {
            $this->defaultableFlowObjects[$defaultSequenceFlow] = $id;
        }
    }

    /**
     * @param string     $id
     * @param string     $participant
     * @param string     $operation
     * @param string     $name
     * @param int|string $defaultSequenceFlow
     *
     * @since 1.2.0
     */
    public function addServiceTask($id, $participant, $operation, $name = null, $defaultSequenceFlow = null)
    {
        $this->serviceTasks[$id] = [$participant, $operation, $name];

        if ($defaultSequenceFlow !== null) {
            $this->defaultableFlowObjects[$defaultSequenceFlow] = $id;
        }
    }

    /**
     * @param string     $id
     * @param string     $participant
     * @param string     $message
     * @param string     $operation
     * @param string     $name
     * @param int|string $defaultSequenceFlow
     *
     * @since 1.3.0
     */
    public function addSendTask($id, $participant, $message, $operation, $name = null, $defaultSequenceFlow = null)
    {
        $this->sendTasks[$id] = [$participant, $message, $operation, $name];

        if ($defaultSequenceFlow !== null) {
            $this->defaultableFlowObjects[$defaultSequenceFlow] = $id;
        }
    }

    /**
     * @return Workflow
     *
     * @throws \LogicException
     */
    public function build()
    {
        $workflow = new Workflow($this->workflowId, $this->workflowName);

        foreach ($this->roles as $id => $role) {
            list($name) = $role;
            $workflow->addRole(new Role($id, $name));
        }

        foreach ($this->startEvents as $id => $event) {
            list($roleId, $name) = $event;
            $this->assertWorkflowHasRole($workflow, $roleId);

            $workflow->addFlowObject(new StartEvent($id, $workflow->getRole($roleId), $name));
        }

        foreach ($this->endEvents as $id => $event) {
            list($roleId, $name) = $event;
            $this->assertWorkflowHasRole($workflow, $roleId);

            $workflow->addFlowObject(new EndEvent($id, $workflow->getRole($roleId), $name));
        }

        foreach ($this->tasks as $id => $task) {
            list($roleId, $name) = $task;
            $this->assertWorkflowHasRole($workflow, $roleId);

            $workflow->addFlowObject(new Task($id, $workflow->getRole($roleId), $name));
        }

        foreach ($this->serviceTasks as $id => $task) {
            list($roleId, $operation, $name) = $task;
            $this->assertWorkflowHasRole($workflow, $roleId);

            $workflow->addFlowObject(new ServiceTask($id, $workflow->getRole($roleId), $operation, $name));
        }

        foreach ($this->sendTasks as $id => $task) {
            list($roleId, $message, $operation, $name) = $task;
            $this->assertWorkflowHasRole($workflow, $roleId);

            $workflow->addFlowObject(new SendTask($id, $workflow->getRole($roleId), $message, $operation, $name));
        }

        foreach ($this->exclusiveGateways as $id => $gateway) {
            list($roleId, $name) = $gateway;
            $this->assertWorkflowHasRole($workflow, $roleId);

            $workflow->addFlowObject(new ExclusiveGateway($id, $workflow->getRole($roleId), $name));
        }

        foreach ($this->parallelGateways as $id => $gateway) {
            list($roleId, $name) = $gateway;
            $this->assertWorkflowHasRole($workflow, $roleId);

            $workflow->addFlowObject(new ParallelGateway($id, $workflow->getRole($roleId), $name));
        }

        foreach ($this->sequenceFlows as $id => $flow) {
            list($source, $destination, $name, $condition) = $flow;

            if (array_key_exists($id, $this->defaultableFlowObjects) && $condition !== null) {
                throw new \LogicException(sprintf('The sequence flow "%s" has the condition "%s". A condition cannot be set to the default sequence flow.', $id, $condition));
            }

            $flowObject = $workflow->getFlowObject($source); /* @var $flowObject TransitionalInterface */
            $workflow->addConnectingObject(new SequenceFlow($id, $flowObject, $workflow->getFlowObject($destination), $name, $condition === null ? null : new Expression($condition)));

            if (array_key_exists($id, $this->defaultableFlowObjects)) {
                $flowObject = $workflow->getFlowObject($this->defaultableFlowObjects[$id]);
                /* @var $flowObject ConditionalInterface */$flowObject->setDefaultSequenceFlowId($id);
            }
        }

        return $workflow;
    }

    /**
     * @param Workflow   $workflow
     * @param int|string $roleId
     *
     * @throws \LogicException
     */
    private function assertWorkflowHasRole(Workflow $workflow, $roleId)
    {
        if (!$workflow->hasRole($roleId)) {
            throw new \LogicException(sprintf('The workflow "%s" does not have the role "%s".', $workflow->getId(), $roleId));
        }
    }
}
