<?php

namespace App\Validation;

use App\Event\Service\AddProductHandler;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ProductDataValidation
{
    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var float
     */
    private $rate;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    public function validation(array $row, array $errors, float $rate): array
    {
        $this->rate = $rate;

        $constraint = [
            new Assert\Collection(
                [
                    'fields'           => [
                        AddProductHandler::PRODUCT_STOCK_LEVEL  => new Assert\Type('numeric'),
                        AddProductHandler::PRODUCT_PRICE        => [
                            new Assert\Type('numeric'),
                            new Assert\LessThan(1000 * $this->rate),
                        ],
                        AddProductHandler::PRODUCT_DISCONTINUED => new Assert\Choice(['choices' => ['yes', 'no', '']]),
                    ],
                    'allowExtraFields' => true,
                ]
            ),
            new Assert\Callback(['callback' => [$this, 'validatePriceAndStock']]),
        ];

        $violations = $this->validator->validate($row, $constraint);
        if (count($violations) > 0) {
            foreach ($violations as $violation) {
                $errors[$row[AddProductHandler::PRODUCT_CODE]][$violation->getPropertyPath()] = $violation->getMessage(
                );
            }
        }

        return $errors;
    }

    public function validatePriceAndStock($array, ExecutionContextInterface $context): void
    {
        if (null === $array[AddProductHandler::PRODUCT_PRICE]
            || '' === $array[AddProductHandler::PRODUCT_PRICE]
            || null === $array[AddProductHandler::PRODUCT_STOCK_LEVEL]
            || '' === $array[AddProductHandler::PRODUCT_STOCK_LEVEL]) {
            return;
        }

        if (($array[AddProductHandler::PRODUCT_PRICE] < (5 * $this->rate)
            && $array[AddProductHandler::PRODUCT_STOCK_LEVEL] < (10 * $this->rate))) {
            $context
                ->buildViolation('The price and stock level do not meet conditions.')
                ->atPath(sprintf('[%s]', AddProductHandler::PRODUCT_PRICE))
                ->addViolation();
        }
    }
}
