<?php

declare(strict_types=1);

/*
 * This code is under BSD 3-Clause "New" or "Revised" License.
 *
 * PHP version 7 and above required
 *
 * @category  EventManager
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * @link      https://www.biurad.com/projects/eventmanager
 * @since     Version 0.1
 */

namespace BiuradPHP\Events;

use ArrayAccess;
use ArrayIterator;
use InvalidArgumentException;
use IteratorAggregate;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event encapsulation class.
 *
 * Encapsulates events thus decoupling the observer from the subject they encapsulate.
 *
 * @author Drak <drak@zikula.org>
 */
class GenericEvent extends Event implements ArrayAccess, IteratorAggregate
{
    protected $subject;
    protected $arguments;

    /**
     * Encapsulate an event with $subject and $args.
     *
     * @param mixed $subject   The subject of the event, usually an object or a callable
     * @param array $arguments Arguments to store in the event
     */
    public function __construct($subject = null, array $arguments = [])
    {
        $this->subject = $subject;
        $this->arguments = $arguments;
    }

    /**
     * Getter for subject property.
     *
     * @return mixed The observer subject
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Get argument by key.
     *
     * @param string $key
     *
     * @return mixed Contents of array key
     */
    public function getArgument(string $key)
    {
        if ($this->hasArgument($key)) {
            return $this->arguments[$key];
        }

        throw new InvalidArgumentException(sprintf('Argument "%s" not found.', $key));
    }

    /**
     * Add argument to event.
     *
     * @param string $key
     * @param mixed $value Value
     *
     * @return $this
     */
    public function setArgument(string $key, $value): self
    {
        $this->arguments[$key] = $value;

        return $this;
    }

    /**
     * Getter for all arguments.
     *
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Set args property.
     *
     * @param array $args
     *
     * @return $this
     */
    public function setArguments(array $args = []): self
    {
        $this->arguments = $args;

        return $this;
    }

    /**
     * Has argument.
     *
     * @param string $key
     * @return bool
     */
    public function hasArgument(string $key): bool
    {
        return array_key_exists($key, $this->arguments);
    }

    /**
     * ArrayAccess for argument getter.
     *
     * @param string $key Array key
     *
     * @return mixed
     *
     * @throws InvalidArgumentException if key does not exist in $this->args
     */
    public function offsetGet($key)
    {
        return $this->getArgument($key);
    }

    /**
     * ArrayAccess for argument setter.
     *
     * @param string $key   Array key to set
     * @param mixed  $value Value
     */
    public function offsetSet($key, $value)
    {
        $this->setArgument($key, $value);
    }

    /**
     * ArrayAccess for unset argument.
     *
     * @param string $key Array key
     */
    public function offsetUnset($key)
    {
        if ($this->hasArgument($key)) {
            unset($this->arguments[$key]);
        }
    }

    /**
     * ArrayAccess has argument.
     *
     * @param string $key Array key
     *
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->hasArgument($key);
    }

    /**
     * IteratorAggregate for iterating over the object like an array.
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->arguments);
    }
}
