<?php

namespace PHPMentors\Workflower\Workflow\Activity;

use PHPMentors\Workflower\Workflow\Participant\LoggedParticipant;
use PHPMentors\Workflower\Workflow\Participant\ParticipantInterface;
use PHPMentors\Workflower\Workflow\ProcessInstanceInterface;

class WorkItem implements WorkItemInterface, \Serializable
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var ProcessInstanceInterface
     */
    private $parentProcessInstance;

    /**
     * @var ActivityInterface
     */
    private $parentActivity;

    /**
     * @var string
     */
    private $state = self::STATE_CREATED;

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

    /**
     * @var array
     */
    private $data;

    public function __construct($id)
    {
        $this->id = $id;
        $this->creationDate = new \DateTime();
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize([
            'id' => $this->id,
            'parentProcessInstance' => $this->parentProcessInstance,
            'parentActivity' => $this->parentActivity,
            'state' => $this->state,
            'participant' => $this->participant === null ? null : ($this->participant instanceof LoggedParticipant ? $this->participant : new LoggedParticipant($this->participant)),
            'creationDate' => $this->creationDate,
            'allocationDate' => $this->allocationDate,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'endParticipant' => $this->endParticipant === null ? null : ($this->endParticipant instanceof LoggedParticipant ? $this->endParticipant : new LoggedParticipant($this->endParticipant)),
            'endResult' => $this->endResult,
        ]);
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
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function setParentProcessInstance(ProcessInstanceInterface $processInstance)
    {
        $this->parentProcessInstance = $processInstance;
    }

    /**
     * {@inheritdoc}
     */
    public function getParentProcessInstance()
    {
        return $this->parentProcessInstance;
    }

    /**
     * {@inheritdoc}
     */
    public function setParentActivity(ActivityInterface $activity)
    {
        $this->parentActivity = $activity;
    }

    /**
     * {@inheritdoc}
     */
    public function getParentActivity()
    {
        return $this->parentActivity;
    }

    /**
     * {@inheritdoc}
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function isAllocatable()
    {
        return $this->getState() == WorkItem::STATE_CREATED;
    }

    /**
     * {@inheritdoc}
     */
    public function isStartable()
    {
        return $this->getState() == WorkItem::STATE_ALLOCATED;
    }

    /**
     * {@inheritdoc}
     */
    public function isCompletable()
    {
        return $this->getState() == WorkItem::STATE_STARTED;
    }

    /**
     * {@inheritdoc}
     */
    public function isCancelled()
    {
        return $this->getState() == WorkItem::STATE_CANCELLED;
    }

    /**
     * {@inheritdoc}
     */
    public function isEnded()
    {
        $state = $this->getState();

        return $state == WorkItem::STATE_ENDED || $state == WorkItem::STATE_CANCELLED;
    }

    /**
     * {@inheritdoc}
     */
    public function getState()
    {
        return $this->state;
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
        if (!$this->isAllocatable()) {
            throw new UnexpectedWorkItemStateException(sprintf('The current work item of the activity "%s" is not allocatable.', $this->getId()));
        }

        $this->state = self::STATE_ALLOCATED;
        $this->allocationDate = new \DateTime();
        $this->participant = $participant;
    }

    /**
     * {@inheritdoc}
     */
    public function start(): void
    {
        if (!$this->isStartable()) {
            throw new UnexpectedWorkItemStateException(sprintf('The current work item of the activity "%s" is not startable.', $this->getId()));
        }

        $this->state = self::STATE_STARTED;
        $this->startDate = new \DateTime();
    }

    /**
     * {@inheritdoc}
     */
    public function complete(ParticipantInterface $participant = null)
    {
        if (!$this->isCompletable()) {
            throw new UnexpectedWorkItemStateException(sprintf('The current work item of the activity "%s" is not completable.', $this->getId()));
        }

        $this->state = self::STATE_ENDED;
        $this->endDate = new \DateTime();
        $this->endParticipant = $participant === null ? $this->participant : $participant;
        $this->endResult = self::END_RESULT_COMPLETION;

        $this->getParentActivity()->completeWork();
    }

    public function cancel(): void
    {
        $this->state = self::STATE_CANCELLED;
        $this->endDate = new \DateTime();
    }
}
