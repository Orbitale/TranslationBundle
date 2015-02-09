<?php
/*
* This file is part of the PierstovalTranslationBundle package.
*
* (c) Alexandre "Pierstoval" Rock Ancelet <pierstoval@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Pierstoval\Bundle\TranslationBundle\Tests\Translator;

use Doctrine\ORM\EntityManager;
use Pierstoval\Bundle\TranslationBundle\Tests\Fixtures\AbstractTestCase;
use Pierstoval\Bundle\TranslationBundle\Translation\Translator;

class TranslatorTest extends AbstractTestCase
{

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @var EntityManager
     */
    protected $em;

    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->translator = $this->getKernel()->getContainer()->get('pierstoval_translator');
        $this->translator->setFlushStrategy(Translator::FLUSH_RUNTIME);
        $this->translator->setFallbackLocales(array($this->getKernel()->getContainer()->getParameter('locale')));
        $this->em = $this->getKernel()->getContainer()->get('doctrine')->getManager();
    }

    /**
     * @dataProvider provideTranslate
     *
     * @param string $source
     * @param string $expected
     * @param string $domain
     * @param string $locale
     * @param array  $params
     */
    public function testTranslate($source, $expected, $domain = null, $locale = null, $params = array())
    {
        $this->assertEquals($this->translator->trans($source, $params, $domain, $locale), $expected);
    }

    public function provideTranslate()
    {
        return array(
            array('', ''),
            array('0', '0'),
            array('  ', '  '),
            array('Message', 'Message'),
            array('Hello %world%!', 'Hello World!', null, null, array('%world%' => 'World'))
        );
    }

    /**
     * @dataProvider provideTranschoice
     *
     * @param string $source
     * @param string $expected
     * @param integer $number
     */
    public function testTranschoice($source, $expected, $number)
    {
        $this->assertEquals($this->translator->transChoice($source, $number), $expected);
    }

    public function provideTranschoice()
    {
        $str1 = '{0} There is no apples|{1} There is one apple|]1,Inf] There are %count% apples';
        return array(
            array($str1, 'There is no apples', 0),
            array($str1, 'There is one apple', 1),
            array($str1, 'There are 2 apples', 2),
        );
    }
}
