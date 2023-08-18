<?php

namespace PHPMentors\Workflower\Workflow;

interface ItemsCollectionInterface extends \Countable, \IteratorAggregate//, \Serializable
{
    /**
     * @param ItemInterface $item
     *
     * @return void
     */
    public function add(ItemInterface $item);

    /**
     * @param ItemInterface $item
     *
     * @return void
     */
    public function remove(ItemInterface $item);

    /**
     * @param int $index
     *
     * @return ItemInterface
     */
    public function getAt(int $index);

    /**
     * @param int|string $id
     *
     * @return ItemInterface
     */
    public function get($id);

    /**
     * @return int
     */
    public function countOfActiveInstances(): int;

    /**
     * @return int
     */
    public function countOfCompletedInstances(): int;

    /**
     * @return ItemInterface[]
     */
    public function getActiveInstances();

    /**
     * @return ItemInterface[]
     */
    public function getCompletedInstances();
}
