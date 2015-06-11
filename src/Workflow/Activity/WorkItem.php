<?php
namespace PHPMentors\Workflower\Workflow\Activity;

use PHPMentors\DomainKata\Entity\EntityInterface;
use PHPMentors\Workflower\Workflow\Participant\ParticipantInterface;

class WorkItem implements EntityInterface, \Serializable
{
    const END_RESULT_COMPLETION = 'completion';

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
    private $endParticipant;

    /**
     * @var string
     */
    private $endResult;

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
            'endParticipant' => $this->endParticipant,
            'endResult' => $this->endResult,
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
    public function getEndParticipant()
    {
        return $this->endParticipant;
    }

    /**
     * @return string
     */
    public function getEndResult()
    {
        return $this->endResult;
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
     * @param string               $endResult
     */
    public function end(ParticipantInterface $participant, $endResult)
    {
        $this->endDate = new \DateTime();
        $this->endParticipant = $participant;
        $this->endResult = $endResult;
    }
}
