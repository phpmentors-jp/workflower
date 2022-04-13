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
use PHPMentors\Workflower\Workflow\Operation\OperationRunnerInterface;
use PHPMentors\Workflower\Workflow\Participant\ParticipantInterface;
use PHPMentors\Workflower\Workflow\Provider\DataProviderInterface;
use PHPUnit\Framework\TestCase;

class ProcessInstanceTest extends TestCase
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
        $workflow = $this->workflowRepository->findById('LoanRequestProcess');
        $workflow->start($workflow->getFlowObject('Start'));

        $this->assertThat($workflow->isActive(), $this->isTrue());
        $this->assertThat($workflow->isEnded(), $this->isFalse());

        $currentFlowObject = $workflow->getCurrentFlowObject(); /* @var $currentFlowObject ActivityInterface */

        $this->assertThat($currentFlowObject, $this->isInstanceOf('PHPMentors\Workflower\Workflow\Activity\ActivityInterface'));
        $this->assertThat($currentFlowObject->getId(), $this->equalTo('RecordLoanApplicationInformation'));
        $this->assertThat($currentFlowObject->getState(), $this->equalTo(ActivityInterface::STATE_ACTIVE));

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
        $workflow->allocateWorkItem($workflow->getCurrentFlowObject()->getWorkItems()->getAt(0), $participant);

        $currentFlowObject = $workflow->getCurrentFlowObject();
        $workitem = $currentFlowObject->getWorkItems()->getAt(0);

        $this->assertThat($currentFlowObject, $this->isInstanceOf('PHPMentors\Workflower\Workflow\Activity\ActivityInterface'));
        $this->assertThat($currentFlowObject->getId(), $this->equalTo('RecordLoanApplicationInformation'));
        $this->assertThat($currentFlowObject->getState(), $this->equalTo(ActivityInterface::STATE_ACTIVE));
        $this->assertThat($workitem->getParticipant(), $this->identicalTo($participant));
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
        $workitem = $workflow->getCurrentFlowObject()->getWorkItems()->getAt(0);
        $workflow->allocateWorkItem($workitem, $participant);
        $workflow->startWorkItem($workitem, $participant);

        $currentFlowObject = $workflow->getCurrentFlowObject();
        $workitem = $workflow->getCurrentFlowObject()->getWorkItems()->getAt(0);

        $this->assertThat($currentFlowObject, $this->isInstanceOf('PHPMentors\Workflower\Workflow\Activity\ActivityInterface'));
        $this->assertThat($currentFlowObject->getId(), $this->equalTo('RecordLoanApplicationInformation'));
        $this->assertThat($currentFlowObject->getState(), $this->equalTo(ActivityInterface::STATE_ACTIVE));
        $this->assertThat($workitem->getCreationDate(), $this->isInstanceOf('DateTime'));
        $this->assertThat($workitem->getAllocationDate(), $this->isInstanceOf('DateTime'));
        $this->assertThat($workitem->getStartDate(), $this->isInstanceOf('DateTime'));
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
        $workitem = $workflow->getCurrentFlowObject()->getWorkItems()->getAt(0);
        $workflow->allocateWorkItem($workitem, $participant);
        $workflow->startWorkItem($workitem, $participant);
        $workflow->completeWorkItem($workitem, $participant);

        $currentFlowObject = $workflow->getCurrentFlowObject();

        $this->assertThat($currentFlowObject, $this->isInstanceOf('PHPMentors\Workflower\Workflow\Activity\ActivityInterface'));
        $this->assertThat($currentFlowObject->getId(), $this->equalTo('CheckApplicantInformation'));
        $this->assertThat($currentFlowObject->getState(), $this->equalTo(ActivityInterface::STATE_ACTIVE));

        $previousFlowObject = $workflow->getPreviousFlowObject();
        $workitem = $previousFlowObject->getWorkItems()->getAt(0);

        $this->assertThat($currentFlowObject, $this->isInstanceOf('PHPMentors\Workflower\Workflow\Activity\ActivityInterface'));
        $this->assertThat($previousFlowObject->getId(), $this->equalTo('RecordLoanApplicationInformation'));
        $this->assertThat($previousFlowObject->getState(), $this->equalTo(ActivityInterface::STATE_CLOSED));
        $this->assertThat($workitem->getState(), $this->equalTo(WorkItemInterface::STATE_ENDED));
        $this->assertThat($workitem->getEndDate(), $this->isInstanceOf('DateTime'));
        $this->assertThat($workitem->getEndParticipant(), $this->identicalTo($participant));
        $this->assertThat($workitem->getEndResult(), $this->equalTo(WorkItem::END_RESULT_COMPLETION));
    }

    /**
     * @return array
     */
    public function completeActivityOnConditionalSequenceFlowsData()
    {
        return [
            [true, null],
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

        $workitem = $workflow->getCurrentFlowObject()->getWorkItems()->getAt(0);
        $workflow->allocateWorkItem($workitem, $participant);
        $workflow->startWorkItem($workitem, $participant);
        $workflow->completeWorkItem($workitem, $participant);

        $workitem = $workflow->getCurrentFlowObject()->getWorkItems()->getAt(0);
        $workflow->allocateWorkItem($workitem, $participant);
        $workflow->startWorkItem($workitem, $participant);
        $workflow->setProcessData(['rejected' => $rejected]);
        $workflow->completeWorkItem($workitem, $participant);

        $currentFlowObject = $workflow->getCurrentFlowObject();

        $this->assertThat($currentFlowObject ? $currentFlowObject->getId() : $currentFlowObject, $nextFlowObjectId == null ? $this->isNull() : $this->equalTo($nextFlowObjectId));
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

        $workitem = $workflow->getCurrentFlowObject()->getWorkItems()->getAt(0);
        $workflow->allocateWorkItem($workitem, $participant);
        $workflow->startWorkItem($workitem, $participant);
        $workflow->completeWorkItem($workitem, $participant);

        $workitem = $workflow->getCurrentFlowObject()->getWorkItems()->getAt(0);
        $workflow->allocateWorkItem($workitem, $participant);
        $workflow->startWorkItem($workitem, $participant);
        $workflow->completeWorkItem($workitem, $participant);

        $workitem = $workflow->getCurrentFlowObject()->getWorkItems()->getAt(0);
        $workflow->allocateWorkItem($workitem, $participant);
        $workflow->startWorkItem($workitem, $participant);
        $workflow->setProcessData(['rejected' => $rejected]);
        $workflow->completeWorkItem($workitem, $participant);

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

        $workitem = $workflow->getCurrentFlowObject()->getWorkItems()->getAt(0);
        $workflow->allocateWorkItem($workitem, $participant);
        $workflow->startWorkItem($workitem, $participant);
        $workflow->completeWorkItem($workitem, $participant);

        $workitem = $workflow->getCurrentFlowObject()->getWorkItems()->getAt(0);
        $workflow->allocateWorkItem($workitem, $participant);
        $workflow->startWorkItem($workitem, $participant);
        $workflow->completeWorkItem($workitem, $participant);

        $workitem = $workflow->getCurrentFlowObject()->getWorkItems()->getAt(0);
        $workflow->allocateWorkItem($workitem, $participant);
        $workflow->startWorkItem($workitem, $participant);
        $workflow->completeWorkItem($workitem, $participant);

        $workitem = $workflow->getCurrentFlowObject()->getWorkItems()->getAt(0);
        $workflow->allocateWorkItem($workitem, $participant);
        $workflow->startWorkItem($workitem, $participant);
        $workflow->completeWorkItem($workitem, $participant);

        $this->assertThat($workflow->isActive(), $this->isFalse());
        $this->assertThat($workflow->isEnded(), $this->isTrue());
        $this->assertThat($workflow->getStartDate(), $this->isInstanceOf('DateTime'));
        $this->assertThat($workflow->getEndDate(), $this->isInstanceOf('DateTime'));

        $currentFlowObject = $workflow->getCurrentFlowObject();

        $this->assertThat($currentFlowObject, $this->isNull());
        $this->assertThat($workflow->isEnded(), $this->isTrue());
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

        $workitem = $workflow->getCurrentFlowObject()->getWorkItems()->getAt(0);
        $workflow->allocateWorkItem($workitem, $participant);
        $workflow->startWorkItem($workitem, $participant);
        $workflow->completeWorkItem($workitem, $participant);

        $workitem = $workflow->getCurrentFlowObject()->getWorkItems()->getAt(0);
        $workflow->allocateWorkItem($workitem, $participant);
        $workflow->startWorkItem($workitem, $participant);
        $workflow->completeWorkItem($workitem, $participant);

        $workitem = $workflow->getCurrentFlowObject()->getWorkItems()->getAt(0);
        $workflow->allocateWorkItem($workitem, $participant);
        $workflow->startWorkItem($workitem, $participant);
        $workflow->completeWorkItem($workitem, $participant);

        $workitem = $workflow->getCurrentFlowObject()->getWorkItems()->getAt(0);
        $workflow->allocateWorkItem($workitem, $participant);
        $workflow->startWorkItem($workitem, $participant);
        $workflow->completeWorkItem($workitem, $participant);

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
            $this->assertThat($activityLogEntry->getWorkItem()->getState(), $this->equalTo(WorkItemInterface::STATE_ENDED));
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

        $workitem = $workflow->getCurrentFlowObject()->getWorkItems()->getAt(0);
        $workflow->allocateWorkItem($workitem, $participant);
        $workflow->startWorkItem($workitem, $participant);
        $workflow->completeWorkItem($workitem, $participant);

        $workitem = $workflow->getCurrentFlowObject()->getWorkItems()->getAt(0);
        $workflow->allocateWorkItem($workitem, $participant);
        $workflow->startWorkItem($workitem, $participant);
        $workflow->completeWorkItem($workitem, $participant);

        $workitem = $workflow->getCurrentFlowObject()->getWorkItems()->getAt(1);
        $workflow->allocateWorkItem($workitem, $participant);
        $workflow->startWorkItem($workitem, $participant);
        $workflow->completeWorkItem($workitem, $participant);

        $workitem = $workflow->getCurrentFlowObject()->getWorkItems()->getAt(1);
        $workflow->allocateWorkItem($workitem, $participant);
        $workflow->startWorkItem($workitem, $participant);
        $workflow->setProcessData(['satisfied' => true]);
        $workflow->completeWorkItem($workitem, $participant);

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
        $operationRunner->expects($this->exactly(2))->method('run')->willReturnCallback(function (WorkItemInterface $workItem) use ($self) {
            static $calls = 0;

            ++$calls;
            $operational = $workItem->getActivity();
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
        $participant->expects($this->exactly(3))->method('hasRole')->with($this->equalTo(ProcessInstance::DEFAULT_ROLE_ID))->willReturn(true);

        $workflow = $this->workflowRepository->findById('NoLanesProcess');
        $workflow->start($workflow->getFlowObject('Start'));

        $workitem = $workflow->getCurrentFlowObject()->getWorkItems()->getAt(0);
        $workflow->allocateWorkItem($workitem, $participant);
        $workflow->startWorkItem($workitem, $participant);
        $workflow->completeWorkItem($workitem, $participant);

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
        $operationRunner->expects($this->exactly(2))->method('run')->willReturnCallback(function (WorkItemInterface $workItem) use ($self) {
            static $calls = 0;

            ++$calls;
            $operational = $workItem->getActivity();
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
            $this->assertThat($concurrentFlowObjects, $this->containsIdentical($currentFlowObject->getId()));
            $this->assertThat(current($currentFlowObject->getToken())->getPreviousFlowObject()->getId(), $this->equalTo('ParallelGateway1'));

            unset($concurrentFlowObjects[array_search($currentFlowObject->getId(), $concurrentFlowObjects)]);
            $concurrentFlowObjects = array_values($concurrentFlowObjects);
        }

        $workitem = $currentFlowObjects[0]->getWorkItems()->getAt(0);
        $workflow->allocateWorkItem($workitem, $participant);
        $workflow->startWorkItem($workitem, $participant);
        $workflow->completeWorkItem($workitem, $participant);

        $this->assertThat(count($workflow->getCurrentFlowObjects()), $this->equalTo(2));

        $workitem = $currentFlowObjects[1]->getWorkItems()->getAt(0);
        $workflow->allocateWorkItem($workitem, $participant);
        $workflow->startWorkItem($workitem, $participant);
        $workflow->completeWorkItem($workitem, $participant);

        $this->assertThat(count($workflow->getCurrentFlowObjects()), $this->equalTo(1));
        $this->assertThat(current($workflow->getCurrentFlowObjects())->getId(), $this->equalTo('ArchiveOrder'));
        $this->assertThat(current(current($workflow->getCurrentFlowObjects())->getToken())->getPreviousFlowObject()->getId(), $this->equalTo('ParallelGateway2'));

        $activityLog = $workflow->getActivityLog();

        $this->assertThat(count($activityLog), $this->equalTo(3));

        $concurrentFlowObjects = ['ReceivePayment', 'ShipOrder'];

        $this->assertThat($concurrentFlowObjects, $this->containsIdentical($activityLog->get(0)->getActivity()->getId()));

        unset($concurrentFlowObjects[array_search($activityLog->get(0)->getActivity()->getId(), $concurrentFlowObjects)]);

        $this->assertThat($concurrentFlowObjects, $this->containsIdentical($activityLog->get(1)->getActivity()->getId()));

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
                $workitem = $currentFlowObject->getWorkItems()->getAt(0);
                $workflow->allocateWorkItem($workitem, $participant);
                $workflow->startWorkItem($workitem, $participant);
                $workflow->completeWorkItem($workitem, $participant);

                break;
            }
        }

        $this->assertThat($workflow->isActive(), $this->isTrue());
        $this->assertThat($workflow->isEnded(), $this->isFalse());

        $currentFlowObjects = $workflow->getCurrentFlowObjects();

        $this->assertThat(count($currentFlowObjects), $this->equalTo(2));

        foreach ($workflow->getCurrentFlowObjects() as $currentFlowObject) {
            if ($currentFlowObject->getId() == 'Task2') {
                $workitem = $currentFlowObject->getWorkItems()->getAt(0);
                $workflow->allocateWorkItem($workitem, $participant);
                $workflow->startWorkItem($workitem, $participant);
                $workflow->completeWorkItem($workitem, $participant);

                break;
            }
        }

        $this->assertThat($workflow->isActive(), $this->isTrue());
        $this->assertThat($workflow->isEnded(), $this->isFalse());

        $currentFlowObjects = $workflow->getCurrentFlowObjects();

        $this->assertThat(count($currentFlowObjects), $this->equalTo(1));

        foreach ($workflow->getCurrentFlowObjects() as $currentFlowObject) {
            if ($currentFlowObject->getId() == 'Task3') {
                $workitem = $currentFlowObject->getWorkItems()->getAt(0);
                $workflow->allocateWorkItem($workitem, $participant);
                $workflow->startWorkItem($workitem, $participant);
                $workflow->completeWorkItem($workitem, $participant);

                break;
            }
        }

        $this->assertThat($workflow->isActive(), $this->isFalse());
        $this->assertThat($workflow->isEnded(), $this->isTrue());
    }

    /**
     * @test
     *
     * A sequence flow can have a condition defined on it. When a BPMN 2.0 activity is left,
     * the default behavior is to evaluate the conditions on the outgoing sequence flows.
     * When a condition evaluates to ‘true’, that outgoing sequence flow is selected.
     * When multiple sequence flows are selected that way, multiple executions will
     * be generated and the process is continued in a parallel way.
     *
     * Correct usage of conditional sequence flows:
     * https://www.modeling-guidelines.org/guidelines/correct-usage-of-conditional-and-default-flows/
     *
     * @since Method available since Release 2.0.0
     */
    public function parallelSequenceFlowsTrueCondition()
    {
        $participant = $this->createMock(ParticipantInterface::class);
        $participant->method('hasRole')->willReturn(true);

        $workflow = $this->workflowRepository->findById('ParallelSequenceFlows');
        $workflow->setProcessData(['stock' => 4]);
        $workflow->start($workflow->getFlowObject('Start'));
        $currentFlowObjects = $workflow->getCurrentFlowObjects();

        $this->assertThat(count($currentFlowObjects), $this->equalTo(1));
        $this->assertThat($workflow->isActive(), $this->isTrue());
        $this->assertThat($workflow->isEnded(), $this->isFalse());

        $workitem = $currentFlowObjects[0]->getWorkItems()->getAt(0);
        $workflow->allocateWorkItem($workitem, $participant);
        $workflow->startWorkItem($workitem, $participant);
        $workflow->completeWorkItem($workitem, $participant);

        $currentFlowObjects = $workflow->getCurrentFlowObjects();

        $this->assertThat(count($currentFlowObjects), $this->equalTo(2));
        $this->assertThat($workflow->isActive(), $this->isTrue());
        $this->assertThat($workflow->isEnded(), $this->isFalse());
        $this->assertEquals('Task 3', $currentFlowObjects[0]->getName());
        $this->assertEquals('Task 2', $currentFlowObjects[1]->getName());

        $workitem = $currentFlowObjects[0]->getWorkItems()->getAt(0);
        $workflow->allocateWorkItem($workitem, $participant);
        $workflow->startWorkItem($workitem, $participant);
        $workflow->completeWorkItem($workitem, $participant);

        $workitem = $currentFlowObjects[1]->getWorkItems()->getAt(0);
        $workflow->allocateWorkItem($workitem, $participant);
        $workflow->startWorkItem($workitem, $participant);
        $workflow->completeWorkItem($workitem, $participant);

        $this->assertThat($workflow->isActive(), $this->isFalse());
        $this->assertThat($workflow->isEnded(), $this->isTrue());
    }

    /**
     * @test
     *
     * A sequence flow can have a condition defined on it. When a BPMN 2.0 activity is left,
     * the default behavior is to evaluate the conditions on the outgoing sequence flows.
     * When a condition evaluates to ‘true’, that outgoing sequence flow is selected.
     * When multiple sequence flows are selected that way, multiple executions will
     * be generated and the process is continued in a parallel way.
     *
     * Correct usage of conditional sequence flows:
     * https://www.modeling-guidelines.org/guidelines/correct-usage-of-conditional-and-default-flows/
     *
     * @since Method available since Release 2.0.0
     */
    public function parallelSequenceFlowsFalseCondition()
    {
        $participant = $this->createMock(ParticipantInterface::class);
        $participant->method('hasRole')->willReturn(true);

        $workflow = $this->workflowRepository->findById('ParallelSequenceFlows');
        $workflow->setProcessData(['stock' => 14]);
        $workflow->start($workflow->getFlowObject('Start'));
        $currentFlowObjects = $workflow->getCurrentFlowObjects();

        $this->assertThat(count($currentFlowObjects), $this->equalTo(1));
        $this->assertThat($workflow->isActive(), $this->isTrue());
        $this->assertThat($workflow->isEnded(), $this->isFalse());

        $workitem = $currentFlowObjects[0]->getWorkItems()->getAt(0);
        $workflow->allocateWorkItem($workitem, $participant);
        $workflow->startWorkItem($workitem, $participant);
        $workflow->completeWorkItem($workitem, $participant);

        $currentFlowObjects = $workflow->getCurrentFlowObjects();

        $this->assertThat(count($currentFlowObjects), $this->equalTo(1));
        $this->assertThat($workflow->isActive(), $this->isTrue());
        $this->assertThat($workflow->isEnded(), $this->isFalse());
        $this->assertEquals('Task 2', $currentFlowObjects[0]->getName());

        $workitem = $currentFlowObjects[0]->getWorkItems()->getAt(0);
        $workflow->allocateWorkItem($workitem, $participant);
        $workflow->startWorkItem($workitem, $participant);
        $workflow->completeWorkItem($workitem, $participant);

        $this->assertThat($workflow->isActive(), $this->isFalse());
        $this->assertThat($workflow->isEnded(), $this->isTrue());
    }

    /**
     * @test
     *
     * @since Method available since Release 2.0.0
     */
    public function executeParallelUserTasks()
    {
        $participant = $this->createMock(ParticipantInterface::class);
        $participant->method('hasRole')->willReturn(true);

        $parallelData = [
            ['test' => 1],
            ['test' => 2],
            ['test' => 3],
            ['test' => 4],
        ];
        $dataProvider = $this->getMockBuilder(DataProviderInterface::class)
            ->setMethods(['getParallelInstancesData', 'getSequentialInstanceData', 'getSingleInstanceData', 'mergeInstancesData'])
            ->getMock();
        $dataProvider->method('getParallelInstancesData')->willReturn($parallelData);

        $workflow = $this->workflowRepository->findById('ParallelUserTasks');
        $workflow->setDataProvider($dataProvider);
        $workflow->start($workflow->getFlowObject('Start'));
        $currentFlowObject = $workflow->getCurrentFlowObject();

        $this->assertThat($workflow->isActive(), $this->isTrue());
        $this->assertThat($workflow->isEnded(), $this->isFalse());
        $this->assertThat($currentFlowObject->getWorkItems()->count(), $this->equalTo(4));

        $workitem = $currentFlowObject->getWorkItems()->getAt(0);
        $workflow->allocateWorkItem($workitem, $participant);
        $workflow->startWorkItem($workitem, $participant);
        $workflow->completeWorkItem($workitem, $participant);

        $workitem = $currentFlowObject->getWorkItems()->getAt(2);
        $workflow->allocateWorkItem($workitem, $participant);
        $workflow->startWorkItem($workitem, $participant);
        $workflow->completeWorkItem($workitem, $participant);

        $userTask = $workflow->getFlowObject('Task_1');

        $this->assertThat($workflow->isEnded(), $this->isTrue());
        $this->assertThat($userTask->getWorkItems()->getAt(1)->isCancelled(), $this->isTrue());
        $this->assertThat($userTask->getWorkItems()->getAt(3)->isCancelled(), $this->isTrue());
    }

    /**
     * @test
     *
     * @since Method available since Release 2.0.0
     */
    public function executeSequentialUserTasks()
    {
        $participant = $this->createMock(ParticipantInterface::class);
        $participant->method('hasRole')->willReturn(true);

        $counter = 0;
        $dataProvider = $this->getMockBuilder(DataProviderInterface::class)
            ->setMethods(['getParallelInstancesData', 'getSequentialInstanceData', 'getSingleInstanceData', 'mergeInstancesData'])
            ->getMock();
        $dataProvider->method('getSequentialInstanceData')->willReturn(['test' => ++$counter]);

        $workflow = $this->workflowRepository->findById('SequentialUserTasks');
        $workflow->setDataProvider($dataProvider);
        $workflow->start($workflow->getFlowObject('Start'));
        $currentFlowObject = $workflow->getCurrentFlowObject();

        $this->assertThat($workflow->isActive(), $this->isTrue());
        $this->assertThat($workflow->isEnded(), $this->isFalse());
        $this->assertThat($currentFlowObject->getWorkItems()->count(), $this->equalTo(1));

        $workitem = $currentFlowObject->getWorkItems()->getAt(0);
        $workflow->allocateWorkItem($workitem, $participant);
        $workflow->startWorkItem($workitem, $participant);
        $workflow->completeWorkItem($workitem, $participant);

        $this->assertThat($currentFlowObject->getWorkItems()->count(), $this->equalTo(2));

        $workitem = $currentFlowObject->getWorkItems()->getAt(1);
        $workflow->allocateWorkItem($workitem, $participant);
        $workflow->startWorkItem($workitem, $participant);
        $workflow->completeWorkItem($workitem, $participant);

        $this->assertThat($workflow->isEnded(), $this->isTrue());
    }

    /**
     * @test
     *
     * @since Method available since Release 2.0.0
     */
    public function processSerialization()
    {
        $workflow = $this->workflowRepository->findById('SequentialUserTasks');
        $serialized = $workflow->serialize();

        $restore = new ProcessInstance('test', 'Test');
        $restore->unserialize($serialized);

        $this->assertThat($serialized, $this->equalTo($restore->serialize()));
    }

    /**
     * @test
     *
     * @since Method available since Release 2.0.0
     */
    public function executeSubProcessTask()
    {
        $participant = $this->createMock(ParticipantInterface::class);
        $participant->method('hasRole')->willReturn(true);

        $workflow = $this->workflowRepository->findById('SubProcess');
        $workflow->start($workflow->getFlowObject('Start'));
        $currentFlowObjects = $workflow->getCurrentFlowObjects();

        $this->assertThat($workflow->isActive(), $this->isTrue());
        $this->assertThat($workflow->isEnded(), $this->isFalse());
        $this->assertThat($currentFlowObjects[0]->getWorkItems()->count(), $this->equalTo(1));
        $this->assertThat($currentFlowObjects[1]->getWorkItems()->count(), $this->equalTo(1));

        $workitem = $currentFlowObjects[0]->getWorkItems()->getAt(0);
        $workflow->allocateWorkItem($workitem, $participant);
        $workflow->startWorkItem($workitem, $participant);
        $workflow->completeWorkItem($workitem, $participant);

        $processInstance = $currentFlowObjects[1]->getWorkItems()->getAt(0);
        $currentFlowObject = $processInstance->getCurrentFlowObject();
        $workitem = $currentFlowObject->getWorkItems()->getAt(0);
        $processInstance->allocateWorkItem($workitem, $participant);
        $processInstance->startWorkItem($workitem, $participant);
        $processInstance->completeWorkItem($workitem, $participant);

        $this->assertThat($processInstance->isEnded(), $this->isTrue());
        $this->assertThat($workflow->isEnded(), $this->isTrue());
    }

    /**
     * @test
     *
     * @since Method available since Release 2.0.0
     */
    public function executeCallActivityTask()
    {
        $participant = $this->createMock(ParticipantInterface::class);
        $participant->method('hasRole')->willReturn(true);

        $workflow = $this->workflowRepository->findById('CallActivity');
        $workflow->start($workflow->getFlowObject('Start'));
        $currentFlowObjects = $workflow->getCurrentFlowObjects();

        $this->assertThat($workflow->isActive(), $this->isTrue());
        $this->assertThat($workflow->isEnded(), $this->isFalse());
        $this->assertThat($currentFlowObjects[0]->getWorkItems()->count(), $this->equalTo(1));
        $this->assertThat($currentFlowObjects[1]->getWorkItems()->count(), $this->equalTo(1));

        $workitem = $currentFlowObjects[0]->getWorkItems()->getAt(0);
        $workflow->allocateWorkItem($workitem, $participant);
        $workflow->startWorkItem($workitem, $participant);
        $workflow->completeWorkItem($workitem, $participant);

        $processInstance = $currentFlowObjects[1]->getWorkItems()->getAt(0);
        $currentFlowObject = $processInstance->getCurrentFlowObject();
        $workitem = $currentFlowObject->getWorkItems()->getAt(0);
        $processInstance->allocateWorkItem($workitem, $participant);
        $processInstance->startWorkItem($workitem, $participant);
        $processInstance->completeWorkItem($workitem, $participant);
        $processInstance->cancel();

        $this->assertThat($processInstance->isEnded(), $this->isTrue());
        $this->assertThat($workflow->isEnded(), $this->isTrue());
    }

    /**
     * @test
     *
     * @since Method available since Release 2.0.0
     */
    public function inclusiveGateway()
    {
        $participant = $this->createMock(ParticipantInterface::class);
        $participant->method('hasRole')->willReturn(true);

        $workflow = $this->workflowRepository->findById('InclusiveGateway');
        $workflow->setProcessData([
            'paymentReceived' => false,
            'shipOrder' => true,
        ]);
        $workflow->start($workflow->getFlowObject('Start'));

        $currentFlowObjects = $workflow->getCurrentFlowObjects();

        $this->assertThat(count($currentFlowObjects), $this->equalTo(2));

        foreach ($currentFlowObjects as $currentFlowObject) {
            $this->assertThat(current($currentFlowObject->getToken())->getPreviousFlowObject()->getId(), $this->equalTo('InclusiveGateway1'));
        }

        $workitem = $currentFlowObjects[0]->getWorkItems()->getAt(0);
        $workflow->allocateWorkItem($workitem, $participant);
        $workflow->startWorkItem($workitem, $participant);
        $workflow->completeWorkItem($workitem, $participant);

        $this->assertThat(count($workflow->getCurrentFlowObjects()), $this->equalTo(2));

        $workitem = $currentFlowObjects[1]->getWorkItems()->getAt(0);
        $workflow->allocateWorkItem($workitem, $participant);
        $workflow->startWorkItem($workitem, $participant);
        $workflow->completeWorkItem($workitem, $participant);

        $this->assertThat(count($workflow->getCurrentFlowObjects()), $this->equalTo(1));
        $this->assertThat(current($workflow->getCurrentFlowObjects())->getId(), $this->equalTo('ArchiveOrder'));
        $this->assertThat(current(current($workflow->getCurrentFlowObjects())->getToken())->getPreviousFlowObject()->getId(), $this->equalTo('InclusiveGateway2'));

        $activityLog = $workflow->getActivityLog();

        $this->assertThat(count($activityLog), $this->equalTo(3));

        $concurrentFlowObjects = ['ReceiveOrder', 'ShipOrder'];

        $this->assertThat($concurrentFlowObjects, $this->containsIdentical($activityLog->get(0)->getActivity()->getId()));

        unset($concurrentFlowObjects[array_search($activityLog->get(0)->getActivity()->getId(), $concurrentFlowObjects)]);

        $this->assertThat($concurrentFlowObjects, $this->containsIdentical($activityLog->get(1)->getActivity()->getId()));

        $this->assertThat($activityLog->get(2)->getActivity()->getId(), $this->equalTo('ArchiveOrder'));
    }

    /**
     * @test
     *
     * @since Method available since Release 2.0.0
     */
    public function inclusiveGateway2()
    {
        $participant = $this->createMock(ParticipantInterface::class);
        $participant->method('hasRole')->willReturn(true);

        $workflow = $this->workflowRepository->findById('InclusiveGateway');
        $workflow->setProcessData([
            'paymentReceived' => true,
            'shipOrder' => true,
        ]);
        $workflow->start($workflow->getFlowObject('Start'));

        $currentFlowObjects = $workflow->getCurrentFlowObjects();

        $this->assertThat(count($currentFlowObjects), $this->equalTo(1));

        foreach ($currentFlowObjects as $currentFlowObject) {
            $this->assertThat(current($currentFlowObject->getToken())->getPreviousFlowObject()->getId(), $this->equalTo('InclusiveGateway1'));
        }

        $workitem = $currentFlowObjects[0]->getWorkItems()->getAt(0);
        $workflow->allocateWorkItem($workitem, $participant);
        $workflow->startWorkItem($workitem, $participant);
        $workflow->completeWorkItem($workitem, $participant);

        $this->assertThat(count($workflow->getCurrentFlowObjects()), $this->equalTo(1));
        $this->assertThat(current($workflow->getCurrentFlowObjects())->getId(), $this->equalTo('ArchiveOrder'));
        $this->assertThat(current(current($workflow->getCurrentFlowObjects())->getToken())->getPreviousFlowObject()->getId(), $this->equalTo('InclusiveGateway2'));

        $activityLog = $workflow->getActivityLog();

        $this->assertThat(count($activityLog), $this->equalTo(2));

        $concurrentFlowObjects = ['ShipOrder'];

        $this->assertThat($concurrentFlowObjects, $this->containsIdentical($activityLog->get(0)->getActivity()->getId()));

        $this->assertThat($activityLog->get(1)->getActivity()->getId(), $this->equalTo('ArchiveOrder'));
    }
}
