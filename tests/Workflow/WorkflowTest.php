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
use PHPMentors\Workflower\Workflow\Activity\WorkItem;
use PHPMentors\Workflower\Workflow\Activity\WorkItemInterface;
use PHPMentors\Workflower\Workflow\Operation\OperationalInterface;
use PHPMentors\Workflower\Workflow\Operation\OperationRunnerInterface;
use PHPMentors\Workflower\Workflow\Participant\ParticipantInterface;
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
    protected function setUp()
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
        $participant = $this->createMock(ParticipantInterface::class);
        $participant->method('hasRole')->willReturn(true);

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
        $participant = $this->createMock(ParticipantInterface::class);
        $participant->method('hasRole')->willReturn(true);

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
        $participant = $this->createMock(ParticipantInterface::class);
        $participant->method('hasRole')->willReturn(true);

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
     * @param bool   $rejected
     * @param string $nextFlowObjectId
     *
     * @test
     * @dataProvider completeActivityOnConditionalSequenceFlowsData
     */
    public function completeActivityOnConditionalSequenceFlow($rejected, $nextFlowObjectId)
    {
        $participant = $this->createMock(ParticipantInterface::class);
        $participant->method('hasRole')->willReturn(true);

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
     * @param bool   $rejected
     * @param string $nextFlowObjectId
     *
     * @test
     * @dataProvider selectSequenceFlowOnExclusiveGatewayData
     */
    public function selectSequenceFlowOnExclusiveGateway($rejected, $nextFlowObjectId)
    {
        $participant = $this->createMock(ParticipantInterface::class);
        $participant->method('hasRole')->willReturn(true);

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
        $participant = $this->createMock(ParticipantInterface::class);
        $participant->method('hasRole')->willReturn(true);

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
        $participant = $this->createMock(ParticipantInterface::class);
        $participant->method('hasRole')->willReturn(true);

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
        $participant = $this->createMock(ParticipantInterface::class);
        $participant->method('hasRole')->willReturn(true);

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
     *
     * @since Method available since Release 1.2.0
     */
    public function executeServiceTasks()
    {
        $participant = $this->createMock(ParticipantInterface::class);
        $participant->method('hasRole')->willReturn(true);
        $operationRunner = $this->getMockBuilder(OperationRunnerInterface::class)
            ->setMethods(['provideParticipant', 'run'])
            ->getMock();
        $operationRunner->method('provideParticipant')->willReturn($participant);
        $self = $this;
        $operationRunner->expects($this->exactly(2))->method('run')->willReturnCallback(function (OperationalInterface $operational, Workflow $workflow) use ($self) {
            static $calls = 0;

            ++$calls;
            $self->assertThat($operational, $self->isInstanceOf('PHPMentors\Workflower\Workflow\Activity\ServiceTask'));
            $self->assertThat($operational->getOperation(), $self->equalTo('phpmentors_workflower.service'.$calls));
        });

        $workflow = $this->workflowRepository->findById('ServiceTasksProcess');
        $workflow->setOperationRunner($operationRunner);
        $workflow->start($workflow->getFlowObject('Start'));

        $this->assertThat($workflow->isEnded(), $this->isTrue());
    }

    /**
     * @test
     *
     * @since Method available since Release 1.3.0
     */
    public function provideDefaultRoleForWorkflowWithoutLanes()
    {
        $participant = $this->getMockBuilder(ParticipantInterface::class)
            ->setMethods(['hasRole', 'setResource', 'getResource', 'getName', 'getId'])
            ->getMock();
        $participant->expects($this->exactly(3))->method('hasRole')->with($this->equalTo(Workflow::DEFAULT_ROLE_ID))->willReturn(true);

        $workflow = $this->workflowRepository->findById('NoLanesProcess');
        $workflow->start($workflow->getFlowObject('Start'));
        $workflow->allocateWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->startWorkItem($workflow->getCurrentFlowObject(), $participant);
        $workflow->completeWorkItem($workflow->getCurrentFlowObject(), $participant);

        $this->assertThat($workflow->isEnded(), $this->isTrue());
    }

    /**
     * @test
     *
     * @since Method available since Release 1.3.0
     */
    public function executeSendTasks()
    {
        $participant = $this->createMock(ParticipantInterface::class);
        $participant->method('hasRole')->willReturn(true);
        $operationRunner = $this->getMockBuilder(OperationRunnerInterface::class)
            ->setMethods(['provideParticipant', 'run'])
            ->getMock();
        $operationRunner->method('provideParticipant')->willReturn($participant);
        $self = $this;
        $operationRunner->expects($this->exactly(2))->method('run')->willReturnCallback(function (OperationalInterface $operational, Workflow $workflow) use ($self) {
            static $calls = 0;

            ++$calls;
            $self->assertThat($operational, $self->isInstanceOf('PHPMentors\Workflower\Workflow\Activity\SendTask'));
            $self->assertThat($operational->getOperation(), $self->equalTo('phpmentors_workflower.service'.$calls));
            $self->assertThat($operational, $self->isInstanceOf('PHPMentors\Workflower\Workflow\Resource\MessageInterface'));
            $self->assertThat($operational->getMessage(), $self->equalTo('phpmentors_workflower.message'.$calls));
        });

        $workflow = $this->workflowRepository->findById('SendTasksProcess');
        $workflow->setOperationRunner($operationRunner);
        $workflow->start($workflow->getFlowObject('Start'));

        $this->assertThat($workflow->isEnded(), $this->isTrue());
    }

    /**
     * @test
     *
     * @since Method available since Release 2.0.0
     */
    public function parallelGateway()
    {
        $participant = $this->createMock(ParticipantInterface::class);
        $participant->method('hasRole')->willReturn(true);

        $workflow = $this->workflowRepository->findById('ParallelGatewayProcess');
        $workflow->start($workflow->getFlowObject('Start'));

        $concurrentFlowObjects = ['ReceivePayment', 'ShipOrder'];
        $currentFlowObjects = $workflow->getCurrentFlowObjects();

        $this->assertThat(count($currentFlowObjects), $this->equalTo(count($concurrentFlowObjects)));

        foreach ($currentFlowObjects as $currentFlowObject) {
            $this->assertThat($concurrentFlowObjects, $this->contains($currentFlowObject->getId()));
            $this->assertThat(current($currentFlowObject->getToken())->getPreviousFlowObject()->getId(), $this->equalTo('ParallelGateway1'));

            unset($concurrentFlowObjects[array_search($currentFlowObject->getId(), $concurrentFlowObjects)]);
            $concurrentFlowObjects = array_values($concurrentFlowObjects);
        }

        $workflow->allocateWorkItem($currentFlowObjects[0], $participant);
        $workflow->startWorkItem($currentFlowObjects[0], $participant);
        $workflow->completeWorkItem($currentFlowObjects[0], $participant);

        $this->assertThat(count($workflow->getCurrentFlowObjects()), $this->equalTo(2));

        $workflow->allocateWorkItem($currentFlowObjects[1], $participant);
        $workflow->startWorkItem($currentFlowObjects[1], $participant);
        $workflow->completeWorkItem($currentFlowObjects[1], $participant);

        $this->assertThat(count($workflow->getCurrentFlowObjects()), $this->equalTo(1));
        $this->assertThat(current($workflow->getCurrentFlowObjects())->getId(), $this->equalTo('ArchiveOrder'));
        $this->assertThat(current(current($workflow->getCurrentFlowObjects())->getToken())->getPreviousFlowObject()->getId(), $this->equalTo('ParallelGateway2'));

        $activityLog = $workflow->getActivityLog();

        $this->assertThat(count($activityLog), $this->equalTo(3));

        $concurrentFlowObjects = ['ReceivePayment', 'ShipOrder'];

        $this->assertThat($concurrentFlowObjects, $this->contains($activityLog->get(0)->getActivity()->getId()));

        unset($concurrentFlowObjects[array_search($activityLog->get(0)->getActivity()->getId(), $concurrentFlowObjects)]);

        $this->assertThat($concurrentFlowObjects, $this->contains($activityLog->get(1)->getActivity()->getId()));

        $this->assertThat($activityLog->get(2)->getActivity()->getId(), $this->equalTo('ArchiveOrder'));
    }

    /**
     * @test
     *
     * "All the tokens that were generated within the Process MUST be consumed by an End Event before the Process has been completed."
     * --Business Process Model and Notation, v2.0 https://www.omg.org/spec/BPMN/2.0 p.246
     *
     * @since Method available since Release 2.0.0
     */
    public function multipleEndEvents()
    {
        $participant = $this->createMock(ParticipantInterface::class);
        $participant->method('hasRole')->willReturn(true);

        $workflow = $this->workflowRepository->findById('MultipleEndEventsProcess');
        $workflow->start($workflow->getFlowObject('Start'));
        $currentFlowObjects = $workflow->getCurrentFlowObjects();

        $this->assertThat(count($currentFlowObjects), $this->equalTo(3));

        foreach ($workflow->getCurrentFlowObjects() as $currentFlowObject) {
            if ($currentFlowObject->getId() == 'Task1') {
                $workflow->allocateWorkItem($currentFlowObject, $participant);
                $workflow->startWorkItem($currentFlowObject, $participant);
                $workflow->completeWorkItem($currentFlowObject, $participant);

                break;
            }
        }

        $this->assertThat($workflow->isActive(), $this->isTrue());
        $this->assertThat($workflow->isEnded(), $this->isFalse());

        $currentFlowObjects = $workflow->getCurrentFlowObjects();

        $this->assertThat(count($currentFlowObjects), $this->equalTo(3));

        foreach ($workflow->getCurrentFlowObjects() as $currentFlowObject) {
            if ($currentFlowObject->getId() == 'Task2') {
                $workflow->allocateWorkItem($currentFlowObject, $participant);
                $workflow->startWorkItem($currentFlowObject, $participant);
                $workflow->completeWorkItem($currentFlowObject, $participant);

                break;
            }
        }

        $this->assertThat($workflow->isActive(), $this->isTrue());
        $this->assertThat($workflow->isEnded(), $this->isFalse());

        $currentFlowObjects = $workflow->getCurrentFlowObjects();

        $this->assertThat(count($currentFlowObjects), $this->equalTo(3));

        foreach ($workflow->getCurrentFlowObjects() as $currentFlowObject) {
            if ($currentFlowObject->getId() == 'Task3') {
                $workflow->allocateWorkItem($currentFlowObject, $participant);
                $workflow->startWorkItem($currentFlowObject, $participant);
                $workflow->completeWorkItem($currentFlowObject, $participant);

                break;
            }
        }

        $this->assertThat($workflow->isActive(), $this->isFalse());
        $this->assertThat($workflow->isEnded(), $this->isTrue());
    }
}
