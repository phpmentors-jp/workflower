# Workflower

A BPMN 2.0 workflow engine for PHP

`Workflower` is a BPMN 2.0 workflow engine for PHP. `Workflower` runs business processes using [the BPMN 2.0 specification](http://www.omg.org/spec/BPMN/2.0/). It's open-source and distributed under [the BSD 2-Clause License](http://opensource.org/licenses/BSD-2-Clause).

[![Total Downloads](https://poser.pugx.org/phpmentors/workflower/downloads)](https://packagist.org/packages/phpmentors/workflower)
[![Latest Stable Version](https://poser.pugx.org/phpmentors/workflower/v/stable)](https://packagist.org/packages/phpmentors/workflower)
[![Latest Unstable Version](https://poser.pugx.org/phpmentors/workflower/v/unstable)](https://packagist.org/packages/phpmentors/workflower)
[![Build Status](https://travis-ci.org/phpmentors-jp/workflower.svg?branch=master)](https://travis-ci.org/phpmentors-jp/workflower)

## Features

* Workflow
  * The workflow engine and domain model
* Process
  * Some interfaces to work with `Workflow` objects
* Definition
  * BPMN 2.0 process definitions
* Persistence
  * Serialize/deserialize interfaces for `Workflow` objects

### Supported workflow elements

* Connecting objects
  * Sequence flows
* Flow objects
  * Activities
    * Tasks
    * Service tasks
    * Send tasks
  * Events
    * Start events
    * End events
  * Gateways
    * Exclusive gateways
* Swimlanes
  * Lanes

## Installation

`Workflower` can be installed using [Composer](http://getcomposer.org/).

Add the dependency to `phpmentors/workflower` into your `composer.json` file as the following:

**Stable version:**

```
composer require phpmentors/workflower "1.4.*"
```

**Development version:**

```
composer require phpmentors/workflower "~2.0@dev"
```

## Documentation

* [Quick Start Guide](https://github.com/phpmentors-jp/workflower/blob/master/docs/quick-start-guide.md)
* [Release Notes](https://github.com/phpmentors-jp/workflower/releases)

## Support

If you find a bug or have a question, or want to request a feature, create an issue or pull request for it on [Issues](https://github.com/phpmentors-jp/workflower/issues).

## Copyright

Copyright (c) 2015-2019 KUBO Atsuhiro and [contributors](https://github.com/phpmentors-jp/workflower/wiki/Contributors), All rights reserved.

## License

[The BSD 2-Clause License](http://opensource.org/licenses/BSD-2-Clause)
