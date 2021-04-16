<?php


namespace PHPMentors\Workflower\Workflow\Gateway;


use PHPMentors\Workflower\Workflow\Connection\SequenceFlow;
use PHPMentors\Workflower\Workflow\SequenceFlowNotSelectedException;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * @since Class available since Release 2.0.0
 */
class InclusiveGateway extends Gateway
{
    /**
     * @var int|string
     */
    private $defaultSequenceFlowId;

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
            'defaultSequenceFlowId' => $this->defaultSequenceFlowId,
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
    public function end(): void
    {
        $workflow = $this->getWorkflow();
        $incoming = $workflow->getConnectingObjectCollectionByDestination($this);
        $incomingTokens = $this->getToken();

        // Upon execution, a token is consumed from each incoming Sequence Flow that
        // has a token. A token will be produced on some of the outgoing Sequence
        // Flows.
        // In order to determine the outgoing Sequence Flows that receive a token, all
        // conditions on the outgoing Sequence Flows are evaluated. The evaluation
        // does not have to respect a certain order.
        // For every condition which evaluates to true, a token MUST be passed on the
        // respective Sequence Flow.
        // If and only if none of the conditions evaluates to true, the token is passed on the
        // default Sequence Flow.
        // In case all conditions evaluate to false and a default flow has not been specified,
        // the Inclusive Gateway throws an exception.

        if (count($incomingTokens) == count($incoming)) {
            $selectedSequenceFlows = [];

            foreach ($workflow->getConnectingObjectCollectionBySource($this) as $outgoing) {
                if ($outgoing instanceof SequenceFlow && $outgoing->getId() !== $this->getDefaultSequenceFlowId()) {
                    $condition = $outgoing->getCondition();
                    if ($condition === null) {
                        // find the next one that has a condition
                        continue;
                    } else {
                        $expressionLanguage = $workflow->getExpressionLanguage() ?: new ExpressionLanguage();
                        if ($expressionLanguage->evaluate($condition, $workflow->getProcessData())) {
                            $selectedSequenceFlows[] = $outgoing;
                            break;
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

            parent::end();
        }
    }

}