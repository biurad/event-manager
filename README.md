# Event manager for listening and dispatching events

Events serve as a great way to decouple various aspects of your application, since a single event can have multiple listeners that do not depend on each other. For example, you may wish to send a Slack notification to your user each time an order has shipped. Instead of coupling your order processing code to your Slack notification code, you can raise an `OrderShipped` event, which a listener can receive and transform into a Slack notification.

**`Please note that this documentation is currently work-in-progress. Feel free to contribute.`**

## Installation

The recommended way to install Event Manager is via Composer:

```bash
composer require biurad/biurad-events-bus
```

It requires PHP version 7.2 and supports PHP up to 7.4. The dev-master version requires PHP 7.2.

## How To Use

Next, let's take a look at the listener for our example event. Event listeners receive the event instance in any method provided. Import the proper event class and type-hint the event on the method. Within the method, you may perform any actions necessary to respond to the event:

Sometimes, you may wish to stop the propagation of an event to other listeners. You may do so by returning `false` from your listener's method.

To dispatch an event, you may pass an instance of the event to the  `addListener` method. The dispatcher will dispatch the event to all of its registered listeners. Since the `EventDispatcher` is globally available, you may call it from anywhere in your application.

Event subscribers are classes that may subscribe to multiple events from within the class itself, allowing you to define several event handlers within a single class. Subscribers should define a static `getSubscribedEvents` method, which will be passed an event dispatcher instance. You may call the `listen` method on the given dispatcher to register event listeners.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Testing

To run the tests you'll have to start the included node based server if any first in a separate terminal window.

With the server running, you can start testing.

```bash
vendor/bin/phpunit
```

## Security

If you discover any security related issues, please report using the issue tracker.
use our example [Issue Report](.github/ISSUE_TEMPLATE/Bug_report.md) template.

## Want to be listed on our projects website

You're free to use this package, but if it makes it to your production environment we highly appreciate you sending us a message on our website, mentioning which of our package(s) you are using.

Post Here: [Project Patreons - https://patreons.biurad.com](https://patreons.biurad.com)

We publish all received request's on our website;

## Credits

- [Divine Niiquaye](https://github.com/divineniiquaye)
- [All Contributors](https://biurad.com/projects/biurad-events-bus/contributers)

## Support us

`Biurad Lap` is a technology agency in Accra, Ghana. You'll find an overview of all our open source projects [on our website](https://biurad.com/opensource).

Does your business depend on our contributions? Reach out and support us on to build more project's. We want to build over one hundred project's in two years. [Support Us](https://biurad.com/donate) achieve our goal.

Reach out and support us on [Patreon](https://www.patreon.com/biurad). All pledges will be dedicated to allocating workforce on maintenance and new awesome stuff.

[Thanks to all who made Donations and Pledges to Us.](.github/ISSUE_TEMPLATE/Support_us.md)

## License

The BSD-3-Clause . Please see [License File](LICENSE.md) for more information.
