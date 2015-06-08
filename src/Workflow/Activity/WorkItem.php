<?php
namespace PHPMentors\Workflower\Workflow\Activity;

use PHPMentors\DomainKata\Entity\EntityInterface;
use PHPMentors\Workflower\Workflow\Participant\ParticipantInterface;

class WorkItem implements EntityInterface
{
    const ENDED_WITH_COMPLETION = 'completion';

    /**
     * @var \DateTime
     */
    private $startDate;

    /**
     * @var ParticipantInterface
     */
    private $startedBy;

    /**
     * @var \DateTime
     */
    private $endDate;

    /**
     * @var ParticipantInterface
     */
    private $endedBy;

    /**
     * @var string
     */
    private $endedWith;

    /**
     * @param ParticipantInterface $assignee
     */
    public function __construct(ParticipantInterface $assignee)
    {
        $this->startDate = new \DateTime();
        $this->startedBy = $assignee;
    }

    /**
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @return ParticipantInterface
     */
    public function getStartedBy()
    {
        return $this->startedBy;
    }

    /**
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * @return ParticipantInterface
     */
    public function getEndedBy()
    {
        return $this->endedBy;
    }

    /**
     * @return string
     */
    public function getEndedWith()
    {
        return $this->endedWith;
    }

    /**
     * @return bool
     */
    public function isEnded()
    {
        return $this->endDate !== null;
    }

    /**
     * @param ParticipantInterface $participant
     * @param string               $endedWith
     */
    public function end(ParticipantInterface $participant, $endedWith)
    {
        $this->endDate = new \DateTime();
        $this->endedBy = $participant;
        $this->endedWith = $endedWith;
    }
}
