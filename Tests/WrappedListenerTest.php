<?php

declare(strict_types=1);

/*
 * This file is part of BiuradPHP opensource projects.
 *
 * PHP version 7.2 and above required
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BiuradPHP\Events\Tests;

use BiuradPHP\Events\WrappedListener;
use Closure;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class WrappedListenerTest extends TestCase
{
    /**
     * @dataProvider provideListenersToDescribe
     */
    public function testListenerDescription($listener, $expected): void
    {
        $wrappedListener = new WrappedListener(
            $listener,
            null,
            $this->getMockBuilder(EventDispatcherInterface::class)->getMock()
        );

        $this->assertStringMatchesFormat($expected, $wrappedListener->getPretty());
    }

    public function provideListenersToDescribe()
    {
        return [
            [new FooListener(), 'BiuradPHP\Events\Tests\FooListener::__invoke'],
            [[new FooListener(), 'listen'], 'BiuradPHP\Events\Tests\FooListener::listen'],
            [[FooListener::class, 'listenStatic'], 'BiuradPHP\Events\Tests\FooListener::listenStatic'],
            [[FooListener::class, 'invalidMethod'], 'BiuradPHP\Events\Tests\FooListener::invalidMethod'],
            ['var_dump', 'var_dump'],
            [
                function (): string {
                    return 'something';
                },
                'closure',
            ],
            [Closure::fromCallable([new FooListener(), 'listen']), 'BiuradPHP\Events\Tests\FooListener::listen'],
            [
                Closure::fromCallable([FooListener::class, 'listenStatic']),
                'BiuradPHP\Events\Tests\FooListener::listenStatic',
            ],
            [Closure::fromCallable(function (): void {
            }), 'closure'],
        ];
    }
}

class FooListener
{
    public function __invoke(): void
    {
    }

    public function listen(): void
    {
    }

    public static function listenStatic(): void
    {
    }
}
