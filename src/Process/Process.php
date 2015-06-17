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

namespace PHPMentors\Workflower\Process;

use PHPMentors\DomainKata\Service\ServiceInterface;
use PHPMentors\Workflower\Workflow\WorkflowRepositoryInterface;

class Process implements ServiceInterface
{
    /**
     * @var ProcessContextInterface
     */
    private $processContext;

    /**
     * @var int|string
     */
    private $workflowId;

    /**
     * @var WorkflowRepositoryInterface
     */
    private $workflowRepository;

    /**
     * @param int|string                  $workflowId
     * @param WorkflowRepositoryInterface $workflowRepository
     */
    public function __construct($workflowId, WorkflowRepositoryInterface $workflowRepository)
    {
        $this->workflowId = $workflowId;
        $this->workflowRepository = $workflowRepository;
    }

    /**
     * @param ProcessContextInterface $processContext
     */
    public function setProcessContext(ProcessContextInterface $processContext)
    {
        $this->processContext = $processContext;

        if ($this->processContext->getWorkflow() === null) {
            $this->processContext->setWorkflow($this->createWorkflow());
        }
    }

    /**
     * @param int|string $startEventId
     */
    public function start($startEventId)
    {
        assert($this->processContext !== null);
        assert($this->processContext->getWorkflow() !== null);

        $this->processContext->getWorkflow()->setProcessData($this->processContext->getProcessData());
        $this->processContext->getWorkflow()->start($this->processContext->getWorkflow()->getFlowObject($startEventId));
    }

    /**
     * @param WorkItemContextInterface $workItemContext
     */
    public function allocateWorkItem(WorkItemContextInterface $workItemContext)
    {
        assert($this->processContext !== null);
        assert($this->processContext->getWorkflow() !== null);

        $this->processContext->getWorkflow()->allocateWorkItem(
            $this->processContext->getWorkflow()->getFlowObject($workItemContext->getActivityId()),
            $workItemContext->getParticipant()
        );
    }

    /**
     * @param WorkItemContextInterface $workItemContext
     */
    public function startWorkItem(WorkItemContextInterface $workItemContext)
    {
        assert($this->processContext !== null);
        assert($this->processContext->getWorkflow() !== null);

        $this->processContext->getWorkflow()->startWorkItem(
            $this->processContext->getWorkflow()->getFlowObject($workItemContext->getActivityId()),
            $workItemContext->getParticipant()
        );
    }

    /**
     * @param WorkItemContextInterface $workItemContext
     */
    public function completeWorkItem(WorkItemContextInterface $workItemContext)
    {
        assert($this->processContext !== null);
        assert($this->processContext->getWorkflow() !== null);

        $this->processContext->getWorkflow()->setProcessData($this->processContext->getProcessData());
        $this->processContext->getWorkflow()->completeWorkItem(
            $this->processContext->getWorkflow()->getFlowObject($workItemContext->getActivityId()),
            $workItemContext->getParticipant()
        );
    }

    /**
     * @return Workflow
     *
     * @throws WorkflowNotFoundException
     */
    private function createWorkflow()
    {
        $workflow = $this->workflowRepository->findById($this->workflowId);
        if ($workflow === null) {
            throw new WorkflowNotFoundException(sprintf('The workflow "%s" is not found.', $this->workflowId));
        }

        return $workflow;
    }
}
