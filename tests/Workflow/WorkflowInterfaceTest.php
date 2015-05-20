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

use PHPMentors\Workflower\Workflow\Activity\ActivityInterface;

abstract class WorkflowInterfaceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WorkflowInterface
     */
    private $workflow;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $workflowRepository = new WorkflowRepository();
        $this->workflow = $workflowRepository->findById('LoanRequestProcess');
    }

    /**
     * @test
     */
    public function isInBeforeStarting()
    {
        $this->assertThat($this->workflow->isActive(), $this->isFalse());
        $this->assertThat($this->workflow->isEnded(), $this->isFalse());
        $this->assertThat($this->workflow->getCurrentFlowObject(), $this->isNull());
        $this->assertThat($this->workflow->getPreviousFlowObject(), $this->isNull());
    }

    /**
     * @test
     */
    public function start()
    {
        $participant = \Phake::mock('PHPMentors\Workflower\Workflow\Participant\ParticipantInterface');
        \Phake::when($participant)->hasRole($this->anything())->thenReturn(true);

        $this->workflow->start($this->workflow->getFlowObject('Start'));

        $this->assertThat($this->workflow->isActive(), $this->isTrue());
        $this->assertThat($this->workflow->isEnded(), $this->isFalse());

        $currentFlowObject = $this->workflow->getCurrentFlowObject();

        $this->assertThat($currentFlowObject, $this->isInstanceOf('PHPMentors\Workflower\Workflow\Activity\Task'));
        $this->assertThat($currentFlowObject->getId(), $this->equalTo('Record Loan Application Information'));
        $this->assertThat($currentFlowObject->isActive(), $this->isFalse());
        $this->assertThat($currentFlowObject->isEnded(), $this->isFalse());

        $previousFlowObject = $this->workflow->getPreviousFlowObject();

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

        $this->workflow->start($this->workflow->getFlowObject('Start'));
        $this->workflow->assignActivity($this->workflow->getCurrentFlowObject(), $participant);

        $currentFlowObject = $this->workflow->getCurrentFlowObject();

        $this->assertThat($currentFlowObject, $this->isInstanceOf('PHPMentors\Workflower\Workflow\Activity\Task'));
        $this->assertThat($currentFlowObject->getId(), $this->equalTo('Record Loan Application Information'));
        $this->assertThat($currentFlowObject->isActive(), $this->isTrue());
        $this->assertThat($currentFlowObject->isEnded(), $this->isFalse());
        $this->assertThat($currentFlowObject->getParticipant(), $this->identicalTo($participant));
        $this->assertThat($currentFlowObject->getStartDate(), $this->isInstanceOf('DateTime'));
    }

    /**
     * @test
     */
    public function completeActivity()
    {
        $participant = \Phake::mock('PHPMentors\Workflower\Workflow\Participant\ParticipantInterface');
        \Phake::when($participant)->hasRole($this->anything())->thenReturn(true);

        $this->workflow->start($this->workflow->getFlowObject('Start'));
        $this->workflow->assignActivity($this->workflow->getCurrentFlowObject(), $participant);
        $this->workflow->completeActivity($this->workflow->getCurrentFlowObject(), $participant);

        $currentFlowObject = $this->workflow->getCurrentFlowObject();

        $this->assertThat($currentFlowObject, $this->isInstanceOf('PHPMentors\Workflower\Workflow\Activity\Task'));
        $this->assertThat($currentFlowObject->getId(), $this->equalTo('Check Applicant Information'));
        $this->assertThat($currentFlowObject->isActive(), $this->isFalse());
        $this->assertThat($currentFlowObject->isEnded(), $this->isFalse());

        $previousFlowObject = $this->workflow->getPreviousFlowObject();

        $this->assertThat($previousFlowObject, $this->isInstanceOf('PHPMentors\Workflower\Workflow\Activity\Task'));
        $this->assertThat($previousFlowObject->getId(), $this->equalTo('Record Loan Application Information'));
        $this->assertThat($previousFlowObject->isActive(), $this->isFalse());
        $this->assertThat($previousFlowObject->isEnded(), $this->isTrue());
        $this->assertThat($previousFlowObject->getEndDate(), $this->isInstanceOf('DateTime'));
        $this->assertThat($previousFlowObject->getEndedWith(), $this->equalTo(ActivityInterface::ENDED_WITH_COMPLETION));
    }

    /**
     * @return array
     */
    public function completeActivityOnConditionalSequenceFlowsData()
    {
        return array(
            array(true, 'End'),
            array(false, 'Loan Study'),
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

        $this->workflow->setProcessData('rejected', false);
        $this->workflow->start($this->workflow->getFlowObject('Start'));
        $this->workflow->assignActivity($this->workflow->getCurrentFlowObject(), $participant);
        $this->workflow->completeActivity($this->workflow->getCurrentFlowObject(), $participant);
        $this->workflow->assignActivity($this->workflow->getCurrentFlowObject(), $participant);
        $this->workflow->setProcessData('rejected', $rejected);
        $this->workflow->completeActivity($this->workflow->getCurrentFlowObject(), $participant);

        $currentFlowObject = $this->workflow->getCurrentFlowObject();

        $this->assertThat($currentFlowObject->getId(), $this->equalTo($nextFlowObjectId));
    }

    /**
     * @return array
     */
    public function selectSequenceFlowOnExclusiveGatewayData()
    {
        return array(
            array(true, 'Inform Rejection'),
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

        $this->workflow->setProcessData('rejected', false);
        $this->workflow->start($this->workflow->getFlowObject('Start'));
        $this->workflow->assignActivity($this->workflow->getCurrentFlowObject(), $participant);
        $this->workflow->completeActivity($this->workflow->getCurrentFlowObject(), $participant);
        $this->workflow->assignActivity($this->workflow->getCurrentFlowObject(), $participant);
        $this->workflow->completeActivity($this->workflow->getCurrentFlowObject(), $participant);
        $this->workflow->assignActivity($this->workflow->getCurrentFlowObject(), $participant);
        $this->workflow->setProcessData('rejected', $rejected);
        $this->workflow->completeActivity($this->workflow->getCurrentFlowObject(), $participant);

        $currentFlowObject = $this->workflow->getCurrentFlowObject();

        $this->assertThat($currentFlowObject->getId(), $this->equalTo($nextFlowObjectId));
    }

    /**
     * @test
     */
    public function end()
    {
        $participant = \Phake::mock('PHPMentors\Workflower\Workflow\Participant\ParticipantInterface');
        \Phake::when($participant)->hasRole($this->anything())->thenReturn(true);

        $this->workflow->setProcessData('rejected', false);
        $this->workflow->start($this->workflow->getFlowObject('Start'));
        $this->workflow->assignActivity($this->workflow->getCurrentFlowObject(), $participant);
        $this->workflow->completeActivity($this->workflow->getCurrentFlowObject(), $participant);
        $this->workflow->assignActivity($this->workflow->getCurrentFlowObject(), $participant);
        $this->workflow->completeActivity($this->workflow->getCurrentFlowObject(), $participant);
        $this->workflow->assignActivity($this->workflow->getCurrentFlowObject(), $participant);
        $this->workflow->completeActivity($this->workflow->getCurrentFlowObject(), $participant);
        $this->workflow->assignActivity($this->workflow->getCurrentFlowObject(), $participant);
        $this->workflow->completeActivity($this->workflow->getCurrentFlowObject(), $participant);

        $this->assertThat($this->workflow->isActive(), $this->isFalse());
        $this->assertThat($this->workflow->isEnded(), $this->isTrue());
        $this->assertThat($this->workflow->getStartDate(), $this->isInstanceOf('DateTime'));
        $this->assertThat($this->workflow->getEndDate(), $this->isInstanceOf('DateTime'));

        $currentFlowObject = $this->workflow->getCurrentFlowObject();

        $this->assertThat($currentFlowObject, $this->isInstanceOf('PHPMentors\Workflower\Workflow\Event\EndEvent'));
        $this->assertThat($currentFlowObject->getId(), $this->equalTo('End'));

        $previousFlowObject = $this->workflow->getPreviousFlowObject();

        $this->assertThat($previousFlowObject, $this->isInstanceOf('PHPMentors\Workflower\Workflow\Activity\Task'));
        $this->assertThat($previousFlowObject->getId(), $this->equalTo('Disbursement'));
    }
}
