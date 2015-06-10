<?php
namespace PHPMentors\Workflower\Workflow\Activity;

use PHPMentors\DomainKata\Entity\EntityInterface;
use PHPMentors\Workflower\Workflow\Participant\ParticipantInterface;

class WorkItem implements EntityInterface, \Serializable
{
    const ENDED_WITH_COMPLETION = 'completion';

    /**
     * @var \DateTime
     */
    private $startDate;

    /**
     * @var ParticipantInterface
     */
    private $startParticipant;

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
     * @param ParticipantInterface $startParticipant
     */
    public function __construct(ParticipantInterface $startParticipant)
    {
        $this->startDate = new \DateTime();
        $this->startParticipant = $startParticipant;
    }

    /**
     * {@inheritDoc}
     */
    public function serialize()
    {
        return serialize(array(
            'startDate' => $this->startDate,
            'startParticipant' => $this->startParticipant,
            'endDate' => $this->endDate,
            'endedBy' => $this->endedBy,
            'endedWith' => $this->endedWith,
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
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @return ParticipantInterface
     */
    public function getStartParticipant()
    {
        return $this->startParticipant;
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
