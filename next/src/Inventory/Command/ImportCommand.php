<?php

namespace App\Inventory\Command;

use Exception;
use Symfony\Component\Console\Helper\HelperInterface;
use App\Inventory\Importer\ExcelDataReader;
use App\Inventory\Importer\Excel;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

#[AsCommand(name: 'app-store:excel:import', description: 'Import data from excel')]
class ImportCommand extends Command
{
    public function __construct(
        private readonly ExcelDataReader $excelDataReader,
        private readonly Excel $excel,
    ) {
        parent::__construct();
    }

    protected $connection;

    /** @var  Excel */
    protected $importer;

    protected function configure(): void
    {
        $this->setName('app-store:excel:import')
            ->setDefinition([
                new InputOption('file', '', InputOption::VALUE_REQUIRED, 'Path for excel file'),
                new InputOption('ic-id', '', InputOption::VALUE_REQUIRED, 'Inventory Config ID')
            ])
            ->setHelp(<<<EOT
The <info>app-store:excel:import</info> command helps you import data from a excel file.

By default, the command interacts with the developer to get options.
Any passed option will be used as a default value for the interaction

<info>php app/console app-store:excel:import --fie=/path/to/your/excel.xls --ic-id=1</info>

If you want to disable any user interaction, use <comment>--no-interaction</comment> but don't forget to pass all needed options:

<info>php app/console app-store:excel:import --file=path  --ic-id=1 --no-interaction</info>
EOT
            )
            ->setDescription('Import data from excel');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $question = $this->getQuestionHelper();

        if ($input->isInteractive()) {
            if (!$question->
            ask($input, $output, new ConfirmationQuestion('Do you confirm import [yes]?', TRUE))
            ) {
                $output->writeln('<error>Command aborted</error>');
                return 1;
            }
        }

        $importer = $this->getImporter();
        $importer->setInventoryConfig($input->getOption('ic-id'));
        $importer->import($this->getDataReader()->getData($input->getOption('file')));

        $output->writeln('<info>Data has been successfully imported!</info>');
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $output->writeln('<info>Welcome to the Excel Importer tool</info>');
        $output->writeln('');
        $question = $this->getQuestionHelper();
        $data = [];
        $options = $this->getDefinition()->getOptions();
        $data['file'] = $this->getInteractiveData($options['file'], $input, $output, $question);
        $data['ic-id'] = $this->getInteractiveData($options['ic-id'], $input, $output, $question);

        $output->writeln([
            '',
            $this->getHelper('formatter')->formatBlock('Summary before importing', 'bg=blue;fg=white', TRUE),
            '',
            sprintf("We are going to use the following info to import:"),
            '',
        ]);
        foreach ($data as $item => $value) {
            $output->writeln(sprintf('Import using  %s : "<info>%s</info>"', $item, $value));
        }
    }

    protected function getInteractiveData(InputOption $inputOption, InputInterface $input, OutputInterface $output, QuestionHelper $question)
    {
        $value = NULL;
        $option = $inputOption->getName();
        try {
            $value = $input->getOption($option) ?: NULL;
        } catch (Exception $error) {
            $output->writeln($question->getHelperSet()->get('formatter')->formatBlock($error->getMessage(), 'error', false));
        }
        $description = trim($inputOption->getDescription());
        $defaultValue = $inputOption->getDefault();
        if (NULL === $value || $value === $inputOption->getDefault()) {
            $value = $question
                ->ask($input,
                    $output,
                    new Question("$description :", $defaultValue)
                );
            $input->setOption($option, $value);
        }
        return $value;
    }

    /**
     * @return QuestionHelper
     */
    protected function getQuestionHelper()
    {
        return $this->getHelperSet()->get('question');
    }

    /**
     * @return HelperInterface
     */
    protected function getProgressHelper()
    {
        return $this->getHelperSet()->get('progress');
    }

    /**
     * @return ExcelDataReader
     */
    protected function getDataReader()
    {
        return $this->excelDataReader;
    }


    /**
     * @return Excel
     */
    protected function getImporter()
    {
        return $this->excel;
    }
}
