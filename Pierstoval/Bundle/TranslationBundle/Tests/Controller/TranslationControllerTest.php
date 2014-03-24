<?php

namespace Pierstoval\Bundle\TranslationBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TranslationControllerTest extends WebTestCase
{
    public function testChangelang()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/lang/{locale}');
    }

    public function testManagetranslations()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/admin/translations/{locale}/{domain}');
    }

}
