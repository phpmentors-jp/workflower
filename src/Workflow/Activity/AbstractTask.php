<?php


namespace PHPMentors\Workflower\Workflow\Activity;


use PHPMentors\Workflower\Workflow\Connection\SequenceFlow;
use PHPMentors\Workflower\Workflow\Element\FlowObject;
use PHPMentors\Workflower\Workflow\Element\Token;
use PHPMentors\Workflower\Workflow\ItemsCollectionInterface;
use PHPMentors\Workflower\Workflow\Participant\Role;
use PHPMentors\Workflower\Workflow\SequenceFlowNotSelectedException;
use PHPMentors\Workflower\Workflow\Workflow;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * @since Class available since Release 2.0.0
 */
abstract class AbstractTask extends FlowObject implements ActivityInterface, \Serializable
{
    /**
     * @var int|string
     */
    private $id;

    /**
     * @var Role
     */
    private $role;

    /**
     * @var string
     */
    private $name;

    /**
     * @var int|string
     */
    private $defaultSequenceFlowId;

    /**
     * @var bool
     *
     * @since Property available since Release 2.0.0
     */
    private $multiInstance = false;

    /**
     * @var bool
     *
     * @since Property available since Release 2.0.0
     */
    private $sequential = false;

    /**
     * @var Expression
     */
    private $completionCondition;

    /**
     * @var string
     */
    private $state = self::STATE_INACTIVE;

    /**
     * @var ItemsCollectionInterface
     */
    protected $workItems;

    public function __construct(array $config = [])
    {
        parent::__construct($config);

        foreach ($config as $name => $value) {
            if (property_exists(self::class, $name)) {
                $this->{$name} = $value;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize([
            get_parent_class($this) => parent::serialize(),
            'id' => $this->id,
            'role' => $this->role,
            'name' => $this->name,
            'state' => $this->state,
            'defaultSequenceFlowId' => $this->defaultSequenceFlowId,
            'multiInstance' => $this->multiInstance,
            'sequential' => $this->sequential,
            'completionCondition' => $this->completionCondition,
            'workItems' => $this->workItems
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        foreach (unserialize($serialized) as $name => $value) {
            if ($name == get_parent_class($this)) {
                parent::unserialize($value);
                continue;
            }

            if (property_exists($this, $name)) {
                $this->$name = $value;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getWorkItems()
    {
        return $this->workItems;
    }

    /**
     * {@inheritdoc}
     */
    public function setWorkItems(ItemsCollectionInterface $collection)
    {
        $this->workItems = $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function setWorkflow(Workflow $workflow): void
    {
        parent::setWorkflow($workflow);
        $this->setWorkItems($workflow->generateWorkItemsCollection($this));

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
     *
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function equals($target)
    {
        if (!($target instanceof self)) {
            return false;
        }

        return $this->id === $target->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultSequenceFlowId($sequenceFlowId)
    {
        $this->defaultSequenceFlowId = $sequenceFlowId;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultSequenceFlowId()
    {
        return $this->defaultSequenceFlowId;
    }

    /**
     * {@inheritdoc}
     */
    public function isClosed()
    {
        return $this->getState() === self::STATE_CLOSED;
    }

    /**
     * {@inheritdoc}
     */
    public function isFailed()
    {
        return $this->getState() === self::STATE_FAILED;
    }

    /**
     * @return bool
     */
    public function isMultiInstance(): bool
    {
        return $this->multiInstance;
    }

    /**
     * @param bool $multiInstance
     */
    public function setMultiInstance(bool $multiInstance): void
    {
        $this->multiInstance = $multiInstance;
    }

    /**
     * @return bool
     */
    public function isSequential(): bool
    {
        return $this->sequential;
    }

    /**
     * @param bool $sequential
     */
    public function setSequential(bool $sequential): void
    {
        $this->sequential = $sequential;
    }

    /**
     * @param Expression $completionCondition
     */
    public function setCompletionCondition($completionCondition): void
    {
        $this->completionCondition = $completionCondition;
    }

    /**
     * @return Expression
     */
    public function getCompletionCondition()
    {
        return $this->completionCondition;
    }

    /**
     * {@inheritdoc}
     */
    public function start(): void
    {
        parent::start();
        $this->state = self::STATE_ACTIVE;
    }

    /**
     * {@inheritdoc}
     */
    public function end(): void
    {
        // This one gets called after all work items are completed

        $workflow = $this->getWorkflow();
        $selectedSequenceFlows = [];
        $incomingTokens = $this->getToken();

        foreach ($workflow->getConnectingObjectCollectionBySource($this) as $outgoing) {
            if ($outgoing instanceof SequenceFlow && $outgoing->getId() !== $this->getDefaultSequenceFlowId()) {
                $condition = $outgoing->getCondition();
                if ($condition === null) {
                    $selectedSequenceFlows[] = $outgoing;
                } else {
                    $expressionLanguage = $workflow->getExpressionLanguage() ?: new ExpressionLanguage();
                    if ($expressionLanguage->evaluate($condition, $workflow->getProcessData() ?: [])) {
                        $selectedSequenceFlows[] = $outgoing;
                    }
                }
            }
        }

        if (count($selectedSequenceFlows) === 0) {
            $next = $workflow->getConnectingObject($this->getDefaultSequenceFlowId());

            if ($next) {
                $selectedSequenceFlows[] = $next;
            }
        }

        if (count($selectedSequenceFlows) === 0) {
            throw new SequenceFlowNotSelectedException(sprintf('No sequence flow can be selected on "%s".', $this->getId()));
        }

        foreach ($incomingTokens as $incomingToken) {
            $workflow->removeToken($this, $incomingToken);
        }

        foreach ($selectedSequenceFlows as $selectedSequenceFlow) {
            $token = $workflow->generateToken($this);
            $selectedSequenceFlow->getDestination()->run($token);
        }

        $this->state = self::STATE_CLOSED;
    }

    /**
     * {@inheritdoc}
     */
    public function run(Token $token): void
    {
        $token->flow($this);

        $this->state = self::STATE_READY;
        $this->start();
        $this->createWork();
    }

    /**
     * {@inheritdoc}
     */
    public function cancel()
    {
        $state = $this->getState();

        if ($state === self::STATE_INACTIVE || $state === self::STATE_READY || $state === self::STATE_ACTIVE) {
            $this->cancelActiveInstances();
            $workflow = $this->getWorkflow();

            foreach ($this->getToken() as $token) {
                $workflow->removeToken($this, $token);
            }

            $this->state = self::STATE_FAILED;
        }
    }

    protected function cancelActiveInstances()
    {
        // cancel all active work items
        foreach ($this->getWorkItems()->getActiveInstances() as $workiItem) {
            $workiItem->cancel();
        }
    }

}