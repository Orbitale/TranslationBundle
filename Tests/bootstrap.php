<?php
/*
* This file is part of the PierstovalCmsBundle package.
*
* (c) Alexandre "Pierstoval" Rock Ancelet <pierstoval@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;

$file = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($file)) {
    throw new RuntimeException('Install dependencies to run test suite.');
}
$autoload = require_once $file;

AnnotationRegistry::registerFile(__DIR__.'/../vendor/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php');

if (file_exists(__DIR__.'/../vendor/pierstoval_translation_test.db')) {
    unlink(__DIR__.'/../vendor/pierstoval_translation_test.db');
}

include __DIR__.'/Fixtures/App/AppKernel.php';

$application = new Application(new AppKernel('test', true));
$application->setAutoExit(false);

//// Drop the database
//$input = new ArrayInput(array('command' => 'doctrine:database:drop','--force' => true,));
//$application->run($input);

// Create database
$input = new ArrayInput(array('command' => 'doctrine:database:create',));
$application->run($input);

// Create database schema
$input = new ArrayInput(array('command' => 'doctrine:schema:create',));
$application->run($input);

unset($input, $application);