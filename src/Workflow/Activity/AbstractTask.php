<?php

namespace PHPMentors\Workflower\Workflow\Activity;

use PHPMentors\Workflower\Workflow\Connection\SequenceFlow;
use PHPMentors\Workflower\Workflow\Element\FlowObject;
use PHPMentors\Workflower\Workflow\Element\Token;
use PHPMentors\Workflower\Workflow\ItemsCollectionInterface;
use PHPMentors\Workflower\Workflow\Participant\Role;
use PHPMentors\Workflower\Workflow\SequenceFlowNotSelectedException;
use PHPMentors\Workflower\Workflow\ProcessInstance;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * @since Class available since Release 2.0.0
 */
abstract class AbstractTask extends FlowObject implements ActivityInterface
{
    /**
     * @var int|string
     */
    protected $id;

    /**
     * @var Role
     */
    protected $role;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var int|string
     */
    protected $defaultSequenceFlowId;

    /**
     * @var bool
     *
     * @since Property available since Release 2.0.0
     */
    protected $multiInstance = false;

    /**
     * @var bool
     *
     * @since Property available since Release 2.0.0
     */
    protected $sequential = false;

    /**
     * @var Expression
     */
    protected $completionCondition;

    /**
     * @var string
     */
    protected $state = self::STATE_INACTIVE;

    /**
     * @var ItemsCollectionInterface
     */
    protected $workItems;

    protected array $properties = [];

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
    public function setProcessInstance(ProcessInstance $processInstance): void
    {
        parent::setProcessInstance($processInstance);
        $this->setWorkItems($processInstance->generateWorkItemsCollection($this));
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

        $processInstance = $this->getProcessInstance();
        $selectedSequenceFlows = [];
        $incomingTokens = $this->getToken();

        foreach ($processInstance->getConnectingObjectCollectionBySource($this) as $outgoing) {
            if ($outgoing instanceof SequenceFlow && $outgoing->getId() !== $this->getDefaultSequenceFlowId()) {
                $condition = $outgoing->getCondition();
                if ($condition === null) {
                    $selectedSequenceFlows[] = $outgoing;
                } else {
                    $expressionLanguage = $processInstance->getExpressionLanguage() ?: new ExpressionLanguage();
                    if ($expressionLanguage->evaluate($condition, $processInstance->getProcessData() ?: [])) {
                        $selectedSequenceFlows[] = $outgoing;
                    }
                }
            }
        }

        if (count($selectedSequenceFlows) === 0) {
            $next = $processInstance->getConnectingObject($this->getDefaultSequenceFlowId());

            if ($next) {
                $selectedSequenceFlows[] = $next;
            }
        }

        if (count($selectedSequenceFlows) === 0) {
            throw new SequenceFlowNotSelectedException(sprintf('No sequence flow can be selected on "%s".', $this->getId()));
        }

        foreach ($incomingTokens as $incomingToken) {
            $processInstance->removeToken($this, $incomingToken);
        }

        foreach ($selectedSequenceFlows as $selectedSequenceFlow) {
            $token = $processInstance->generateToken($this);
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
            $processInstance = $this->getProcessInstance();

            foreach ($this->getToken() as $token) {
                $processInstance->removeToken($this, $token);
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

    /**
     * @return array
     */
    public function getProperties(): array
    {
        return $this->properties;
    }
}
