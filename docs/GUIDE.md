# Quick Start Guide

Workflower is a [BPMN 2.0](http://www.omg.org/spec/BPMN/2.0/) workflow engine for PHP and is an open source product released under [The BSD 2-Clause License](https://opensource.org/licenses/BSD-2-Clause). The primary use of Workflower is to manage human-centric business processes with PHP applications.

This article shows the work required to manage business processes using Workflower on [Symfony](http://symfony.com/) applications.

## Symfony integration with Workflower using PHPMentorsWorkflowerBundle

[PHPMentorsWorkflowerBundle](https://github.com/phpmentors-jp/workflower-bundle) is an integration layer to use Workflower in Symfony applications and provides the following features:

* Automatically generates DI container services corresponding to workflows and automatically injects service objects using the `phpmentors_workflower.process_aware` tags
* Allocates tasks to participants and controls access for participants using the Symfony security system
* Provides transparent serialization/deserialization for entities using [Doctrine ORM](http://www.doctrine-project.org/projects/orm.html)
* Supports multiple workflow contexts (directories where BPMN files are stored)

## Installing Workflower and PHPMentorsWorkflowerBundle

First, we will use Composer to install Workflower and PHPMentorsWorkflowerBundle as project dependent packages:

```console
$ composer require phpmentors/workflower "1.2.*"
$ composer require phpmentors/workflower-bundle "1.2.*"
```

Second, change `Appkernel` to enable `PHPMentorsWorkflowerBundle`:

```php
// app/AppKernel.php

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new PHPMentors\WorkflowerBundle\PHPMentorsWorkflowerBundle(),
        );

        // ...

        return $bundles;
    }
}
```

## Configuraring PHPMentorsWorkflowerBundle

Next, configure `PHPMentorsWorkflowerBundle`. An example is shown below:

```yaml
# app/config/config.yml

# ...

phpmentors_workflower:
    serializer_service: phpmentors_workflower.base64_php_workflow_serializer
    workflow_contexts:
        app:
            definition_dir: "%kernel.root_dir%/../src/AppBundle/Resources/config/workflower"

# ...
```

* `serializer_service` - Specify the ID of the DI container service to be used for serializing `PHPMentors\Workflower\Workflow\Workflow` objects that are instances of workflows. `PHPMentors\Workflower\Persistence\WorkflowSerializerInterface` implementation is expected for the specified service. The default is `phpmentors_workflower.php_workflow_serializer`. You can also use `phpmentors_workflower.base64_php_workflow_serializer` to encode/decode serialized objects with MIME base64.
* `workflow_contexts` - Specify `definition_dir` (the directory where the BPMN files are stored) for each workflow context ID.

## Designing workflows with BPMN

Define a workflow to work with Workflower using an editor supporting BPMN 2.0. Initially it is better to define a workflow consisting only of start events, tasks, and end events, and then designs and defines the entire workflow once if you can see to work the workflow from start to finish. The name of this BPMN file is used as `the workflow ID`. Save it as a name like `LoanRequestProcess.bpmn`. The conditional expression of the sequence flow used for branching is evaluated as an expression of [the Symfony ExpressionLanguage component](http://symfony.com/doc/3.1/components/expression_language.html). Note that the sequence flow evaluation order is undefined, so it is necessary to set conditional expressions consistent with other branch destinations. In the conditional expression, you can use associative array keys returned from `PHPMentors\Workflower\Process\ProcessContextInterface::getProcessData()`.

The screenshot below is from [BPMN2 Modeler](https://www.eclipse.org/bpmn2-modeler/), a BPMN editor available in [Eclipse](https://eclipse.org/):

![Editing a BPMN 2.0 model by BPMN2 Modeler](https://cloud.githubusercontent.com/assets/52985/20619334/57813132-b337-11e6-80dd-36e1c873eb81.png)

### Workflow elements supported by Workflower

Workflower supports the following BPMN 2.0 workflow elements:

* Connecting objects
  * Sequence flows
* Flow objects
  * Activities
    * Tasks
    * Service tasks
  * Events
    * Start events
    * End events
  * Gateways
    * Exclusive gateways

Please be aware that the unsupported elements in a workflow will be ignored.

## Designing entities that represent instances of workflow

Design an entity to be persistent representing an instance of a specific workflow (called as a `process` in Workflower) and add it to the application. This entity usually implements `PHPMentors\Workflower\Process\ProcessContextInterface` and `PHPMentors\Workflower\Persistence\WorkflowSerializableInterface`. The associative array returned from `PHPMentors\Workflower\Process\ProcessContextInterface::getProcessData()` is expanded in the conditional expression of the sequence flows in the workflow.

It's also a good idea to provide properties that hold a snapshot of some properties of the `Workflow` object according to the need in the application (e.g. querying the database). For example, if your application needs to search the database for processes that remain in a particular activity, add `$currentActivity` to the entity that represents the current activity. An example is shown below:

```php
// ...

use PHPMentors\Workflower\Persistence\WorkflowSerializableInterface;
use PHPMentors\Workflower\Process\ProcessContextInterface;
use PHPMentors\Workflower\Workflow\Workflow;
// ...

class LoanRequestProcess implements ProcessContextInterface, WorkflowSerializableInterface
{
    // ...

   /**
     * @var Workflow
     */
    private $workflow;

    /**
     * @var string
     *
     * @Column(type="blob", name="serialized_workflow")
     */
    private $serializedWorkflow;
    
    // ...

    /**
     * {@inheritdoc}
     */
    public function getProcessData()
    {
        return array(
            'foo' => $this->foo,
            'bar' => $this->bar,
            // ...
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setWorkflow(Workflow $workflow)
    {
        $this->workflow = $workflow;
    }

    /**
     * {@inheritdoc}
     */
    public function getWorkflow()
    {
        return $this->workflow;
    }

    /**
     * {@inheritdoc}
     */
    public function setSerializedWorkflow($workflow)
    {
        $this->serializedWorkflow = $workflow;
    }

    /**
     * {@inheritdoc}
     */
    public function getSerializedWorkflow()
    {
        if (is_resource($this->serializedWorkflow)) {
            return stream_get_contents($this->serializedWorkflow, -1, 0);
        } else {
            return $this->serializedWorkflow;
        }
    }

    // ...
}
```

## Designing domain services for managing business processes

In order to use Workflower at the production level, you will need domain services for starting processes, allocating/starting/ completing work items. In order to link the processes of a specific workflow with some domain services, implement `PHPMentors\Workflower\Process\ProcessAwareInterface` and tag the DI container services of the domain services with the `phpmentors_workflower.process_aware` tag. An example is shown below:

```php
// ...

use PHPMentors\DomainKata\Entity\EntityInterface;
use PHPMentors\Workflower\Process\Process;
use PHPMentors\Workflower\Process\ProcessAwareInterface;
use PHPMentors\Workflower\Process\WorkItemContextInterface;
// ...

class LoanRequestProcessCompletionUsecase implements ProcessAwareInterface
{
    // ...

    /**
     * @var Process
     */
    private $process;

    // ...
    
    /**
     * {@inheritdoc}
     */
    public function setProcess(Process $process)
    {
        $this->process = $process;
    }

    // ...

    /**
     * {@inheritdoc}
     */
    public function run(EntityInterface $entity)
    {
        assert($entity instanceof WorkItemContextInterface);

        $this->process->completeWorkItem($entity);

        // ...
    }

    // ...
}
```

The service definition corresponding to the above is as follows:

```yaml
# ...

app.loan_request_process_completion_usecase:
    class: "%app.loan_request_process_completion_usecase.class%"
    tags:
        - { name: phpmentors_workflower.process_aware, workflow: LoanRequestProcess, context: app }
    # ...

# ...
```

Implementation of the use case class corresponding to "combination of process and operation" as in this example can be said to be basic, but if you can analyze the commonality and variability of process operations and extract the variability to the outside, you can also combine operations into a single class.

## Managing business processes

Finally, it is necessary to implement clients (controllers, commands, event listeners, etc.) to start processes, allocate/start/complete work items. These clients should also be able to extract variability to the outside.

Once you have done so, you will be able to perform a series of operations on the business process from the web interface or the command line interface (CLI).

## Toward the realization of Generative Programming with BPMS

In this article we have seen the work required to manage business processes using Workflower on Symfony applications. Workflower and PHPMentorsWorkflowerBundle will only provide the `Workflow` domain model and basic integration layer corresponding to BPMN 2.0 workflow elements, so further work (and skill) will be required to actually create the workflow system on the application. It is by no means easy. This is because it is the design of a BPMS (Business Process Management System) or BPMS framework suitable for the target domain.

In addition, software development by BPMS can be said to be the practice of [Generative Programming](https://www.amazon.com/dp/0201309777/ref=cm_sw_r_tw_dp_x_Yj8nybT850KQV) in the business process domain. Currently there are few BPMS available in PHP, and only people who try to create it will achieve results.

## References

* [phpmentors-jp/workflower-bundle](https://github.com/phpmentors-jp/workflower-bundle)
* [phpmentors-jp/workflower](https://github.com/phpmentors-jp/workflower)
* [Q-BPM](http://en.q-bpm.org/mediawiki/index.php?title=Main_Page)
