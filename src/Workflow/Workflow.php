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
use PHPMentors\Workflower\Workflow\Participant\Role;
use PHPMentors\Workflower\Workflow\Participant\RoleCollection;
use PHPMentors\Workflower\Workflow\Type\ConditionalInterface;
use PHPMentors\Workflower\Workflow\Type\ConnectingObjectCollection;
use PHPMentors\Workflower\Workflow\Type\ConnectingObjectInterface;
use PHPMentors\Workflower\Workflow\Type\FlowObjectCollection;
use PHPMentors\Workflower\Workflow\Type\FlowObjectInterface;
use PHPMentors\Workflower\Workflow\Type\TransitionalInterface;
use Stagehand\FSM\Event\TransitionEvent;
use Stagehand\FSM\State\FinalState;
use Stagehand\FSM\State\InitialState;
use Stagehand\FSM\State\State;
use Stagehand\FSM\State\StateInterface;
use Stagehand\FSM\StateMachine\StateMachine;
use Stagehand\FSM\StateMachine\StateMachineInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class Workflow implements EntityInterface, IdentifiableInterface, \Serializable
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
     * @var RoleCollection
     */
    private $roleCollection;

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
    private $processData;

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
        $this->roleCollection = new RoleCollection();
        $this->stateMachine = $this->createStateMachine($id);
        $this->name = $name;
    }

    /**
     * {@inheritDoc}
     */
    public function serialize()
    {
        return serialize(array(
            'name' => $this->name,
            'connectingObjectCollection' => $this->connectingObjectCollection,
            'flowObjectCollection' => $this->flowObjectCollection,
            'roleCollection' => $this->roleCollection,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'stateMachine' => $this->stateMachine,
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function unserialize($serialized)
    {
        foreach (unserialize($serialized) as $name => $value) {
            if (property_exists($this, $name)) {
                $this->$name = $value;
            }
        }
    }

    /**
     * {@inheritDoc}
     *
     * @return int|string
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
     * @param int|string $id
     *
     * @return ConnectingObjectInterface|null
     */
    public function getConnectingObject($id)
    {
        return $this->connectingObjectCollection->get($id);
    }

    /**
     * @param int|string $id
     *
     * @return FlowObjectInterface|null
     */
    public function getFlowObject($id)
    {
        return $this->flowObjectCollection->get($id);
    }

    /**
     * @param Role $role
     */
    public function addRole(Role $role)
    {
        $this->roleCollection->add($role);
    }

    /**
     * @param int|string $id
     *
     * @return bool
     */
    public function hasRole($id)
    {
        return $this->roleCollection->get($id) !== null;
    }

    /**
     * @param int|string $id
     *
     * @return Role
     */
    public function getRole($id)
    {
        return $this->roleCollection->get($id);
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
     * @return FlowObjectInterface|null
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
     * @return FlowObjectInterface|null
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

        if ($this->getCurrentFlowObject() instanceof ActivityInterface) {
            $this->createWorkItem($this->getCurrentFlowObject());
        } elseif ($this->getCurrentFlowObject() instanceof EndEvent) {
            $this->end($this->getCurrentFlowObject());
        }
    }

    /**
     * @param ActivityInterface    $activity
     * @param ParticipantInterface $participant
     *
     * @throws AccessDeniedException
     * @throws UnexpectedActivityException
     */
    public function allocateWorkItem(ActivityInterface $activity, ParticipantInterface $participant)
    {
        if (!$participant->hasRole($activity->getRole()->getId())) {
            throw new AccessDeniedException();
        }

        if (!$activity->equals($this->getCurrentFlowObject())) {
            throw new UnexpectedActivityException();
        }

        $activity->allocate($participant);
    }

    /**
     * @param ActivityInterface    $activity
     * @param ParticipantInterface $participant
     *
     * @throws AccessDeniedException
     * @throws UnexpectedActivityException
     */
    public function startWorkItem(ActivityInterface $activity, ParticipantInterface $participant)
    {
        if (!$participant->hasRole($activity->getRole()->getId())) {
            throw new AccessDeniedException();
        }

        if (!$activity->equals($this->getCurrentFlowObject())) {
            throw new UnexpectedActivityException();
        }

        $activity->start();
    }

    /**
     * @param ActivityInterface    $activity
     * @param ParticipantInterface $participant
     *
     * @throws AccessDeniedException
     * @throws UnexpectedActivityException
     */
    public function completeWorkItem(ActivityInterface $activity, ParticipantInterface $participant)
    {
        if (!$participant->hasRole($activity->getRole()->getId())) {
            throw new AccessDeniedException();
        }

        if (!$activity->equals($this->getCurrentFlowObject())) {
            throw new UnexpectedActivityException();
        }

        $activity->complete($participant);
        $this->selectSequenceFlow($activity);

        if ($this->getCurrentFlowObject() instanceof ActivityInterface) {
            $this->createWorkItem($this->getCurrentFlowObject());
        } elseif ($this->getCurrentFlowObject() instanceof EndEvent) {
            $this->end($this->getCurrentFlowObject());
        }
    }

    /**
     * @param array $processData
     */
    public function setProcessData(array $processData)
    {
        $this->processData = $processData;
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
     * @param TransitionalInterface $currentFlowObject
     *
     * @throws SequenceFlowNotSelectedException
     */
    private function selectSequenceFlow(TransitionalInterface $currentFlowObject)
    {
        foreach ($this->connectingObjectCollection->filterBySource($currentFlowObject) as $connectingObject) { /* @var $connectingObject ConnectingObjectInterface */
            if ($connectingObject instanceof SequenceFlow) {
                if (!($currentFlowObject instanceof ConditionalInterface) || $connectingObject->getId() !== $currentFlowObject->getDefaultSequenceFlowId()) {
                    $condition = $connectingObject->getCondition();
                    if ($condition === null) {
                        $selectedSequenceFlow = $connectingObject;
                        break;
                    } else {
                        $expressionLanguage = new ExpressionLanguage();
                        if ($expressionLanguage->evaluate($condition, $this->processData)) {
                            $selectedSequenceFlow = $connectingObject;
                            break;
                        }
                    }
                }
            }
        }

        if (!isset($selectedSequenceFlow)) {
            if (!($currentFlowObject instanceof ConditionalInterface) || $currentFlowObject->getDefaultSequenceFlowId() === null) {
                throw new SequenceFlowNotSelectedException(sprintf('No sequence flow can be selected on "%s".',  $currentFlowObject->getId()));
            }

            $selectedSequenceFlow = $this->connectingObjectCollection->get($currentFlowObject->getDefaultSequenceFlowId());
        }

        $this->stateMachine->triggerEvent($selectedSequenceFlow->getDestination()->getId());

        if ($this->getCurrentFlowObject() instanceof GatewayInterface) {
            $this->selectSequenceFlow($this->getCurrentFlowObject());
        }
    }

    /**
     * @param ActivityInterface $activity
     *
     * @throws UnexpectedActivityException
     */
    private function createWorkItem(ActivityInterface $activity)
    {
        if (!$activity->equals($this->getCurrentFlowObject())) {
            throw new UnexpectedActivityException();
        }

        $activity->createWorkItem();
    }
}
