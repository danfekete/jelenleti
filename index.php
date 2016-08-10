<?php
/**
 * Copyright (c) 2016, VOOV LLC.
 * All rights reserved.
 * Written by Daniel Fekete
 * daniel.fekete@voov.hu
 */
date_default_timezone_set('Europe/Budapest');

use danfekete\jelenleti\GeneratorCommand;
use Symfony\Component\Console\Application;

require 'vendor/autoload.php';
$command = new GeneratorCommand();
$app = new Application();
$app->add($command);
//$app->setDefaultCommand($command->getName());
$app->run();