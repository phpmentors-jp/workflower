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

use PHPMentors\Workflower\Workflow\Activity\ActivityInterface;
use PHPMentors\Workflower\Workflow\Activity\WorkItem;
use PHPMentors\Workflower\Workflow\Activity\WorkItemInterface;
use PHPMentors\Workflower\Workflow\Operation\OperationalInterface;
use PHPUnit\Framework\TestCase;

class WorkflowTest extends TestCase
{
    /**
     * @var WorkflowRepositoryInterface
     */
    protected $workflowRepository;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->workflowRepository = new WorkflowRepository();
    }

    /**
     * @test
     */
    public function isInBeforeStarting()
    {
        $workflow = $this->workflowRepository->findById('LoanRequestProcess');

        $this->assertThat($workflow->isActive(), $this->isFalse());
        $this->assertThat($workflow->isEnded(), $this->isFalse());
        $this->assertThat($workflow->getCurrentFlowObject(), $this->isNull());
        $this->assertThat($workflow->getPreviousFlowObject(), $this->isNull());
    }

    /**
     * @test
     */
    public function start()
    {
        $participant = \Phake::mock('PHPMentors\Workflower\Workflow\Participant\ParticipantInterface');
        \Phake::when($participant)->hasRole($this->anything())->thenReturn(true);

        $workflow = $this->workflowRepository->findById('LoanRequestProcess');
        $workflow->start($workflow->getFlowObject('Start'));

        $this->assertThat($workflow->isActive(), $this->isTrue());
        $this->assertThat($workflow->isEnded(), $this->isFalse());

        $currentFlowObject = $workflow->getCurrentFlowObject(); /* @var $currentFlowObject ActivityInterface */

        $this->assertThat($currentFlowObject, $this->isInstanceOf('PHPMentors\Workflower\Workflow\Activity\ActivityInterface'));
        $this->assertThat($currentFlowObject->getId(), $this->equalTo('RecordLoanApplicationInformation'));
        $this->assertThat($currentFlowObject->getCurrentState(), $this->equalTo(WorkItemInterface::STATE_CREATED));

        $previousFlowObject = $workflow->getPreviousFlowObject();

        $this->assertThat($previousFlowObject, $this->isInstanceOf('PHPMentors\Workflower\Workflow\Event\StartEvent'));
        $this->assertThat($previousFlowObject->getId(), $this->equalTo('Start'));
    }

    /**
     * @test
     */
    public function allocateWorkItem()
    {
        $participant = \Phake::mock('PHPMentors\Workflower\Workflow\Participant\ParticipantInterface');
        \Phake::when($participant)->hasRole($this->anything())->thenReturn(true);

        $workflow = $this->workflowRepository->findById('LoanRequestProcess');
        $workflow->start($workflow->getFlowObject('Start'));
        $workflow->allocateWorkItem($workflow->getCurrentFlowObject(), $participant);

        $currentFlowObject = $workflow->getCurrentFlowObject();

        $this->assertThat($currentFlowObject, $this->isInstanceOf('PHPMentors\Workflower\Workflow\Activity\ActivityInterface'));
        $this->assertThat($currentFlowObject->getId(), $this->equalTo('RecordLoanApplicationInformation'));
        $this->assertThat($currentFlowObject->getCurrentState(), $this->equalTo(WorkItemInterface::STATE_ALLOCATED));
        $this->assertThat($currentFlowObject->getParticipant(), $this->identicalTo($participant));
    }

    /**
     * @test
     */
    public function startWorkItem()
    {
        $participant = \Phake::mock('PHPMentors\Workflower\Workflow\Participant\ParticipantInterface');
        \Phake::when($participant)->hasRole($this->anything())->thenReturn(true);

        $workflow = $this->workflowRepository->findById('LoanRequestProcess');
        $workflow->start($workflow->getFlowObject('Start'));
        $workflow->allocateWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->startWorkItem($workflow->getCurrentFlowObject(), $participant);

        $currentFlowObject = $workflow->getCurrentFlowObject();

        $this->assertThat($currentFlowObject, $this->isInstanceOf('PHPMentors\Workflower\Workflow\Activity\ActivityInterface'));
        $this->assertThat($currentFlowObject->getId(), $this->equalTo('RecordLoanApplicationInformation'));
        $this->assertThat($currentFlowObject->getCurrentState(), $this->equalTo(WorkItemInterface::STATE_STARTED));
        $this->assertThat($currentFlowObject->getCreationDate(), $this->isInstanceOf('DateTime'));
        $this->assertThat($currentFlowObject->getAllocationDate(), $this->isInstanceOf('DateTime'));
        $this->assertThat($currentFlowObject->getStartDate(), $this->isInstanceOf('DateTime'));
    }

    /**
     * @test
     */
    public function completeWorkItem()
    {
        $participant = \Phake::mock('PHPMentors\Workflower\Workflow\Participant\ParticipantInterface');
        \Phake::when($participant)->hasRole($this->anything())->thenReturn(true);

        $workflow = $this->workflowRepository->findById('LoanRequestProcess');
        $workflow->start($workflow->getFlowObject('Start'));
        $workflow->allocateWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->startWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->completeWorkItem($workflow->getCurrentFlowObject(), $participant);

        $currentFlowObject = $workflow->getCurrentFlowObject();

        $this->assertThat($currentFlowObject, $this->isInstanceOf('PHPMentors\Workflower\Workflow\Activity\ActivityInterface'));
        $this->assertThat($currentFlowObject->getId(), $this->equalTo('CheckApplicantInformation'));
        $this->assertThat($currentFlowObject->getCurrentState(), $this->equalTo(WorkItemInterface::STATE_CREATED));

        $previousFlowObject = $workflow->getPreviousFlowObject();

        $this->assertThat($currentFlowObject, $this->isInstanceOf('PHPMentors\Workflower\Workflow\Activity\ActivityInterface'));
        $this->assertThat($previousFlowObject->getId(), $this->equalTo('RecordLoanApplicationInformation'));
        $this->assertThat($previousFlowObject->getCurrentState(), $this->equalTo(WorkItemInterface::STATE_ENDED));
        $this->assertThat($previousFlowObject->getEndDate(), $this->isInstanceOf('DateTime'));
        $this->assertThat($previousFlowObject->getEndParticipant(), $this->identicalTo($participant));
        $this->assertThat($previousFlowObject->getEndResult(), $this->equalTo(WorkItem::END_RESULT_COMPLETION));
    }

    /**
     * @return array
     */
    public function completeActivityOnConditionalSequenceFlowsData()
    {
        return [
            [true, 'End'],
            [false, 'LoanStudy'],
        ];
    }

    /**
     * @param  bool  $rejected
     * @param  string  $nextFlowObjectId
     *
     * @test
     * @dataProvider completeActivityOnConditionalSequenceFlowsData
     */
    public function completeActivityOnConditionalSequenceFlow($rejected, $nextFlowObjectId)
    {
        $participant = \Phake::mock('PHPMentors\Workflower\Workflow\Participant\ParticipantInterface');
        \Phake::when($participant)->hasRole($this->anything())->thenReturn(true);

        $workflow = $this->workflowRepository->findById('LoanRequestProcess');
        $workflow->setProcessData(['rejected' => false]);
        $workflow->start($workflow->getFlowObject('Start'));

        $workflow->allocateWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->startWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->completeWorkItem($workflow->getCurrentFlowObject(), $participant);

        $workflow->allocateWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->startWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->setProcessData(['rejected' => $rejected]);
        $workflow->completeWorkItem($workflow->getCurrentFlowObject(), $participant);

        $currentFlowObject = $workflow->getCurrentFlowObject();

        $this->assertThat($currentFlowObject->getId(), $this->equalTo($nextFlowObjectId));
    }

    /**
     * @return array
     */
    public function selectSequenceFlowOnExclusiveGatewayData()
    {
        return [
            [true, 'InformRejection'],
            [false, 'Disbursement'],
        ];
    }

    /**
     * @param  bool  $rejected
     * @param  string  $nextFlowObjectId
     *
     * @test
     * @dataProvider selectSequenceFlowOnExclusiveGatewayData
     */
    public function selectSequenceFlowOnExclusiveGateway($rejected, $nextFlowObjectId)
    {
        $participant = \Phake::mock('PHPMentors\Workflower\Workflow\Participant\ParticipantInterface');
        \Phake::when($participant)->hasRole($this->anything())->thenReturn(true);

        $workflow = $this->workflowRepository->findById('LoanRequestProcess');
        $workflow->setProcessData(['rejected' => false]);
        $workflow->start($workflow->getFlowObject('Start'));

        $workflow->allocateWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->startWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->completeWorkItem($workflow->getCurrentFlowObject(), $participant);

        $workflow->allocateWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->startWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->completeWorkItem($workflow->getCurrentFlowObject(), $participant);

        $workflow->allocateWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->startWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->setProcessData(['rejected' => $rejected]);
        $workflow->completeWorkItem($workflow->getCurrentFlowObject(), $participant);

        $currentFlowObject = $workflow->getCurrentFlowObject();

        $this->assertThat($currentFlowObject->getId(), $this->equalTo($nextFlowObjectId));
    }

    /**
     * @test
     */
    public function end()
    {
        $participant = \Phake::mock('PHPMentors\Workflower\Workflow\Participant\ParticipantInterface');
        \Phake::when($participant)->hasRole($this->anything())->thenReturn(true);

        $workflow = $this->workflowRepository->findById('LoanRequestProcess');
        $workflow->setProcessData(['rejected' => false]);
        $workflow->start($workflow->getFlowObject('Start'));

        $workflow->allocateWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->startWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->completeWorkItem($workflow->getCurrentFlowObject(), $participant);

        $workflow->allocateWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->startWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->completeWorkItem($workflow->getCurrentFlowObject(), $participant);

        $workflow->allocateWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->startWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->completeWorkItem($workflow->getCurrentFlowObject(), $participant);

        $workflow->allocateWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->startWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->completeWorkItem($workflow->getCurrentFlowObject(), $participant);

        $this->assertThat($workflow->isActive(), $this->isFalse());
        $this->assertThat($workflow->isEnded(), $this->isTrue());
        $this->assertThat($workflow->getStartDate(), $this->isInstanceOf('DateTime'));
        $this->assertThat($workflow->getEndDate(), $this->isInstanceOf('DateTime'));

        $currentFlowObject = $workflow->getCurrentFlowObject();

        $this->assertThat($currentFlowObject, $this->isInstanceOf('PHPMentors\Workflower\Workflow\Event\EndEvent'));
        $this->assertThat($currentFlowObject->getId(), $this->equalTo('End'));

        $previousFlowObject = $workflow->getPreviousFlowObject();

        $this->assertThat($previousFlowObject, $this->isInstanceOf('PHPMentors\Workflower\Workflow\Activity\ActivityInterface'));
        $this->assertThat($previousFlowObject->getId(), $this->equalTo('Disbursement'));
    }

    /**
     * @test
     */
    public function getActivityLog()
    {
        $participant = \Phake::mock('PHPMentors\Workflower\Workflow\Participant\ParticipantInterface');
        \Phake::when($participant)->hasRole($this->anything())->thenReturn(true);

        $workflow = $this->workflowRepository->findById('LoanRequestProcess');
        $workflow->setProcessData(['rejected' => false]);
        $workflow->start($workflow->getFlowObject('Start'));

        $workflow->allocateWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->startWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->completeWorkItem($workflow->getCurrentFlowObject(), $participant);

        $workflow->allocateWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->startWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->completeWorkItem($workflow->getCurrentFlowObject(), $participant);

        $workflow->allocateWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->startWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->completeWorkItem($workflow->getCurrentFlowObject(), $participant);

        $workflow->allocateWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->startWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->completeWorkItem($workflow->getCurrentFlowObject(), $participant);

        $activityLog = $workflow->getActivityLog();

        $this->assertThat($activityLog, $this->isInstanceOf('PHPMentors\Workflower\Workflow\ActivityLogCollection'));
        $this->assertThat(count($activityLog), $this->equalTo(4));

        $activityIds = [
            'RecordLoanApplicationInformation',
            'CheckApplicantInformation',
            'LoanStudy',
            'Disbursement',
        ];

        foreach ($activityLog as $i => $activityLogEntry) { /* @var $activityLogEntry ActivityLog */
            $this->assertThat($activityLogEntry->getActivity()->getId(), $this->equalTo($activityIds[$i]));
            $this->assertThat($activityLogEntry->getWorkItem()->getCurrentState(), $this->equalTo(WorkItemInterface::STATE_ENDED));
            $this->assertThat($activityLogEntry->getWorkItem()->getParticipant(), $this->identicalTo($participant));
            $this->assertThat($activityLogEntry->getWorkItem()->getCreationDate(), $this->isInstanceOf('DateTime'));
            $this->assertThat($activityLogEntry->getWorkItem()->getAllocationDate(), $this->isInstanceOf('DateTime'));
            $this->assertThat($activityLogEntry->getWorkItem()->getStartDate(), $this->isInstanceOf('DateTime'));
            $this->assertThat($activityLogEntry->getWorkItem()->getEndDate(), $this->isInstanceOf('DateTime'));
            $this->assertThat($activityLogEntry->getWorkItem()->getEndParticipant(), $this->identicalTo($participant));
            $this->assertThat($activityLogEntry->getWorkItem()->getEndResult(), $this->equalTo(WorkItemInterface::END_RESULT_COMPLETION));
        }
    }

    /**
     * @test
     */
    public function getActivityLogWithMultipleExecutionsOfSameActivity()
    {
        $participant = \Phake::mock('PHPMentors\Workflower\Workflow\Participant\ParticipantInterface');
        \Phake::when($participant)->hasRole($this->anything())->thenReturn(true);

        $workflow = $this->workflowRepository->findById('MultipleWorkItemsProcess');
        $workflow->setProcessData(['satisfied' => false]);
        $workflow->start($workflow->getFlowObject('Start'));

        $workflow->allocateWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->startWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->completeWorkItem($workflow->getCurrentFlowObject(), $participant);

        $workflow->allocateWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->startWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->completeWorkItem($workflow->getCurrentFlowObject(), $participant);

        $workflow->allocateWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->startWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->completeWorkItem($workflow->getCurrentFlowObject(), $participant);

        $workflow->allocateWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->startWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->setProcessData(['satisfied' => true]);
        $workflow->completeWorkItem($workflow->getCurrentFlowObject(), $participant);

        $activityLog = $workflow->getActivityLog();

        $this->assertThat($activityLog, $this->isInstanceOf('PHPMentors\Workflower\Workflow\ActivityLogCollection'));
        $this->assertThat(count($activityLog), $this->equalTo(4));

        $this->assertThat($activityLog->get(0)->getActivity()->getId(), $this->equalTo('Task1'));
        $this->assertThat($activityLog->get(1)->getActivity()->getId(), $this->equalTo('Task2'));
        $this->assertThat($activityLog->get(2)->getActivity()->getId(), $this->equalTo('Task1'));
        $this->assertThat($activityLog->get(3)->getActivity()->getId(), $this->equalTo('Task2'));

        $this->assertThat($activityLog->get(0)->getActivity(), $this->identicalTo($activityLog->get(2)->getActivity()));
        $this->assertThat($activityLog->get(0)->getWorkItem(), $this->logicalNot($this->identicalTo($activityLog->get(2)->getWorkItem())));

        $this->assertThat($activityLog->get(1)->getActivity(), $this->identicalTo($activityLog->get(3)->getActivity()));
        $this->assertThat($activityLog->get(1)->getWorkItem(), $this->logicalNot($this->identicalTo($activityLog->get(3)->getWorkItem())));
    }

    /**
     * @test
     */
    public function injectExpressionLanguage()
    {
        $expressionLanguage = \Phake::mock('Symfony\Component\ExpressionLanguage\ExpressionLanguage');
        \Phake::when($expressionLanguage)->evaluate($this->equalTo('rejected == true'))->thenReturn(true);

        $workflow = $this->workflowRepository->findById('LoanRequestProcess');
        $workflow->setExpressionLanguage($expressionLanguage);

        $workflow->setProcessData(['rejected' => false]);
        $workflow->start($workflow->getFlowObject('Start'));

        // if not will get an error: this test did not perform any assertions
        $this->assertTrue(true);
    }

    /**
     * @test
     *
     * @since Method available since Release 1.2.0
     */
    public function executeServiceTasks()
    {
        $participant = \Phake::mock('PHPMentors\Workflower\Workflow\Participant\ParticipantInterface');
        \Phake::when($participant)->hasRole($this->anything())->thenReturn(true);
        $operationRunner = \Phake::mock('PHPMentors\Workflower\Workflow\Operation\OperationRunnerInterface');
        \Phake::when($operationRunner)->provideParticipant($this->anything(), $this->anything())->thenReturn($participant);
        $self = $this;
        \Phake::when($operationRunner)->run($this->anything(), $this->anything())->thenReturnCallback(function (OperationalInterface $operational, Workflow $workflow) use ($self) {
            static $calls = 0;

            $calls++;
            $self->assertThat($operational, $self->isInstanceOf('PHPMentors\Workflower\Workflow\Activity\ServiceTask'));
            $self->assertThat($operational->getOperation(), $self->equalTo('phpmentors_workflower.service'.$calls));
        });

        $workflow = $this->workflowRepository->findById('ServiceTasksProcess');
        $workflow->setOperationRunner($operationRunner);
        $workflow->start($workflow->getFlowObject('Start'));

        $this->assertThat($workflow->isEnded(), $this->isTrue());

        \Phake::verify($operationRunner, \Phake::times(2))->run($this->anything(), $this->anything());
    }

    /**
     * @test
     *
     * @since Method available since Release 1.3.0
     */
    public function provideDefaultRoleForWorkflowWithoutLanes()
    {
        $participant = \Phake::mock('PHPMentors\Workflower\Workflow\Participant\ParticipantInterface');
        \Phake::when($participant)->hasRole($this->anything())->thenReturn(true);

        $workflow = $this->workflowRepository->findById('NoLanesProcess');
        $workflow->start($workflow->getFlowObject('Start'));
        $workflow->allocateWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->startWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->completeWorkItem($workflow->getCurrentFlowObject(), $participant);

        $this->assertThat($workflow->isEnded(), $this->isTrue());

        \Phake::verify($participant, \Phake::times(3))->hasRole($this->equalTo(Workflow::DEFAULT_ROLE_ID));
    }

    /**
     * @test
     *
     * @since Method available since Release 1.3.0
     */
    public function executeSendTasks()
    {
        $participant = \Phake::mock('PHPMentors\Workflower\Workflow\Participant\ParticipantInterface');
        \Phake::when($participant)->hasRole($this->anything())->thenReturn(true);
        $operationRunner = \Phake::mock('PHPMentors\Workflower\Workflow\Operation\OperationRunnerInterface');
        \Phake::when($operationRunner)->provideParticipant($this->anything(), $this->anything())->thenReturn($participant);
        $self = $this;
        \Phake::when($operationRunner)->run($this->anything(), $this->anything())->thenReturnCallback(function (OperationalInterface $operational, Workflow $workflow) use ($self) {
            static $calls = 0;

            $calls++;
            $self->assertThat($operational, $self->isInstanceOf('PHPMentors\Workflower\Workflow\Activity\SendTask'));
            $self->assertThat($operational->getOperation(), $self->equalTo('phpmentors_workflower.service'.$calls));
            $self->assertThat($operational, $self->isInstanceOf('PHPMentors\Workflower\Workflow\Resource\MessageInterface'));
            $self->assertThat($operational->getMessage(), $self->equalTo('phpmentors_workflower.message'.$calls));
        });

        $workflow = $this->workflowRepository->findById('SendTasksProcess');
        $workflow->setOperationRunner($operationRunner);
        $workflow->start($workflow->getFlowObject('Start'));

        $this->assertThat($workflow->isEnded(), $this->isTrue());

        \Phake::verify($operationRunner, \Phake::times(2))->run($this->anything(), $this->anything());
    }
}
