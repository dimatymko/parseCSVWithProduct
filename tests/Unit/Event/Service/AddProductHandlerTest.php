<?php

namespace App\Tests\Unit\Event\Service;

use App\Entity\Product;
use App\Event\Service\AddProductHandler;
use App\Repository\ProductRepository;
use Generator;
use PHPUnit\Framework\TestCase;

class AddProductHandlerTest extends TestCase
{
    /**
     * @dataProvider getDataForExecute
     *
     * @param int   $expected
     * @param array $rows
     *
     * @return void
     */
    public function testExecute(int $expected, array $rows): void
    {
        $handler = new AddProductHandler($this->createProductRepositoryMock(false));

        $this->assertEquals($expected, $handler->execute($rows, ['P0003' => 'error'], false));
    }

    /**
     * @return Generator
     */
    public function getDataForExecute(): Generator
    {
        yield [
            2,
            [
                [
                    'Product Code'        => 'P0001',
                    'Product Name'        => 'TV-1',
                    'Product Description' => 'TV-1',
                    'Stock'               => '1',
                    'Cost in GBP'         => '1.99',
                    'Discontinued'        => '',
                ],
                [
                    'Product Code'        => 'P0002',
                    'Product Name'        => 'TV-2',
                    'Product Description' => 'TV-2',
                    'Stock'               => '2',
                    'Cost in GBP'         => '399.99',
                    'Discontinued'        => '',
                ],
                [
                    'Product Code'        => 'P0003',
                    'Product Name'        => 'TV',
                    'Product Description' => '32” Tv',
                    'Stock'               => '10',
                    'Cost in GBP'         => '399.99',
                    'Discontinued'        => '',
                ],
            ],
        ];
    }

    private function createProductRepositoryMock(bool $existProduct = false): ProductRepository
    {
        $productRepository = $this
            ->getMockBuilder(ProductRepository::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->setMethods(['findOneByCode', 'create', 'persist', 'save'])
            ->getMock();

        $productRepository->method('findOneByCode')->willReturn($existProduct);
        $productRepository->method('create')->willReturn($this->createMock(Product::class));

        return $productRepository;
    }
}
