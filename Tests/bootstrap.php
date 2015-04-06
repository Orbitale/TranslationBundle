<?php
/*
* This file is part of the OrbitaleTranslationBundle package.
*
* (c) Alexandre Rock Ancelet <contact@orbitale.io>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

$file = __DIR__.'/../vendor/autoload.php';
if (!file_exists($file)) {
    throw new RuntimeException('Install dependencies to run test suite.');
}
$autoload = require_once $file;

AnnotationRegistry::registerFile(__DIR__.'/../vendor/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php');

$dbFile = __DIR__.'/../vendor/orbitale_translation_test.db';

if (file_exists($dbFile)) {
    unlink($dbFile);
}

if (!is_dir(__DIR__.'/../build')) {
    mkdir(__DIR__.'/../build');
}

include __DIR__.'/Fixtures/App/AppKernel.php';

$application = new Application(new AppKernel('test', true));
$application->setAutoExit(false);

//// Drop the database
//$input = new ArrayInput(array('command' => 'doctrine:database:drop','--force' => true,));
//$application->run($input);

// Create database
$input = new ArrayInput(array('command' => 'doctrine:database:create',));
$application->run($input, new NullOutput);

// Create database schema
$input = new ArrayInput(array('command' => 'doctrine:schema:create',));
$application->run($input, new NullOutput);

unset($input, $application);
