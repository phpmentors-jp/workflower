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

namespace PHPMentors\Workflower\Workflow;

use PHPMentors\Workflower\Workflow\Activity\Task;
use PHPMentors\Workflower\Workflow\Connection\SequenceFlow;
use PHPMentors\Workflower\Workflow\Event\EndEvent;
use PHPMentors\Workflower\Workflow\Event\StartEvent;
use PHPMentors\Workflower\Workflow\Gateway\ExclusiveGateway;
use PHPMentors\Workflower\Workflow\Participant\Role;
use Symfony\Component\ExpressionLanguage\Expression;

class WorkflowBuilder
{
    /**
     * @var array
     */
    private $endEvents = array();

    /**
     * @var array
     */
    private $exclusiveGateways = array();

    /**
     * @var array
     */
    private $roles = array();

    /**
     * @var array
     */
    private $sequenceFlows = array();

    /**
     * @var array
     */
    private $startEvents = array();

    /**
     * @var array
     */
    private $tasks = array();

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
    private $defaultableFlowObjects = array();

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
        $this->endEvents[$id] = array($participant, $name);
    }

    /**
     * @param int|string $id
     * @param string     $participant
     * @param string     $name
     * @param int|string $defaultSequenceFlow
     */
    public function addExclusiveGateway($id, $participant, $name = null, $defaultSequenceFlow = null)
    {
        $this->exclusiveGateways[$id] = array($participant, $name);

        if ($defaultSequenceFlow !== null) {
            $this->defaultableFlowObjects[$defaultSequenceFlow] = $id;
        }
    }

    /**
     * @param int|string $id
     * @param string     $name
     */
    public function addRole($id, $name = null)
    {
        $this->roles[$id] = array($name);
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

        $this->sequenceFlows[$id] = array($source, $destination, $name, $condition);
    }

    /**
     * @param int|string $id
     * @param string     $participant
     * @param string     $name
     * @param int|string $defaultSequenceFlow
     */
    public function addStartEvent($id, $participant, $name = null, $defaultSequenceFlow = null)
    {
        $this->startEvents[$id] = array($participant, $name, $defaultSequenceFlow);

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
        $this->tasks[$id] = array($participant, $name, $defaultSequenceFlow);

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

        foreach ($this->exclusiveGateways as $id => $gateway) {
            list($roleId, $name) = $gateway;
            $this->assertWorkflowHasRole($workflow, $roleId);

            $workflow->addFlowObject(new ExclusiveGateway($id, $workflow->getRole($roleId), $name));
        }

        foreach ($this->sequenceFlows as $id => $flow) {
            list($source, $destination, $name, $condition) = $flow;

            if (array_key_exists($id, $this->defaultableFlowObjects) && $condition !== null) {
                throw new \LogicException(sprintf('The sequence flow "%s" has the condition "%s". A condition cannot be set to the default sequence flow.', $id, $condition));
            }

            $workflow->addConnectingObject(new SequenceFlow($id, $workflow->getFlowObject($source), $workflow->getFlowObject($destination), $name, $condition === null ? null : new Expression($condition)));

            if (array_key_exists($id, $this->defaultableFlowObjects)) {
                $workflow->getFlowObject($this->defaultableFlowObjects[$id])->setDefaultSequenceFlowId($id);
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
