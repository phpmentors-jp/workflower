<?php


namespace PHPMentors\Workflower\Workflow;


use PHPMentors\Workflower\Workflow\Activity\CallTask;
use PHPMentors\Workflower\Workflow\Activity\ManualTask;
use PHPMentors\Workflower\Workflow\Activity\SendTask;
use PHPMentors\Workflower\Workflow\Activity\ServiceTask;
use PHPMentors\Workflower\Workflow\Activity\SubProcessTask;
use PHPMentors\Workflower\Workflow\Activity\Task;
use PHPMentors\Workflower\Workflow\Activity\UserTask;
use PHPMentors\Workflower\Workflow\Connection\SequenceFlow;
use PHPMentors\Workflower\Workflow\Element\ConditionalInterface;
use PHPMentors\Workflower\Workflow\Event\EndEvent;
use PHPMentors\Workflower\Workflow\Event\StartEvent;
use PHPMentors\Workflower\Workflow\Gateway\ExclusiveGateway;
use PHPMentors\Workflower\Workflow\Gateway\ParallelGateway;
use PHPMentors\Workflower\Workflow\Participant\Role;
use Symfony\Component\ExpressionLanguage\Expression;

class ProcessDefinition implements ProcessDefinitionInterface
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $name = null;

    /**
     * @var string
     */
    private $description = null;

    /**
     * @var int
     */
    private $version = 1;

    /**
     * @var bool
     */
    private $suspended = false;

    /**
     * @var array
     */
    private $endEvents = [];

    /**
     * @var array
     */
    private $exclusiveGateways = [];

    /**
     * @var array
     *
     * @since Property available since Release 2.0.0
     */
    private $parallelGateways = [];

    /**
     * @var array
     */
    private $roles = [];

    /**
     * @var array
     */
    private $sequenceFlows = [];

    /**
     * @var array
     */
    private $startEvents = [];

    /**
     * @var array
     */
    private $tasks = [];

    /**
     * @var array
     */
    private $userTasks = [];

    /**
     * @var array
     */
    private $manualTasks = [];

    /**
     * @var array
     *
     * @since Property available since Release 1.2.0
     */
    private $serviceTasks = [];

    /**
     * @var array
     *
     * @since Property available since Release 1.3.0
     */
    private $sendTasks = [];

    /**
     * @var array
     */
    private $defaultableFlowObjects = [];

    /**
     * @var array
     */
    private $subProcesses = [];

    /**
     * @var array
     */
    private $callActivities = [];

    /**
     * @var ProcessDefinitionRepositoryInterface
     */
    private $processDefinitions;

    /**
     * @param array $config Array containing the necessary params.
     *    $config = [
     *      'id' => (string)
     *      'name' => (string)
     *      'description' => (string)
     *      'version' => (string)
     *      'startEvents' => (array)
     *      'endEvents' => (array)
     *      'exclusiveGateways' => (array)
     *      'parallelGateways' => (array)
     *      'roles' => (array)
     *      'sequenceFlows' => (array)
     *      'tasks' => (array)
     *      'userTasks' => (array)
     *      'manualTasks' => (array)
     *      'serviceTasks' => (array)
     *      'sendTasks' => (array)
     *      'subProcesses' => (array)
     *    ]
     */
    public function __construct(array $config = [])
    {
        foreach ($config as $name => $definitions) {
            switch ($name) {
                case 'roles':
                    foreach ($definitions as $definition) {
                        $this->addRole($definition);
                    }
                    break;

                case 'startEvents':
                    foreach ($definitions as $definition) {
                        $this->addStartEvent($definition);
                    }
                    break;

                case 'endEvents':
                    foreach ($definitions as $definition) {
                        $this->addEndEvent($definition);
                    }
                    break;

                case 'exclusiveGateways':
                    foreach ($definitions as $definition) {
                        $this->addExclusiveGateway($definition);
                    }
                    break;

                case 'parallelGateways':
                    foreach ($definitions as $definition) {
                        $this->addParallelGateway($definition);
                    }
                    break;

                case 'tasks':
                    foreach ($definitions as $definition) {
                        $this->addTask($definition);
                    }
                    break;

                case 'userTasks':
                    foreach ($definitions as $definition) {
                        $this->addUserTask($definition);
                    }
                    break;

                case 'manualTasks':
                    foreach ($definitions as $definition) {
                        $this->addManualTask($definition);
                    }
                    break;

                case 'serviceTasks':
                    foreach ($definitions as $definition) {
                        $this->addServiceTask($definition);
                    }
                    break;

                case 'sendTasks':
                    foreach ($definitions as $definition) {
                        $this->addSendTask($definition);
                    }
                    break;

                case 'subProcesses':
                    foreach ($definitions as $definition) {
                        $this->addSubProcessTask($definition);
                    }
                    break;

                case 'callActivities':
                    foreach ($definitions as $definition) {
                        $this->addCallActivity($definition);
                    }
                    break;

                case 'sequenceFlows':
                    foreach ($definitions as $definition) {
                        $this->addSequenceFlow($definition);
                    }
                    break;

                default:
                    if (property_exists(self::class, $name)) {
                        $this->{$name} = $definitions;
                    }
                    break;
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
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * {@inheritdoc}
     */
    public function isSuspended()
    {
        $this->suspended;
    }

    /**
     * @param bool $suspended
     */
    public function setSuspended(bool $suspended): void
    {
        $this->suspended = $suspended;
    }

    /**
     * {@inheritdoc}
     */
    public function setProcessDefinitions(ProcessDefinitionRepositoryInterface $collection): void
    {
        $this->processDefinitions = $collection;
    }


    /**
     * {@inheritdoc}
     */
    public function getProcessDefinitions()
    {
        return $this->processDefinitions;
    }

    /**
     * {@inheritdoc}
     */
    public function createProcessInstance()
    {
        $workflow = new Workflow($this->getId(), $this->getName());
        $workflow->setProcessDefinition($this);

        foreach ($this->roles as $config) {
            $workflow->addRole(new Role($config));
        }

        foreach ($this->startEvents as $config) {
            $clone = array_merge([], $config);
            $this->replaceRoleInConfig($workflow, $clone);

            $workflow->addFlowObject(new StartEvent($clone));
        }

        foreach ($this->endEvents as $config) {
            $clone = array_merge([], $config);
            $this->replaceRoleInConfig($workflow, $clone);

            $workflow->addFlowObject(new EndEvent($clone));
        }

        foreach ($this->tasks as $config) {
            $clone = array_merge([], $config);
            $this->replaceRoleInConfig($workflow, $clone);

            $workflow->addFlowObject(new Task($clone));
        }

        foreach ($this->userTasks as $config) {
            $clone = array_merge([], $config);
            $this->replaceRoleInConfig($workflow, $clone);

            $workflow->addFlowObject(new UserTask($clone));
        }

        foreach ($this->manualTasks as $config) {
            $clone = array_merge([], $config);
            $this->replaceRoleInConfig($workflow, $clone);

            $workflow->addFlowObject(new ManualTask($clone));
        }

        foreach ($this->serviceTasks as $config) {
            $clone = array_merge([], $config);
            $this->replaceRoleInConfig($workflow, $clone);

            $workflow->addFlowObject(new ServiceTask($clone));
        }

        foreach ($this->sendTasks as $config) {
            $clone = array_merge([], $config);
            $this->replaceRoleInConfig($workflow, $clone);

            $workflow->addFlowObject(new SendTask($clone));
        }

        foreach ($this->subProcesses as $config) {
            $clone = array_merge([], $config);

            $this->replaceRoleInConfig($workflow, $clone);
            $definition = new ProcessDefinition($clone['processDefinition']);
            $definition->setProcessDefinitions($this->getProcessDefinitions());
            $clone['processDefinition'] = $definition;

            $workflow->addFlowObject(new SubProcessTask($clone));
        }

        foreach ($this->callActivities as $config) {
            $clone = array_merge([], $config);

            $this->replaceRoleInConfig($workflow, $config);

            $workflow->addFlowObject(new CallTask($config));
        }

        foreach ($this->exclusiveGateways as $config) {
            $clone = array_merge([], $config);
            $this->replaceRoleInConfig($workflow, $clone);

            $workflow->addFlowObject(new ExclusiveGateway($clone));
        }

        foreach ($this->parallelGateways as $config) {
            $clone = array_merge([], $config);
            $this->replaceRoleInConfig($workflow, $clone);

            $workflow->addFlowObject(new ParallelGateway($clone));
        }

        foreach ($this->sequenceFlows as $config) {
            $clone = array_merge([], $config);
            $id = $this->getParamFromConfig($clone, 'id');
            $condition = $this->getParamFromConfig($clone, 'condition');

            if (array_key_exists($id, $this->defaultableFlowObjects) && $condition !== null) {
                throw new \LogicException(sprintf('The sequence flow "%s" has the condition "%s". A condition cannot be set to the default sequence flow.', $id, $condition));
            }

            $clone['source'] = $workflow->getFlowObject($this->getParamFromConfig($clone, 'source'));
            $clone['destination'] = $workflow->getFlowObject($this->getParamFromConfig($clone, 'destination'));
            $clone['condition'] = $condition === null ? null : new Expression($condition);

            $workflow->addConnectingObject(new SequenceFlow($clone));

            if (array_key_exists($id, $this->defaultableFlowObjects)) {
                $flowObject = $workflow->getFlowObject($this->defaultableFlowObjects[$id]);
                /* @var $flowObject ConditionalInterface */
                $flowObject->setDefaultSequenceFlowId($id);
            }
        }

        return $workflow;
    }

    /**
     * @param array $config Array containing the necessary params.
     *    $config = [
     *      'id' => (string)
     *      'roleId' => (string)
     *      'name' => (string)
     *    ]
     */
    public function addEndEvent(array $config)
    {
        $id = $this->getParamFromConfig($config, 'id');
        $this->endEvents[$id] = $config;
    }

    /**
     * @param array $config Array containing the necessary params.
     *    $config = [
     *      'id' => (string)
     *      'roleId' => (string)
     *      'name' => (string)
     *      'defaultSequenceFlow' => (int|string)
     *    ]
     */
    public function addExclusiveGateway(array $config)
    {
        $id = $this->getParamFromConfig($config, 'id');
        $defaultSequenceFlow = $this->getParamFromConfig($config, 'defaultSequenceFlow');

        $this->exclusiveGateways[$id] = $config;

        if ($defaultSequenceFlow !== null) {
            $this->defaultableFlowObjects[$defaultSequenceFlow] = $id;
        }
    }

    /**
     * @param array $config Array containing the necessary params.
     *    $config = [
     *      'id' => (string)
     *      'roleId' => (string)
     *      'name' => (string)
     *    ]
     */
    public function addParallelGateway(array $config): void
    {
        $id = $this->getParamFromConfig($config, 'id');
        $this->parallelGateways[$id] = $config;
    }

    /**
     * @param array $config Array containing the necessary params.
     *    $config = [
     *      'id' => (string)
     *      'name' => (string)
     *    ]
     */
    public function addRole(array $config)
    {
        $id = $this->getParamFromConfig($config, 'id');
        $this->roles[$id] = $config;
    }

    /**
     * @param array $config Array containing the necessary params.
     *    $config = [
     *      'id' => (string)
     *      'source' => (string)
     *      'destination' => (string)
     *      'name' => (string)
     *      'condition' => (string)
     *    ]
     */
    public function addSequenceFlow(array $config)
    {
        static $i = 0;
        $id = $this->getParamFromConfig($config, 'id');

        if ($id === null) {
            $id = $this->getParamFromConfig($config, 'source', 'source') .
                '.' . $this->getParamFromConfig($config, 'destination', 'destination') . $i;
            ++$i;
        }

        $this->sequenceFlows[$id] = $config;
    }

    /**
     * @param array $config Array containing the necessary params.
     *    $config = [
     *      'id' => (string)
     *      'roleId' => (string)
     *      'name' => (string)
     *      'defaultSequenceFlow' => (int|string)
     *    ]
     */
    public function addStartEvent(array $config)
    {
        $id = $this->getParamFromConfig($config, 'id');
        $defaultSequenceFlow = $this->getParamFromConfig($config, 'defaultSequenceFlowId');

        $this->startEvents[$id] = $config;

        if ($defaultSequenceFlow !== null) {
            $this->defaultableFlowObjects[$defaultSequenceFlow] = $id;
        }
    }

    /**
     * @param array $config Array containing the necessary params.
     *    $config = [
     *      'id' => (string)
     *      'roleId' => (string)
     *      'name' => (string)
     *      'defaultSequenceFlow' => (int|string)
     *      'multiInstance' => (bool)
     *      'sequential' => (bool)
     *      'completionCondition' => (string)
     *    ]
     */
    public function addTask(array $config)
    {
        $id = $this->getParamFromConfig($config, 'id');
        $defaultSequenceFlow = $this->getParamFromConfig($config, 'defaultSequenceFlowId');

        $this->tasks[$id] = $config;

        if ($defaultSequenceFlow !== null) {
            $this->defaultableFlowObjects[$defaultSequenceFlow] = $id;
        }
    }

    /**
     * @param array $config Array containing the necessary params.
     *    $config = [
     *      'id' => (string)
     *      'roleId' => (string)
     *      'name' => (string)
     *      'defaultSequenceFlow' => (int|string)
     *      'multiInstance' => (bool)
     *      'sequential' => (bool)
     *      'completionCondition' => (string)
     *    ]
     */
    public function addUserTask(array $config)
    {
        $id = $this->getParamFromConfig($config, 'id');
        $defaultSequenceFlow = $this->getParamFromConfig($config, 'defaultSequenceFlowId');

        $this->userTasks[$id] = $config;

        if ($defaultSequenceFlow !== null) {
            $this->defaultableFlowObjects[$defaultSequenceFlow] = $id;
        }
    }

    /**
     * @param array $config Array containing the necessary params.
     *    $config = [
     *      'id' => (string)
     *      'roleId' => (string)
     *      'name' => (string)
     *      'defaultSequenceFlow' => (int|string)
     *      'multiInstance' => (bool)
     *      'sequential' => (bool)
     *      'completionCondition' => (string)
     *    ]
     */
    public function addManualTask(array $config)
    {
        $id = $this->getParamFromConfig($config, 'id');
        $defaultSequenceFlow = $this->getParamFromConfig($config, 'defaultSequenceFlowId');

        $this->manualTasks[$id] = $config;

        if ($defaultSequenceFlow !== null) {
            $this->defaultableFlowObjects[$defaultSequenceFlow] = $id;
        }
    }

    /**
     * @param array $config Array containing the necessary params.
     *    $config = [
     *      'id' => (string)
     *      'roleId' => (string)
     *      'name' => (string)
     *      'operation' => (string)
     *      'defaultSequenceFlow' => (int|string)
     *      'multiInstance' => (bool)
     *      'sequential' => (bool)
     *      'completionCondition' => (string)
     *    ]
     */
    public function addServiceTask(array $config)
    {
        $id = $this->getParamFromConfig($config, 'id');
        $defaultSequenceFlow = $this->getParamFromConfig($config, 'defaultSequenceFlowId');

        $this->serviceTasks[$id] = $config;

        if ($defaultSequenceFlow !== null) {
            $this->defaultableFlowObjects[$defaultSequenceFlow] = $id;
        }
    }

    /**
     * @param array $config Array containing the necessary params.
     *    $config = [
     *      'id' => (string)
     *      'roleId' => (string)
     *      'name' => (string)
     *      'message' => (string)
     *      'operation' => (string)
     *      'defaultSequenceFlow' => (int|string)
     *      'multiInstance' => (bool)
     *      'sequential' => (bool)
     *      'completionCondition' => (string)
     *    ]
     */
    public function addSendTask(array $config)
    {
        $id = $this->getParamFromConfig($config, 'id');
        $defaultSequenceFlow = $this->getParamFromConfig($config, 'defaultSequenceFlowId');

        $this->sendTasks[$id] = $config;

        if ($defaultSequenceFlow !== null) {
            $this->defaultableFlowObjects[$defaultSequenceFlow] = $id;
        }
    }

    /**
     * @param array $config Array containing the necessary params.
     *    $config = [
     *      'id' => (string)
     *      'roleId' => (string)
     *      'name' => (string)
     *      'defaultSequenceFlow' => (int|string)
     *      'multiInstance' => (bool)
     *      'sequential' => (bool)
     *      'completionCondition' => (string)
     *      'processDefinition' => []
     *    ]
     */
    public function addSubProcessTask(array $config)
    {
        $id = $this->getParamFromConfig($config, 'id');
        $defaultSequenceFlow = $this->getParamFromConfig($config, 'defaultSequenceFlowId');

        $this->subProcesses[$id] = $config;

        if ($defaultSequenceFlow !== null) {
            $this->defaultableFlowObjects[$defaultSequenceFlow] = $id;
        }
    }

    /**
     * @param array $config Array containing the necessary params.
     *    $config = [
     *      'id' => (string)
     *      'roleId' => (string)
     *      'name' => (string)
     *      'calledElement' => (string)
     *      'defaultSequenceFlow' => (int|string)
     *      'multiInstance' => (bool)
     *      'sequential' => (bool)
     *      'completionCondition' => (string)
     *    ]
     */
    public function addCallActivity(array $config)
    {
        $id = $this->getParamFromConfig($config, 'id');
        $defaultSequenceFlow = $this->getParamFromConfig($config, 'defaultSequenceFlowId');

        $this->callActivities[$id] = $config;

        if ($defaultSequenceFlow !== null) {
            $this->defaultableFlowObjects[$defaultSequenceFlow] = $id;
        }
    }

    /**
     * @param Workflow   $workflow
     * @param int|string $roleId
     *
     * @throws \LogicException
     */
    private function assertWorkflowHasRole(Workflow $workflow, $roleId)
    {
        if (!$workflow->hasRole($roleId)) {
            throw new \LogicException(sprintf('The workflow "%s" does not have the role "%s".', $workflow->getId(), $roleId));
        }
    }

    private function getParamFromConfig($config, $param, $defaultValue = null)
    {
        $value = null;

        if (is_array($config) && isset($config[$param])) {
            $value = $config[$param];
        }

        if ($value === null) {
            $value = $defaultValue;
        }

        return $value;
    }

    private function replaceRoleInConfig($workflow, &$config)
    {
        $roleId = $this->getParamFromConfig($config, 'roleId');

        if ($roleId !== null) {
            $this->assertWorkflowHasRole($workflow, $roleId);
            unset($config['roleId']);
            $config['role'] = $workflow->getRole($roleId);
        }
    }

}