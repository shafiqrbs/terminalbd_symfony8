<?php

namespace App\User\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(name: 'app:install', description: 'App installer.')]
class InstallCommand extends Command
{
    protected $pathToBeCreated = [
        'images',
        'uploads'
    ];

    protected function configure(): void
    {
        $this->setName('app:install')
             ->setDescription('App installer.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Installing Application.</info>');
        $output->writeln('');

        $this->setupStep($input, $output);

        $output->writeln('<info>Application has been successfully installed.</info>');
    
        return Command::SUCCESS;
    }

    protected function setupStep(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Setting up database.</info>');

        $this->runCommand('doctrine:database:create', $input, $output);
        $this->runCommand('doctrine:schema:create', $input, $output);
        $this->runCommand('assets:install', $input, $output);
        $this->runCommand('assetic:dump', $input, $output);

        $output->writeln('');
        $output->writeln('<info>Administration setup.</info>');

        $this->runCommand('app:populate:user', $input, $output);
        $output->writeln('');

        $output->writeln('');

        return $this;
    }

    private function runCommand($command, InputInterface $input, OutputInterface $output)
    {
        $this->getApplication()
             ->find($command)
             ->run($input, $output);

        return $this;
    }

    protected function createCoreDirectories()
    {
        $fs = new Filesystem();

        foreach ($this->pathToBeCreated as $path) {
            if (!$fs->exists($path)) {
                $fs->mkdir($path);
                $fs->chmod($path, '0777');
            }
        }
    }
}
