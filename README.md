# Workflower

A BPMN2 compliant workflow engine

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
  * BPMN2 process definitions
* Persistence
  * Serialize/deserialize interfaces for `Workflow` objects

### The workflow model

* Connecting objects
  * `Sequence flow`
* Flow objects
  * Activities
    * `Task`
  * Events
    * `Start event`
    * `End event`
  * Gateways
    * `Exclusive gateway`

## Installation

`Workflower` can be installed using [Composer](http://getcomposer.org/).

Add the dependency to `phpmentors/workflower` into your `composer.json` file as the following:

```
composer require phpmentors/workflower "~1.0@dev"
```

## Support

If you find a bug or have a question, or want to request a feature, create an issue or pull request for it on [Issues](https://github.com/phpmentors-jp/workflower/issues).

## Copyright

Copyright (c) 2015 KUBO Atsuhiro, All rights reserved.

## License

[The BSD 2-Clause License](http://opensource.org/licenses/BSD-2-Clause)

## Acknowledgments

[Q-BPM.org](http://en.q-bpm.org/) by [Questetra,Inc.](http://www.questetra.com/) is the excellent web site for developers such as us who want to create a [business process management](https://en.wikipedia.org/wiki/Business_process_management) ([workflow](https://en.wikipedia.org/wiki/Workflow_engine)) engine.
