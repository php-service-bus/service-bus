## Contributing
Contributions are welcome. We accept pull requests on [GitHub](https://github.com/php-service-bus/service-bus/issues).

## Workflow
If you have an idea for a new feature, it's a good idea to check out our [issues](https://github.com/php-service-bus/service-bus/issues) or active [pull requests](https://github.com/php-service-bus/service-bus/pulls) first to see if the feature is already being worked on. If not, feel free to submit an issue first, asking whether the feature is beneficial to the project. This will save you from doing a lot of development work only to have your feature rejected. We don't enjoy rejecting your hard work, but some features just don't fit with the goals of the project.

When you do begin working on your feature, here are some guidelines to consider:
* Your pull request description should clearly detail the changes you have made.
* Please write tests for any new features you add.
* Please ensure that tests pass before submitting your pull request.
* Use topic/feature branches. Please do not ask us to pull from your master branch.
* Submit one feature per pull request. If you have multiple features you wish to submit, please break them up into separate pull requests.
* Send coherent history. Make sure each individual commit in your pull request is meaningful. If you had to make multiple intermediate commits while developing, please squash them before submitting.

## Coding Guidelines
This project comes with a configuration file and an executable for php-cs-fixer (.php_cs.dist) that you can use to (re)format your source code for compliance with this project's coding guidelines:
```bash
composer cs-fix
```
For a simple check of the code standard, there is a command:
```bash
composer cs-check
```
## Static analysis
To improve the quality of the code used static analysis (via `psalm`). You can start it with the command:
```bash
composer psalm
```
## Running the tests
The following tests must pass before we will accept a pull request. If any of these do not pass, it will result in a complete build failure.
```bash
composer tests
```
## Communication Channels
You can find help and discussion in the following places:
* [Telegram chat (RU)](https://t.me/php_service_bus)
* Create issue [https://github.com/php-service-bus/service-bus/issues](https://github.com/php-service-bus/service-bus/issues)

## Security
If you discover any security related issues, please email [`dev@async-php.com`](mailto:dev@async-php.com) instead of using the issue tracker.

## Reporting issues
* [General problems](https://github.com/php-service-bus/service-bus/issues)
* [Documentation](https://github.com/php-service-bus/documentation/issues)
