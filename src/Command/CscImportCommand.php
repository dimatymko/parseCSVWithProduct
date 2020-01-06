<?php

namespace App\Command;

use App\Event\Service\AddProductHandler;
use App\Validation\ProductDataValidation;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use League\Csv\Reader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CscImportCommand extends Command
{
    private const TEST_MOD_ACTIVE = true;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var AddProductHandler
     */
    private $handler;

    /**
     * @var SymfonyStyle
     */
    private $terminalMessage;

    /**
     * @var ProductDataValidation
     */
    private $productDataValidation;

    public function __construct(
        EntityManagerInterface $entityManager,
        AddProductHandler $handler,
        ProductDataValidation $productDataValidation
    ) {
        parent::__construct('app:import:products');

        $this->entityManager = $entityManager;
        $this->handler = $handler;
        $this->productDataValidation = $productDataValidation;
    }

    protected function configure(): void
    {
        $this->addArgument('filename', InputArgument::REQUIRED, 'Filename?');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->terminalMessage = new SymfonyStyle($input, $output);
        $fileName = $input->getArgument('filename');

        if (!file_exists($fileName)) {
            $this->terminalMessage->error(sprintf('File "%s" does not exists!', $fileName));

            return;
        }
        $csvReader = $this->initializeCsvReader($fileName);
        $testMod = $this->testMode();

        /* Creating a currency transfer coefficient.
                $swap = new Builder();
                $swap = $swap->build();
                $rate = $swap->latest('USD/GBP');
                $conversionFactor = $rate->getValue();
        */
        $conversionFactor = 0.76;

        $errors = [];
        foreach ($csvReader->getRecords() as $row) {
            $errors = $this->productDataValidation->validation($row, $errors, $conversionFactor);
        }
        try {
            $this->entityManager->beginTransaction();
            $addedProducts = $this->handler->execute($csvReader->jsonSerialize(), $errors, $testMod);
            $this->entityManager->commit();
        } catch (Exception $exception) {
            $this->entityManager->rollback();
            $this->terminalMessage->error(
                sprintf('The command was not completed! Error: "%s"', $exception->getMessage())
            );

            return;
        }

        $products = $csvReader->count();
        $this->terminalMessage->success(
            sprintf(
                '%s product(s) processed:"%s" product(s) changed and "%s" product(s) did not change.',
                $products,
                $addedProducts,
                $products - $addedProducts
            )
        );
        if ($errors) {
            $this->errorsDisplay($errors);
        }
    }

    private function testMode(): bool
    {
        $testMod = false;
        $answer = $this->terminalMessage->ask('Do you want to execute the command it test mode?', 'no');
        if ($answer !== 'no') {
            $testMod = self::TEST_MOD_ACTIVE;
            $this->terminalMessage->note('Test mode is active.');
        }

        return $testMod;
    }

    private function initializeCsvReader(string $fileName): Reader
    {
        $csv = Reader::createFromPath($fileName, 'r');

        return $csv->setHeaderOffset(0);
    }

    private function errorsDisplay(array $errors): void
    {
        $this->terminalMessage->note('Product(s) witch did not write into database.');
        foreach ($errors as $code => $error) {
            $this->terminalMessage->text(
                sprintf('Product code "%s" with error message(s): %s', $code, implode(';', $error))
            );
        }
    }
}
