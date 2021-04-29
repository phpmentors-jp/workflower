<?php

namespace PHPMentors\Workflower\Workflow\Element;

use PHPMentors\Workflower\Workflow\ProcessInstance;

/**
 * @since Class available since Release 2.0.0
 */
abstract class FlowObject implements FlowObjectInterface, TransitionalInterface, \Serializable
{
    /**
     * @var bool
     */
    private $started = false;

    /**
     * @var Token[]
     *
     * @since Property available since Release 2.0.0
     */
    private $token = [];

    /**
     * @var ProcessInstance
     */
    private $processInstance;

    public function __construct(array $config = [])
    {
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
            'token' => $this->token,
            'started' => $this->started,
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
    public function getToken(): iterable
    {
        return $this->token;
    }

    /**
     * {@inheritdoc}
     */
    public function attachToken(Token $token): void
    {
        $this->token[$token->getId()] = $token;
    }

    /**
     * {@inheritdoc}
     */
    public function detachToken(Token $token): void
    {
        assert(array_key_exists($token->getId(), $this->getToken()));

        unset($this->token[$token->getId()]);
    }

    /**
     * {@inheritdoc}
     */
    public function setProcessInstance(ProcessInstance $processInstance): void
    {
        $this->processInstance = $processInstance;
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessInstance(): ProcessInstance
    {
        return $this->processInstance;
    }

    /**
     * @return bool
     */
    public function isStarted(): bool
    {
        return $this->started;
    }

    /**
     * {@inheritdoc}
     */
    public function start(): void
    {
        $this->started = true;
    }

    /**
     * {@inheritdoc}
     */
    public function end(): void
    {
        $this->started = false;
    }

    /**
     * {@inheritdoc}
     */
    public function run(Token $token): void
    {
        $token->flow($this);

        if (!$this->isStarted()) {
            $this->start();
        }

        $this->end();
    }
}
