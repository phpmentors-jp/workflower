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

use PHPMentors\Workflower\Workflow\Activity\WorkItem;

class WorkflowTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WorkflowRepositoryInterface
     */
    protected $workflowRepository;

    /**
     * {@inheritDoc}
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
        $participant = \Phake::mock('PHPMentors\Workflower\Workflow\Participant\ParticipantInterface');
        \Phake::when($participant)->hasRole($this->anything())->thenReturn(true);

        $workflow = $this->workflowRepository->findById('LoanRequestProcess');
        $workflow->start($workflow->getFlowObject('Start'));

        $this->assertThat($workflow->isActive(), $this->isTrue());
        $this->assertThat($workflow->isEnded(), $this->isFalse());

        $currentFlowObject = $workflow->getCurrentFlowObject();

        $this->assertThat($currentFlowObject, $this->isInstanceOf('PHPMentors\Workflower\Workflow\Activity\Task'));
        $this->assertThat($currentFlowObject->getId(), $this->equalTo('RecordLoanApplicationInformation'));
        $this->assertThat($currentFlowObject->isActive(), $this->isFalse());
        $this->assertThat($currentFlowObject->isEnded(), $this->isFalse());

        $previousFlowObject = $workflow->getPreviousFlowObject();

        $this->assertThat($previousFlowObject, $this->isInstanceOf('PHPMentors\Workflower\Workflow\Event\StartEvent'));
        $this->assertThat($previousFlowObject->getId(), $this->equalTo('Start'));
    }

    /**
     * @test
     */
    public function assignActivity()
    {
        $participant = \Phake::mock('PHPMentors\Workflower\Workflow\Participant\ParticipantInterface');
        \Phake::when($participant)->hasRole($this->anything())->thenReturn(true);

        $workflow = $this->workflowRepository->findById('LoanRequestProcess');
        $workflow->start($workflow->getFlowObject('Start'));
        $workflow->assignActivity($workflow->getCurrentFlowObject(), $participant);

        $currentFlowObject = $workflow->getCurrentFlowObject();

        $this->assertThat($currentFlowObject, $this->isInstanceOf('PHPMentors\Workflower\Workflow\Activity\Task'));
        $this->assertThat($currentFlowObject->getId(), $this->equalTo('RecordLoanApplicationInformation'));
        $this->assertThat($currentFlowObject->isActive(), $this->isTrue());
        $this->assertThat($currentFlowObject->isEnded(), $this->isFalse());
        $this->assertThat($currentFlowObject->getStartParticipant(), $this->identicalTo($participant));
        $this->assertThat($currentFlowObject->getStartDate(), $this->isInstanceOf('DateTime'));
    }

    /**
     * @test
     */
    public function completeActivity()
    {
        $participant = \Phake::mock('PHPMentors\Workflower\Workflow\Participant\ParticipantInterface');
        \Phake::when($participant)->hasRole($this->anything())->thenReturn(true);

        $workflow = $this->workflowRepository->findById('LoanRequestProcess');
        $workflow->start($workflow->getFlowObject('Start'));
        $workflow->assignActivity($workflow->getCurrentFlowObject(), $participant);
        $workflow->completeActivity($workflow->getCurrentFlowObject(), $participant);

        $currentFlowObject = $workflow->getCurrentFlowObject();

        $this->assertThat($currentFlowObject, $this->isInstanceOf('PHPMentors\Workflower\Workflow\Activity\Task'));
        $this->assertThat($currentFlowObject->getId(), $this->equalTo('CheckApplicantInformation'));
        $this->assertThat($currentFlowObject->isActive(), $this->isFalse());
        $this->assertThat($currentFlowObject->isEnded(), $this->isFalse());

        $previousFlowObject = $workflow->getPreviousFlowObject();

        $this->assertThat($previousFlowObject, $this->isInstanceOf('PHPMentors\Workflower\Workflow\Activity\Task'));
        $this->assertThat($previousFlowObject->getId(), $this->equalTo('RecordLoanApplicationInformation'));
        $this->assertThat($previousFlowObject->isActive(), $this->isFalse());
        $this->assertThat($previousFlowObject->isEnded(), $this->isTrue());
        $this->assertThat($previousFlowObject->getEndDate(), $this->isInstanceOf('DateTime'));
        $this->assertThat($previousFlowObject->getEndParticipant(), $this->identicalTo($participant));
        $this->assertThat($previousFlowObject->getEndedWith(), $this->equalTo(WorkItem::ENDED_WITH_COMPLETION));
    }

    /**
     * @return array
     */
    public function completeActivityOnConditionalSequenceFlowsData()
    {
        return array(
            array(true, 'End'),
            array(false, 'LoanStudy'),
        );
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
        $participant = \Phake::mock('PHPMentors\Workflower\Workflow\Participant\ParticipantInterface');
        \Phake::when($participant)->hasRole($this->anything())->thenReturn(true);

        $workflow = $this->workflowRepository->findById('LoanRequestProcess');
        $workflow->setProcessData(array('rejected' => false));
        $workflow->start($workflow->getFlowObject('Start'));
        $workflow->assignActivity($workflow->getCurrentFlowObject(), $participant);
        $workflow->completeActivity($workflow->getCurrentFlowObject(), $participant);
        $workflow->assignActivity($workflow->getCurrentFlowObject(), $participant);
        $workflow->setProcessData(array('rejected' => $rejected));
        $workflow->completeActivity($workflow->getCurrentFlowObject(), $participant);

        $currentFlowObject = $workflow->getCurrentFlowObject();

        $this->assertThat($currentFlowObject->getId(), $this->equalTo($nextFlowObjectId));
    }

    /**
     * @return array
     */
    public function selectSequenceFlowOnExclusiveGatewayData()
    {
        return array(
            array(true, 'InformRejection'),
            array(false, 'Disbursement'),
        );
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
        $participant = \Phake::mock('PHPMentors\Workflower\Workflow\Participant\ParticipantInterface');
        \Phake::when($participant)->hasRole($this->anything())->thenReturn(true);

        $workflow = $this->workflowRepository->findById('LoanRequestProcess');
        $workflow->setProcessData(array('rejected' => false));
        $workflow->start($workflow->getFlowObject('Start'));
        $workflow->assignActivity($workflow->getCurrentFlowObject(), $participant);
        $workflow->completeActivity($workflow->getCurrentFlowObject(), $participant);
        $workflow->assignActivity($workflow->getCurrentFlowObject(), $participant);
        $workflow->completeActivity($workflow->getCurrentFlowObject(), $participant);
        $workflow->assignActivity($workflow->getCurrentFlowObject(), $participant);
        $workflow->setProcessData(array('rejected' => $rejected));
        $workflow->completeActivity($workflow->getCurrentFlowObject(), $participant);

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
        $workflow->setProcessData(array('rejected' => false));
        $workflow->start($workflow->getFlowObject('Start'));
        $workflow->assignActivity($workflow->getCurrentFlowObject(), $participant);
        $workflow->completeActivity($workflow->getCurrentFlowObject(), $participant);
        $workflow->assignActivity($workflow->getCurrentFlowObject(), $participant);
        $workflow->completeActivity($workflow->getCurrentFlowObject(), $participant);
        $workflow->assignActivity($workflow->getCurrentFlowObject(), $participant);
        $workflow->completeActivity($workflow->getCurrentFlowObject(), $participant);
        $workflow->assignActivity($workflow->getCurrentFlowObject(), $participant);
        $workflow->completeActivity($workflow->getCurrentFlowObject(), $participant);

        $this->assertThat($workflow->isActive(), $this->isFalse());
        $this->assertThat($workflow->isEnded(), $this->isTrue());
        $this->assertThat($workflow->getStartDate(), $this->isInstanceOf('DateTime'));
        $this->assertThat($workflow->getEndDate(), $this->isInstanceOf('DateTime'));

        $currentFlowObject = $workflow->getCurrentFlowObject();

        $this->assertThat($currentFlowObject, $this->isInstanceOf('PHPMentors\Workflower\Workflow\Event\EndEvent'));
        $this->assertThat($currentFlowObject->getId(), $this->equalTo('End'));

        $previousFlowObject = $workflow->getPreviousFlowObject();

        $this->assertThat($previousFlowObject, $this->isInstanceOf('PHPMentors\Workflower\Workflow\Activity\Task'));
        $this->assertThat($previousFlowObject->getId(), $this->equalTo('Disbursement'));
    }
}
