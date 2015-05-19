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

namespace PHPMentors\Workflower\Workflow;

use PHPMentors\DomainKata\Entity\EntityInterface;
use PHPMentors\DomainKata\Entity\Operation\IdentifiableInterface;
use PHPMentors\Workflower\Workflow\Activity\ActivityInterface;
use PHPMentors\Workflower\Workflow\Activity\UnexpectedActivityException;
use PHPMentors\Workflower\Workflow\Connection\SequenceFlow;
use PHPMentors\Workflower\Workflow\Event\EndEvent;
use PHPMentors\Workflower\Workflow\Event\StartEvent;
use PHPMentors\Workflower\Workflow\Gateway\GatewayInterface;
use PHPMentors\Workflower\Workflow\Participant\ParticipantInterface;
use PHPMentors\Workflower\Workflow\Type\ConnectingObjectCollection;
use PHPMentors\Workflower\Workflow\Type\ConnectingObjectInterface;
use PHPMentors\Workflower\Workflow\Type\FlowObjectCollection;
use PHPMentors\Workflower\Workflow\Type\FlowObjectInterface;
use PHPMentors\Workflower\Workflow\Type\TransitionalFlowObjectInterface;
use Stagehand\FSM\Event\TransitionEvent;
use Stagehand\FSM\State\FinalState;
use Stagehand\FSM\State\InitialState;
use Stagehand\FSM\State\State;
use Stagehand\FSM\State\StateInterface;
use Stagehand\FSM\StateMachine\StateMachine;
use Stagehand\FSM\StateMachine\StateMachineInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class Workflow implements EntityInterface, IdentifiableInterface, WorkflowInterface
{
    /**
     * @var string
     */
    private static $STATE_START = '__START__';

    /**
     * @var string
     */
    private $name;

    /**
     * @var ConnectingObjectCollection
     */
    private $connectingObjectCollection;

    /**
     * @var FlowObjectCollection
     */
    private $flowObjectCollection;

    /**
     * @var string[]
     */
    private $roles = array();

    /**
     * @var \DateTime
     */
    private $startDate;

    /**
     * @var \DateTime
     */
    private $endDate;

    /**
     * @var array
     */
    private $processData = array();

    /**
     * @var StateMachineInterface
     */
    private $stateMachine;

    /**
     * @param int|string $id
     * @param string     $name
     */
    public function __construct($id, $name)
    {
        $this->connectingObjectCollection = new ConnectingObjectCollection();
        $this->flowObjectCollection = new FlowObjectCollection();
        $this->stateMachine = $this->createStateMachine($id);
        $this->name = $name;
    }

    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        return $this->stateMachine->getStateMachineId();
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param ConnectingObjectInterface $connectingObject
     */
    public function addConnectingObject(ConnectingObjectInterface $connectingObject)
    {
        $this->stateMachine->addTransition(
            $this->stateMachine->getState($connectingObject->getSource()->getId()),
            new TransitionEvent($connectingObject->getDestination()->getId()),
            $this->stateMachine->getState($connectingObject->getDestination()->getId()),
            null,
            null
        );

        $this->connectingObjectCollection->add($connectingObject);
    }

    /**
     * @param FlowObjectInterface $flowObject
     */
    public function addFlowObject(FlowObjectInterface $flowObject)
    {
        $this->stateMachine->addState(new State($flowObject->getId()));
        if ($flowObject instanceof StartEvent) {
            $this->stateMachine->addTransition(
                $this->stateMachine->getState(self::$STATE_START),
                new TransitionEvent($flowObject->getId()),
                $this->stateMachine->getState($flowObject->getId()),
                null,
                null
            );
        } elseif ($flowObject instanceof EndEvent) {
            $this->stateMachine->addTransition(
                $this->stateMachine->getState($flowObject->getId()),
                new TransitionEvent($flowObject->getId()),
                $this->stateMachine->getState(StateInterface::STATE_FINAL),
                null,
                null
            );
        }

        $this->flowObjectCollection->add($flowObject);
    }

    /**
     * @param string $id
     *
     * @return FlowObjectInterface|null
     */
    public function getFlowObject($id)
    {
        return $this->flowObjectCollection->get($id);
    }

    /**
     * @param string $role
     */
    public function addRole($role)
    {
        if ($this->hasRole($role)) {
            return;
        }

        $this->roles[] = $role;
    }

    /**
     * @param string $role
     *
     v     * @return bool
     */
    public function hasRole($role)
    {
        return in_array($role, $this->roles);
    }

    /**
     * {@inheritDoc}
     */
    public function isActive()
    {
        return $this->stateMachine->isActive();
    }

    /**
     * {@inheritDoc}
     */
    public function isEnded()
    {
        return $this->stateMachine->isEnded();
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrentFlowObject()
    {
        $state = $this->stateMachine->getCurrentState();
        if ($state === null) {
            return null;
        }

        if ($state instanceof FinalState) {
            return $this->flowObjectCollection->get($this->stateMachine->getPreviousState()->getStateId());
        } else {
            return $this->flowObjectCollection->get($state->getStateId());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getPreviousFlowObject()
    {
        $state = $this->stateMachine->getPreviousState();
        if ($state === null) {
            return null;
        }

        $previousFlowObject = $this->flowObjectCollection->get($state->getStateId());
        if ($previousFlowObject instanceof EndEvent) {
            $transitionLogs = $this->stateMachine->getTransitionLogs();

            return $this->flowObjectCollection->get($transitionLogs[count($transitionLogs) - 2]->getFromState()->getStateId());
        } else {
            return $previousFlowObject;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function start(StartEvent $event)
    {
        $this->startDate = new \DateTime();
        $this->stateMachine->start();
        $this->stateMachine->triggerEvent($event->getId());
        $this->selectSequenceFlow($event);

        if ($this->getCurrentFlowObject() instanceof EndEvent) {
            $this->end($this->getCurrentFlowObject());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function assignActivity(ActivityInterface $activity, ParticipantInterface $participant)
    {
        if (!$participant->hasRole($activity->getRole())) {
            throw new AccessDeniedException();
        }

        if (!$activity->equals($this->getCurrentFlowObject())) {
            throw new UnexpectedActivityException();
        }

        $activity->start($participant);
    }

    /**
     * {@inheritDoc}
     */
    public function completeActivity(ActivityInterface $activity, ParticipantInterface $participant)
    {
        if (!$participant->hasRole($activity->getRole())) {
            throw new AccessDeniedException();
        }

        if (!$activity->equals($this->getCurrentFlowObject())) {
            throw new UnexpectedActivityException();
        }

        $activity->complete($participant);
        $this->selectSequenceFlow($activity);

        if ($this->getCurrentFlowObject() instanceof EndEvent) {
            $this->end($this->getCurrentFlowObject());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function setProcessData($key, $value)
    {
        $this->processData[$key] = $value;
    }

    /**
     * @param EndEvent $event
     */
    private function end(EndEvent $event)
    {
        $this->stateMachine->triggerEvent($event->getId());
        $this->endDate = new \DateTime();
    }

    /**
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * @param string $stateMachineName
     *
     * @return StateMachineInterface
     */
    private function createStateMachine($stateMachineName)
    {
        $stateMachine = new StateMachine($stateMachineName);
        $stateMachine->addState(new InitialState());
        $stateMachine->addState(new FinalState());
        $stateMachine->addState(new State(self::$STATE_START));
        $stateMachine->addTransition(
            $stateMachine->getState(StateInterface::STATE_INITIAL),
            new TransitionEvent(\Stagehand\FSM\Event\EventInterface::EVENT_START),
            $stateMachine->getState(self::$STATE_START),
            null,
            null
        );

        return $stateMachine;
    }

    /**
     * @param TransitionalFlowObjectInterface $currentFlowObject
     *
     * @throws SequenceFlowNotSelectedException
     */
    private function selectSequenceFlow(TransitionalFlowObjectInterface $currentFlowObject)
    {
        $nonDefaultSequenceFlows = array();
        $defaultSequenceFlow = null;
        foreach ($this->connectingObjectCollection->filterBySource($currentFlowObject) as $connectingObject) { /* @var $connectingObject ConnectingObjectInterface */
            if ($connectingObject instanceof SequenceFlow) {
                if ($connectingObject->isDefault()) {
                    $defaultSequenceFlow = $connectingObject;
                } else {
                    $nonDefaultSequenceFlows[] = $connectingObject;
                }
            }
        }

        $selectedSequenceFlow = null;
        foreach ($nonDefaultSequenceFlows as $sequenceFlow) { /* @var $sequenceFlow SequenceFlow */
            $condition = $sequenceFlow->getCondition();
            if ($condition === null) {
                $selectedSequenceFlow = $sequenceFlow;
                break;
            } else {
                $expressionLanguage = new ExpressionLanguage();
                if ($expressionLanguage->evaluate($condition, $this->processData)) {
                    $selectedSequenceFlow = $sequenceFlow;
                    break;
                }
            }
        }

        if ($selectedSequenceFlow === null && $defaultSequenceFlow !== null) {
            $selectedSequenceFlow = $defaultSequenceFlow;
        }

        if ($selectedSequenceFlow === null) {
            throw new SequenceFlowNotSelectedException(sprintf('No sequence flow can be selected on "%s".',  $currentFlowObject->getId()));
        }

        $this->stateMachine->triggerEvent($selectedSequenceFlow->getDestination()->getId());

        if ($this->getCurrentFlowObject() instanceof GatewayInterface) {
            $this->selectSequenceFlow($this->getCurrentFlowObject());
        }
    }
}
