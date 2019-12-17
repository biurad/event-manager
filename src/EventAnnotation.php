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

namespace BiuradPHP\Event;

use Doctrine\Common\Annotations\Reader;
use BiuradPHP\Event\Interfaces\BroadcastInterface;
use BiuradPHP\Event\Interfaces\EventInterface;

/**
 * EventAnnotation loads listeners from a PHP class or its methods.
 *
 * The @Listener annotation can be set on the class (for global parameters),
 * and on each method.
 *
 * The @Listener annotation main value is the event name.
 * The name parameter is mandatory.
 * Here is an example of how you should be able to use it:
 * ```php
 * <?php
 *  class Chat
 *  {
 *     /**
 *      * @Listener("chat.open")
 *      * /
 *     public function open()
 *     {
 *     }
 *
 *     /**
 *      * @Listener("chat.close")
 *      * /
 *     public function close(int $id)
 *     {
 *     }
 *  }
 * ```
 */
class EventAnnotation
{
    /**
     * @var \BiuradPHP\Event\Annotation\Listener
     */
    protected $eventAnnotationClass = 'BiuradPHP\\Event\\Annotation\\Listener';

    /**
     * @var string Namespace for Event Annotation;
     */
    protected $eventNamespace;

    /**
     * The RouteAnnotation Constructor.
     *
     * @param string                              $namespace
     * @param array                               $paths
     * @param EventInterface                      $events
     * @param \Doctrine\Common\Annotations\Reader $reader
     */
    public function __construct(string $namespace, $paths = [], EventInterface $events, Reader $reader)
    {
        $this->eventNamespace = $namespace;

        return $this->register($events, $reader, $paths);
    }

    /**
     * @param array $dirs
     *
     * @return array
     */
    private function findAllClasses(array $dirs)
    {
        $classes = [];

        foreach ($dirs as $prefix => $dir) {
            /** @var \RecursiveIteratorIterator|\SplFileInfo[] $iterator */
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $file) {
                if (($fileName = $file->getBasename('.php')) == $file->getBasename()) {
                    continue;
                }

                $classes[] = str_replace('.', '\\', $fileName);
            }
        }

        return $classes;
    }

    /**
     * Load the annoatation for events.
     *
     * @param EventInterface                      $events
     * @param \Doctrine\Common\Annotations\Reader $reader
     * @param array|string                        $path
     */
    private function register(EventInterface $events, Reader $reader, $path)
    {
        $classes = $this->findAllClasses((array) $path, 'php');

        foreach ($classes as $class) {
            try {
                $reflector = new \ReflectionClass($this->eventNamespace.$class);
            } catch (\ReflectionException $e) {
                throw new \InvalidArgumentException(
                    sprintf('Annotations from class [%s] cannot be read as it is not found.', $class)
                );
            }

            if ($reflector->isAbstract()) {
                throw new \InvalidArgumentException(
                    sprintf('Annotations from class [%s] cannot be read as it is abstract.', $class->getName())
                );
            }

            foreach ($reader->getClassAnnotations($reflector) as $classAnnotation) {
                if ($classAnnotation instanceof $this->eventAnnotationClass) {
                    if ($reflector->implementsInterface(BroadcastInterface::class)) {
                        $events->listen(
                            $classAnnotation->getPriority(), $reflector->getName()
                        );
                    }
                }
            }

            foreach ($reflector->getMethods() as $method) {
                foreach ($reader->getMethodAnnotations($method) as $annotation) {
                    if ($annotation instanceof $this->eventAnnotationClass) {
                        $events->listen(
                            $annotation->getPriority(), [$reflector->getName(), $method->getName()]
                        );
                    }
                }
            }
        }
    }
}
