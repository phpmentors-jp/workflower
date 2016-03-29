<?php
namespace PHPMentors\Workflower\Workflow\Activity;

use PHPMentors\Workflower\Workflow\Participant\LoggedParticipant;
use PHPMentors\Workflower\Workflow\Participant\ParticipantInterface;

class WorkItem implements WorkItemInterface, \Serializable
{
    /**
     * @var string
     */
    private $currentState = self::STATE_CREATED;

    /**
     * @var ParticipantInterface
     */
    private $participant;

    /**
     * @var \DateTime
     */
    private $creationDate;

    /**
     * @var \DateTime
     */
    private $allocationDate;

    /**
     * @var \DateTime
     */
    private $startDate;

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

    public function __construct()
    {
        $this->creationDate = new \DateTime();
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(array(
            'currentState' => $this->currentState,
            'participant' => $this->participant === null ? null : ($this->participant instanceof LoggedParticipant ? $this->participant : new LoggedParticipant($this->participant)),
            'creationDate' => $this->creationDate,
            'allocationDate' => $this->allocationDate,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'endParticipant' => $this->endParticipant === null ? null : ($this->endParticipant instanceof LoggedParticipant ? $this->endParticipant : new LoggedParticipant($this->endParticipant)),
            'endResult' => $this->endResult,
        ));
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getCurrentState()
    {
        return $this->currentState;
    }

    /**
     * {@inheritdoc}
     */
    public function getParticipant()
    {
        return $this->participant;
    }

    /**
     * {@inheritdoc}
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllocationDate()
    {
        return $this->allocationDate;
    }

    /**
     * {@inheritdoc}
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * {@inheritdoc}
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * {@inheritdoc}
     */
    public function getEndParticipant()
    {
        return $this->endParticipant;
    }

    /**
     * {@inheritdoc}
     */
    public function getEndResult()
    {
        return $this->endResult;
    }

    /**
     * {@inheritdoc}
     */
    public function allocate(ParticipantInterface $participant)
    {
        $this->currentState = self::STATE_ALLOCATED;
        $this->allocationDate = new \DateTime();
        $this->participant = $participant;
    }

    /**
     * {@inheritdoc}
     */
    public function start()
    {
        $this->currentState = self::STATE_STARTED;
        $this->startDate = new \DateTime();
    }

    /**
     * {@inheritdoc}
     */
    public function complete(ParticipantInterface $participant = null)
    {
        $this->currentState = self::STATE_ENDED;
        $this->endDate = new \DateTime();
        $this->endParticipant = $participant === null ? $this->participant : $participant;
        $this->endResult = self::END_RESULT_COMPLETION;
    }
}
