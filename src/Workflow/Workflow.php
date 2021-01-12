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

use PHPMentors\Workflower\Workflow\Activity\ActivityInterface;
use PHPMentors\Workflower\Workflow\Activity\UnexpectedActivityException;
use PHPMentors\Workflower\Workflow\Connection\SequenceFlow;
use PHPMentors\Workflower\Workflow\Element\ConditionalInterface;
use PHPMentors\Workflower\Workflow\Element\ConnectingObjectCollection;
use PHPMentors\Workflower\Workflow\Element\ConnectingObjectInterface;
use PHPMentors\Workflower\Workflow\Element\FlowObjectCollection;
use PHPMentors\Workflower\Workflow\Element\FlowObjectInterface;
use PHPMentors\Workflower\Workflow\Element\Token;
use PHPMentors\Workflower\Workflow\Element\TransitionalInterface;
use PHPMentors\Workflower\Workflow\Event\EndEvent;
use PHPMentors\Workflower\Workflow\Event\StartEvent;
use PHPMentors\Workflower\Workflow\Gateway\ExclusiveGateway;
use PHPMentors\Workflower\Workflow\Gateway\ParallelGateway;
use PHPMentors\Workflower\Workflow\Operation\OperationalInterface;
use PHPMentors\Workflower\Workflow\Operation\OperationRunnerInterface;
use PHPMentors\Workflower\Workflow\Participant\ParticipantInterface;
use PHPMentors\Workflower\Workflow\Participant\Role;
use PHPMentors\Workflower\Workflow\Participant\RoleCollection;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class Workflow implements \Serializable
{
    const DEFAULT_ROLE_ID = '__ROLE__';

    /**
     * @var int|string
     *
     * @since 2.0.0
     */
    private $id;

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
     * @var StartEvent
     *
     * @since 2.0.0
     */
    private $startEvent;

    /**
     * @var EndEvent[]
     *
     * @since 2.0.0
     */
    private $endEvents = [];

    /**
     * @var array
     */
    private $processData;

    /**
     * @var ExpressionLanguage
     *
     * @since 1.1.0
     */
    private $expressionLanguage;

    /**
     * @var OperationRunnerInterface
     *
     * @since 1.2.0
     */
    private $operationRunner;

    /**
     * @var TokenRegistry
     *
     * @since 2.0.0
     */
    private $tokenRegistry;

    /**
     * @var ActivityLogCollection
     *
     * @since 2.0.0
     */
    private $activityLogCollection;

    /**
     * @param int|string $id
     * @param string     $name
     */
    public function __construct($id, $name)
    {
        $this->id = $id;
        $this->name = $name;
        $this->connectingObjectCollection = new ConnectingObjectCollection();
        $this->flowObjectCollection = new FlowObjectCollection();
        $this->roleCollection = new RoleCollection();
        $this->tokenRegistry = new TokenRegistry();
        $this->activityLogCollection = new ActivityLogCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize([
            'id' => $this->id,
            'name' => $this->name,
            'connectingObjectCollection' => $this->connectingObjectCollection,
            'flowObjectCollection' => $this->flowObjectCollection,
            'roleCollection' => $this->roleCollection,
            'startEvent' => $this->startEvent,
            'endEvents' => $this->endEvents,
            'tokenRegistry' => $this->tokenRegistry,
            'activityLogCollection' => $this->activityLogCollection,
        ]);
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     *
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
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
        $this->connectingObjectCollection->add($connectingObject);
    }

    /**
     * @param FlowObjectInterface $flowObject
     */
    public function addFlowObject(FlowObjectInterface $flowObject)
    {
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
     * @param TransitionalInterface $flowObject
     *
     * @return ConnectingObjectCollection
     */
    public function getConnectingObjectCollectionBySource(TransitionalInterface $flowObject)
    {
        return $this->connectingObjectCollection->filterBySource($flowObject);
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
     * {@inheritdoc}
     */
    public function isActive()
    {
        return count($this->tokenRegistry->getTokens()) > 0 && count($this->tokenRegistry->getActiveTokens()) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function isEnded()
    {
        return count($this->tokenRegistry->getTokens()) > 0 && count($this->tokenRegistry->getActiveTokens()) == 0;
    }

    /**
     * @return FlowObjectInterface[]
     *
     * @since 2.0.0
     */
    public function getActiveFlowObjects(): array
    {
        return array_map(function (Token $token) {
            return $token->getCurrentFlowObject();
        }, $this->tokenRegistry->getActiveTokens()
        );
    }

    /**
     * @return FlowObjectInterface|null
     */
    public function getCurrentFlowObject(): ?FlowObjectInterface
    {
        $flowObjects = $this->getCurrentFlowObjects();
        if (count($flowObjects) == 0) {
            return null;
        }

        return $flowObjects[0];
    }

    /**
     * @return FlowObjectInterface[]
     *
     * @since 2.0.0
     */
    public function getCurrentFlowObjects(): array
    {
        return array_map(function (Token $token) {
            return $token->getCurrentFlowObject();
        }, $this->tokenRegistry->getTokens()
        );
    }

    /**
     * @return FlowObjectInterface|null
     */
    public function getPreviousFlowObject(): ?FlowObjectInterface
    {
        $flowObjects = $this->getPreviousFlowObjects();
        if (count($flowObjects) == 0) {
            return null;
        }

        return $flowObjects[0];
    }

    /**
     * @return FlowObjectInterface[]
     *
     * @since 2.0.0
     */
    public function getPreviousFlowObjects(): array
    {
        return array_map(function (Token $token) {
            return $token->getPreviousFlowObject();
        }, $this->tokenRegistry->getTokens()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function start(StartEvent $event)
    {
        $this->startEvent = $event;
        $this->tokenRegistry->register($this->generateToken($this->startEvent));
        $this->selectSequenceFlow($this->startEvent);
    }

    /**
     * @param ActivityInterface    $activity
     * @param ParticipantInterface $participant
     */
    public function allocateWorkItem(ActivityInterface $activity, ParticipantInterface $participant)
    {
        $this->assertParticipantHasRole($activity, $participant);
        $this->assertCurrentFlowObjectIsExpectedActivity($activity);

        $activity->allocate($participant);
    }

    /**
     * @param ActivityInterface    $activity
     * @param ParticipantInterface $participant
     */
    public function startWorkItem(ActivityInterface $activity, ParticipantInterface $participant)
    {
        $this->assertParticipantHasRole($activity, $participant);
        $this->assertCurrentFlowObjectIsExpectedActivity($activity);

        $activity->start();
    }

    /**
     * @param ActivityInterface    $activity
     * @param ParticipantInterface $participant
     */
    public function completeWorkItem(ActivityInterface $activity, ParticipantInterface $participant)
    {
        $this->assertParticipantHasRole($activity, $participant);
        $this->assertCurrentFlowObjectIsExpectedActivity($activity);

        $activity->complete($participant);
        $this->selectSequenceFlow($activity);
    }

    /**
     * @param array $processData
     */
    public function setProcessData(array $processData)
    {
        $this->processData = $processData;
    }

    /**
     * @return array
     *
     * @since 1.2.0
     */
    public function getProcessData()
    {
        return $this->processData;
    }

    /**
     * @param ExpressionLanguage $expressionLanguage
     *
     * @since 1.1.0
     */
    public function setExpressionLanguage(ExpressionLanguage $expressionLanguage)
    {
        $this->expressionLanguage = $expressionLanguage;
    }

    /**
     * @param OperationRunnerInterface $operationRunner
     *
     * @since 1.2.0
     */
    public function setOperationRunner(OperationRunnerInterface $operationRunner)
    {
        $this->operationRunner = $operationRunner;
    }

    /**
     * @param EndEvent $event
     */
    private function end(EndEvent $event)
    {
        $this->endEvents[] = $event;
    }

    /**
     * @return \DateTime
     */
    public function getStartDate()
    {
        if ($this->startEvent === null) {
            return null;
        }

        return $this->startEvent->getStartDate();
    }

    /**
     * @return \DateTime
     */
    public function getEndDate()
    {
        if ($this->isEnded()) {
            return $this->endEvents[ count($this->endEvents) - 1 ]->getEndDate();
        }
    }

    /**
     * @return ActivityLogCollection
     */
    public function getActivityLog()
    {
        return $this->activityLogCollection;
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
                        $expressionLanguage = $this->expressionLanguage ?: new ExpressionLanguage();
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
                throw new SequenceFlowNotSelectedException(sprintf('No sequence flow can be selected on "%s".', $currentFlowObject->getId()));
            }

            $selectedSequenceFlow = $this->connectingObjectCollection->get($currentFlowObject->getDefaultSequenceFlowId());
        }

        $token = $currentFlowObject->getToken();
        assert(count($token) === 1);

        $this->flow(current($token), $selectedSequenceFlow->getDestination());
    }

    /**
     * @param ActivityInterface    $activity
     * @param ParticipantInterface $participant
     *
     * @throws AccessDeniedException
     */
    private function assertParticipantHasRole(ActivityInterface $activity, ParticipantInterface $participant)
    {
        if (!$participant->hasRole($activity->getRole()->getId())) {
            throw new AccessDeniedException(sprintf('The participant "%s" does not have the role "%s" that is required to operate the activity "%s".', $participant->getId(), $activity->getRole()->getId(), $activity->getId()));
        }
    }

    /**
     * @param ActivityInterface $activity
     *
     * @throws UnexpectedActivityException
     */
    private function assertCurrentFlowObjectIsExpectedActivity(ActivityInterface $activity)
    {
        foreach ($this->getActiveFlowObjects() as $activeFlowObject) {
            if ($activity->equals($activeFlowObject)) {
                return true;
            }
        }

        throw new UnexpectedActivityException(sprintf('The activity "%s" is not found in the active flow objects.', $activity->getId()));
    }

    /**
     * @since 1.2.0
     *
     * @param ActivityInterface $operational
     */
    private function executeOperationalActivity(ActivityInterface $operational)
    {
        $participant = $this->operationRunner->provideParticipant(/* @var $operational OperationalInterface */ $operational, $this);
        $this->allocateWorkItem($operational, $participant);
        $this->startWorkItem($operational, $participant);
        $this->operationRunner->run(/* @var $operational OperationalInterface */ $operational, $this);
        $this->completeWorkItem($operational, $participant);
    }

    /**
     * @param FlowObjectInterface $flowObject
     * @return Token
     * @throws \Exception
     *
     * @since 2.0.0
     */
    private function generateToken(FlowObjectInterface $flowObject): Token
    {
        return new Token(sha1(random_bytes(24)), $flowObject);
    }

    /**
     * @param Token $token
     * @param FlowObjectInterface $flowObject
     * @throws \Exception
     *
     * @since 2.0.0
     */
    private function flow(Token $token, FlowObjectInterface $flowObject): void
    {
        $token->flow($flowObject);

        if ($flowObject instanceof ExclusiveGateway) {
            $this->selectSequenceFlow($flowObject);
        } elseif ($flowObject instanceof ParallelGateway) {
            $parallelGateway = $flowObject;
            $incomings = $this->connectingObjectCollection->filterByDestination($parallelGateway);
            $incomingTokens = $parallelGateway->getToken();
            if (count($incomingTokens) == count($incomings)) {
                foreach ($incomingTokens as $incomingToken) {
                    $parallelGateway->detachToken($incomingToken);
                    $this->tokenRegistry->remove($incomingToken);
                }

                foreach ($this->connectingObjectCollection->filterBySource($parallelGateway) as $outgoing) {
                    $outgoingToken = $this->generateToken($parallelGateway);
                    $this->tokenRegistry->register($outgoingToken);
                    $this->flow($outgoingToken, $outgoing->getDestination());
                }
            }
        } elseif ($flowObject instanceof ActivityInterface) {
            $flowObject->createWorkItem();
            $this->activityLogCollection->add(new ActivityLog($flowObject));

            if ($flowObject instanceof OperationalInterface) {
                $this->executeOperationalActivity($flowObject);
            }
        } elseif ($flowObject instanceof EndEvent) {
            $this->end($flowObject);
        }
    }
}
