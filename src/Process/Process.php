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

namespace PHPMentors\Workflower\Process;

use PHPMentors\Workflower\Workflow\Activity\ActivityInterface;
use PHPMentors\Workflower\Workflow\Activity\UnexpectedActivityStateException;
use PHPMentors\Workflower\Workflow\Event\StartEvent;
use PHPMentors\Workflower\Workflow\Operation\OperationRunnerInterface;
use PHPMentors\Workflower\Workflow\Workflow;
use PHPMentors\Workflower\Workflow\WorkflowRepositoryInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class Process
{
    /**
     * @var int|string|WorkflowContextInterface
     */
    private $workflowContext;

    /**
     * @var WorkflowRepositoryInterface
     */
    private $workflowRepository;

    /**
     * @var ExpressionLanguage
     *
     * @since 1.2.0
     */
    private $expressionLanguage;

    /**
     * @var OperationRunnerInterface
     *
     * @since 1.2.0
     */
    private $operationRunner;

    /**
     * @param int|string|WorkflowContextInterface $workflowContext
     * @param WorkflowRepositoryInterface         $workflowRepository
     * @param OperationRunnerInterface            $operationRunner
     */
    public function __construct($workflowContext, WorkflowRepositoryInterface $workflowRepository, OperationRunnerInterface $operationRunner)
    {
        $this->workflowContext = $workflowContext;
        $this->workflowRepository = $workflowRepository;
        $this->operationRunner = $operationRunner;
    }

    /**
     * @param ExpressionLanguage $expressionLanguage
     *
     * @since 1.2.0
     */
    public function setExpressionLanguage(ExpressionLanguage $expressionLanguage)
    {
        $this->expressionLanguage = $expressionLanguage;
    }

    /**
     * @param EventContextInterface $eventContext
     */
    public function start(EventContextInterface $eventContext)
    {
        assert($eventContext->getProcessContext() !== null);
        assert($eventContext->getProcessContext()->getWorkflow() === null);
        assert($eventContext->getEventId() !== null);

        $workflow = $this->configureWorkflow($this->createWorkflow());
        $eventContext->getProcessContext()->setWorkflow($workflow);
        $workflow->setProcessData($eventContext->getProcessContext()->getProcessData());
        $flowObject = $workflow->getFlowObject($eventContext->getEventId());
        $workflow->start(/* @var $flowObject StartEvent */$flowObject);
    }

    /**
     * @param WorkItemContextInterface $workItemContext
     */
    public function allocateWorkItem(WorkItemContextInterface $workItemContext)
    {
        assert($workItemContext->getProcessContext() !== null);
        assert($workItemContext->getProcessContext()->getWorkflow() !== null);
        assert($workItemContext->getActivityId() !== null);

        $workflow = $this->configureWorkflow($workItemContext->getProcessContext()->getWorkflow());
        $flowObject = $workflow->getFlowObject($workItemContext->getActivityId());
        $workflow->allocateWorkItem(/* @var $flowObject ActivityInterface */$flowObject, $workItemContext->getParticipant());
    }

    /**
     * @param WorkItemContextInterface $workItemContext
     */
    public function startWorkItem(WorkItemContextInterface $workItemContext)
    {
        assert($workItemContext->getProcessContext() !== null);
        assert($workItemContext->getProcessContext()->getWorkflow() !== null);
        assert($workItemContext->getActivityId() !== null);

        $workflow = $this->configureWorkflow($workItemContext->getProcessContext()->getWorkflow());
        $flowObject = $workflow->getFlowObject($workItemContext->getActivityId());
        $workflow->startWorkItem(/* @var $flowObject ActivityInterface */$flowObject, $workItemContext->getParticipant());
    }

    /**
     * @param WorkItemContextInterface $workItemContext
     */
    public function completeWorkItem(WorkItemContextInterface $workItemContext)
    {
        assert($workItemContext->getProcessContext() !== null);
        assert($workItemContext->getProcessContext()->getWorkflow() !== null);
        assert($workItemContext->getActivityId() !== null);

        $workflow = $this->configureWorkflow($workItemContext->getProcessContext()->getWorkflow());
        $workflow->setProcessData($workItemContext->getProcessContext()->getProcessData());
        $flowObject = $workflow->getFlowObject($workItemContext->getActivityId());
        $workflow->completeWorkItem(/* @var $flowObject ActivityInterface */$flowObject, $workItemContext->getParticipant());
    }

    /**
     * @param WorkItemContextInterface $workItemContext
     *
     * @throws UnexpectedActivityStateException
     */
    public function executeWorkItem(WorkItemContextInterface $workItemContext)
    {
        assert($workItemContext->getProcessContext() !== null);
        assert($workItemContext->getProcessContext()->getWorkflow() !== null);
        assert($workItemContext->getActivityId() !== null);
        assert($workItemContext->getProcessContext()->getWorkflow()->getFlowObject($workItemContext->getActivityId()) instanceof ActivityInterface);

        $activity = $workItemContext->getProcessContext()->getWorkflow()->getFlowObject($workItemContext->getActivityId()); /* @var $activity ActivityInterface */
        if ($activity->isAllocatable()) {
            $this->allocateWorkItem($workItemContext);
            $nextWorkItemContext = new WorkItemContext($workItemContext->getParticipant());
            $nextWorkItemContext->setActivityId($workItemContext->getActivityId());
            $nextWorkItemContext->setProcessContext($workItemContext->getProcessContext());

            return $this->executeWorkItem($nextWorkItemContext);
        } elseif ($activity->isStartable()) {
            $this->startWorkItem($workItemContext);
            $nextWorkItemContext = new WorkItemContext($workItemContext->getParticipant());
            $nextWorkItemContext->setActivityId($workItemContext->getActivityId());
            $nextWorkItemContext->setProcessContext($workItemContext->getProcessContext());

            return $this->executeWorkItem($nextWorkItemContext);
        } elseif ($activity->isCompletable()) {
            $this->completeWorkItem($workItemContext);
        } else {
            throw new UnexpectedActivityStateException(sprintf('The current work item of the activity "%s" is not executable.', $activity->getId()));
        }
    }

    /**
     * @return int|string|WorkflowContextInterface
     *
     * @since 1.1.0
     */
    public function getWorkflowContext()
    {
        return $this->workflowContext;
    }

    /**
     * @return Workflow
     *
     * @throws WorkflowNotFoundException
     */
    private function createWorkflow()
    {
        $workflowId = $this->workflowContext instanceof WorkflowContextInterface ? $this->workflowContext->getWorkflowId() : $this->workflowContext;
        $workflow = $this->workflowRepository->findById($workflowId);
        if ($workflow === null) {
            throw new WorkflowNotFoundException(sprintf('The workflow "%s" is not found.', $workflowId));
        }

        return $workflow;
    }

    /**
     * @param Workflow $workflow
     *
     * @return Workflow
     *
     * @since 1.2.0
     */
    private function configureWorkflow(Workflow $workflow)
    {
        if ($this->expressionLanguage !== null) {
            $workflow->setExpressionLanguage($this->expressionLanguage);
        }

        if ($this->operationRunner !== null) {
            $workflow->setOperationRunner($this->operationRunner);
        }

        return $workflow;
    }
}
