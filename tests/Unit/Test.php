<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class Test extends TestCase
{
    public function testEnglishInflector(): void
    {
        $inflector = new \Symfony\Component\String\Inflector\EnglishInflector();

        $result = $inflector->singularize('houses');

        self::assertEquals(['houses'], $result);
    }

    public function testAccountOutput(): void
    {
        $registry = new \MetaDataTool\PageRegistry();
        $registry->add('https://start.exactonline.nl/docs/hlprestapiresourcesdetails.aspx?name=crmaccounts');
        $config = new \MetaDataTool\Config\DocumentationCrawlerConfig(false);
        $crawler = new \MetaDataTool\DocumentationCrawler($config, $registry);

        $result = $crawler->run();
    }
}
