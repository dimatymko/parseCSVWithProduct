<?php

namespace App\Event\Service;

use App\Repository\ProductRepository;

class AddProductHandler
{
    public const PRODUCT_CODE = 'Product Code';
    public const PRODUCT_NAME = 'Product Name';
    public const PRODUCT_DESCRIPTION = 'Product Description';
    public const PRODUCT_STOCK_LEVEL = 'Stock';
    public const PRODUCT_PRICE = 'Cost in GBP';
    public const PRODUCT_DISCONTINUED = 'Discontinued';

    /**
     * @var ProductRepository
     */
    private $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function execute(array $productRows, array $errorsWithProductCode, bool $testMod): int
    {
        $addedProducts = 0;
        foreach ($productRows as $row) {
            if (array_key_exists($row[self::PRODUCT_CODE], $errorsWithProductCode)) {
                continue;
            }

            $product = $this->productRepository->findOneByCode($row[self::PRODUCT_CODE]);
            if (!$product) {
                $product = $this->productRepository->create();
            }
            $product
                ->setName($row[self::PRODUCT_NAME])
                ->setDescription($row[self::PRODUCT_DESCRIPTION])
                ->setCode($row[self::PRODUCT_CODE])
                ->setDiscontinued(($row[self::PRODUCT_DISCONTINUED] === 'yes') ? new \DateTime() : null)
                ->setAddAt(new \DateTime())
                ->setPrice((int) $row[self::PRODUCT_PRICE])
                ->setStockLevel((int) $row[self::PRODUCT_STOCK_LEVEL]);

            $addedProducts++;
            $this->productRepository->save($product, false);
        }

        if (!$testMod) {
            $this->productRepository->save();
        }

        return $addedProducts;
    }
}
