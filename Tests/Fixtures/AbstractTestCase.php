<?php
/*
* This file is part of the OrbitaleTranslationBundle package.
*
* (c) Alexandre Rock Ancelet <contact@orbitale.io>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Orbitale\Bundle\TranslationBundle\Tests\Fixtures;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class AbstractTestCase extends WebTestCase
{
    /**
     * @var ContainerInterface
     */
    protected static $container;

    /**
     * @var KernelInterface
     */
    protected static $kernel;

    /**
     * @param array $options An array of options to pass to the createKernel class
     * @return KernelInterface
     */
    protected function getKernel(array $options = array())
    {
        return static::$kernel;
    }

    public function setUp()
    {
        if (static::$kernel) {
            static::$kernel->shutdown();
        }
        static::$kernel = static::createKernel(array());
        static::$kernel->boot();
    }

    /**
     * Generates tokens according to normally Translator behavior
     * @param $source
     * @param $domain
     * @param $locale
     * @return string
     */
    protected function generateToken($source, $domain, $locale)
    {
        return md5($source.'_'.$domain.'_'.$locale);
    }

}
