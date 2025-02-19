<?php

declare(strict_types=1);

namespace PicqerExactPhpClientGeneratorTest\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PicqerExactPhpClientGenerator\NamingHelper;
use PicqerExactPhpClientGenerator\ValueObject\Endpoint;
use PicqerExactPhpClientGenerator\ValueObject\PropertyCollection;
use PicqerExactPhpClientGenerator\ValueObject\SupportedMethods;

#[CoversClass(NamingHelper::class)]
class NamingHelperTest extends TestCase
{
    #[DataProvider('ExpectedClassNameDataProvider')]
    public function testGeneratesCorrectClassName(
        string $name,
        string $uri,
        string $expectedClassName,
    ): void
    {
        $endpoint = new Endpoint(
            name: $name,
            documentation: '',
            scope: '',
            uri: $uri,
            supportedMethods: new SupportedMethods(get: true, post: true, put: true, delete: true),
            example: '',
            properties: new PropertyCollection(),
            isDeprecated: false
        );

        $result = NamingHelper::getClassName($endpoint);

        self::assertEquals($expectedClassName, $result);
    }

    public static function ExpectedClassNameDataProvider(): \Generator
    {
        // Exceptions
        yield 'SystemUser' => [
            'name' => 'Users',
            'uri' => '/api/v1/system/Users',
            'expectedClassName' => 'SystemUser'
        ];

        // Bulk
        yield 'BulkGoodsDelivery' => [
            'name' => 'SalesOrder/GoodsDeliveries',
            'uri' => '/api/v1/{division}/bulk/SalesOrder/GoodsDeliveries',
            'expectedClassName' => 'BulkGoodsDelivery'
        ];
        yield 'BulkGoodsDeliveryLine' => [
            'name' => 'SalesOrder/GoodsDeliveryLines',
            'uri' => '/api/v1/{division}/bulk/SalesOrder/GoodsDeliveryLines',
            'expectedClassName' => 'BulkGoodsDeliveryLine'
        ];
    }

}