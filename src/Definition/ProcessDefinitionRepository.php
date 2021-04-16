<?php


namespace PHPMentors\Workflower\Definition;


use PHPMentors\Workflower\Workflow\ProcessDefinitionInterface;
use PHPMentors\Workflower\Workflow\ProcessDefinitionRepositoryInterface;

class ProcessDefinitionRepository implements ProcessDefinitionRepositoryInterface
{
    /**
     * @var array
     */
    private $items = [];

    public function add(ProcessDefinitionInterface $definition)
    {
        $this->items[] = $definition;
        $definition->setProcessDefinitions($this);
        return $definition;
    }

    public function getLatestById(string $id)
    {
        $results = array_filter($this->items, function (ProcessDefinitionInterface $item) use ($id) {
            return $item->getId() === $id;
        });

        return $this->getLatestVersion($results);
    }

    public function getLatestByName(string $name)
    {
        $results = array_filter($this->items, function (ProcessDefinitionInterface $item) use ($name) {
            return $item->getName() === $name;
        });

        return $this->getLatestVersion($results);
    }

    public function getVersionById(string $id, int $version)
    {
        $results = array_filter($this->items, function (ProcessDefinitionInterface $item) use ($id, $version) {
            return $item->getId() === $id && $item->getVersion() === $version;
        });

        return count($results) > 0 ? $results[0] : null;
    }

    public function getVersionByName(string $name, int $version)
    {
        $results = array_filter($this->items, function (ProcessDefinitionInterface $item) use ($name, $version) {
            return $item->getName() === $name && $item->getVersion() === $version;
        });

        return count($results) > 0 ? $results[0] : null;
    }

    /**
     * @param string $file
     */
    public function importFromFile(string $file)
    {
        $import = new Bpmn2Reader();

        $definitions = $import->read($file);

        foreach ($definitions as $definition) {
            $this->add($definition);
        }
    }

    /**
     * @param string $source
     */
    public function importFromSource(string $source)
    {
        $import = new Bpmn2Reader();

        $definitions = $import->readSource($source);

        foreach ($definitions as $definition) {
            $this->add($definition);
        }
    }

    private function getLatestVersion(array $results)
    {
        $max = 0;
        $item = null;

        foreach ($results as $result) {
            $version = $result->getVersion();
            if ($version > $max) {
                $max = $version;
                $item = $result;
            }
        }

        return $item;
    }
}