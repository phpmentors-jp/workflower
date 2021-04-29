<?php


namespace PHPMentors\Workflower\Workflow;


interface ProcessDefinitionRepositoryInterface
{
    /**
     * @param ProcessDefinitionInterface $definition
     * @return ProcessDefinitionInterface}
     */
    public function add(ProcessDefinitionInterface $definition);

    /**
     * @param string $id
     * @return ProcessDefinitionInterface
     */
    public function getLatestById(string $id);

    /**
     * @param string $name
     * @return ProcessDefinitionInterface
     */
    public function getLatestByName(string $name);

    /**
     * @param string $id
     * @param int $version
     * @return ProcessDefinitionInterface
     */
    public function getVersionById(string $id, int $version);

    /**
     * @param string $name
     * @param int $version
     * @return ProcessDefinitionInterface
     */
    public function getVersionByName(string $name, int $version);
}