<?php


namespace PHPMentors\Workflower\Workflow;


use PHPMentors\Workflower\Workflow\Activity\WorkItemInterface;

class WorkItemsCollection implements ItemsCollectionInterface
{
    protected $items = [];

    public function serialize()
    {
        return serialize([
            'items' => $this->items,
        ]);
    }

    public function unserialize($serialized)
    {
        foreach (unserialize($serialized) as $name => $value) {
            if (property_exists($this, $name)) {
                $this->$name = $value;
            }
        }
    }

    public function add(ItemInterface $item)
    {
        $this->items[] = $item;
    }

    public function remove(ItemInterface $item)
    {
        $this->items = array_filter($this->items, function (ItemInterface $currentItem) use ($item) {
            return $currentItem !== $item;
        });
    }

    public function get($id)
    {
        foreach ($this->items as $item) {
            if ($item->getId() === $id) {
                return $item;
            }
        }
        throw new \OutOfBoundsException(sprintf('The item "%s" was not found.', $id));
    }

    public function getAt(int $index)
    {
        if (!array_key_exists($index, $this->items)) {
            return null;
        }

        return $this->items[$index];
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->items);
    }

    public function count()
    {
        return count($this->items);
    }

    public function countOfActiveInstances(): int
    {
        return count($this->getActiveInstances());
    }

    public function countOfCompletedInstances(): int
    {
        return count($this->getCompletedInstances());
    }

    public function getActiveInstances()
    {
        return array_filter($this->items, function (ItemInterface $item) {
            $state = $item->getState();
            return $state !== WorkItemInterface::STATE_ENDED && $state !== WorkItemInterface::STATE_CANCELLED;
        });
    }

    public function getCompletedInstances()
    {
        return array_filter($this->items, function (ItemInterface $item) {
            $state = $item->getState();
            return $state === WorkItemInterface::STATE_ENDED || $state === WorkItemInterface::STATE_CANCELLED;
        });
    }
}