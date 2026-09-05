<?php

namespace App\User\Command;

use App\User\Manager\GroupManager;

use Exception;
use App\User\Manager\UserManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:populate:user', description: 'Populate Core User account.')]
class CreateSuperAdminCommand extends Command
{
    public function __construct(
        private readonly UserManager $userManager,
        private readonly GroupManager $groupManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('app:populate:user')
            ->setDescription('Populate Core User account.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $created = TRUE;
        $userManager = $this->userManager;

        $userName = $this->getNonEmptyInput($output, 'Enter user name for super admin:');
        $user = $userManager->findUserByUsername($userName);

        if ($user != NULL) {

            $created = FALSE;
            $output->writeln('<info>User admin already exist.</info>');
            $password = trim($this->getNewPassword($output));

            if (!empty($password)) {
                $user->setPlainPassword($password);
            }

        } else {
            $user = $this->createUser($userManager, $output, $userName);
        }

        $user->addGroup($this->getSuperAdminUserGroup());

        $userManager->updateUser($user);

        if ($created) {
            $output->writeln('<info>User has been created successfully.</info>');
        }
    
        return Command::SUCCESS;
    }

    protected function createUser(UserManager $userManager, OutputInterface $output, $userName)
    {
        $output->writeln('<info>Populating User.</info>');

        $email = $this->getNonEmptyInput($output, 'Please choose an email:');
        $password = $this->getNonEmptyInput($output, 'Please choose a password:');

        $user = $userManager->createUser();
        $user->setUsername($userName);
        $user->setEmail($email);
        $user->setPlainPassword($password);
        $user->setEnabled(TRUE);

        return $user;
    }

    protected function getSuperAdminUserGroup($groupName = 'Super Administrator')
    {
        $groupManager = $this->groupManager;
        $group = $groupManager->findGroupByName($groupName);

        if (!$group) {
            $group = $groupManager->createGroup($groupName);
            $group->setRoles(['ROLE_SUPER_ADMIN']);
            $groupManager->updateGroup($group, FALSE);
        }

        return $group;
    }

    /**
     * @param OutputInterface $output
     *
     * @return mixed
     */
    protected function getNewPassword(OutputInterface $output)
    {
        return $this->getHelper('dialog')->ask(
            $output,
            'Enter new password(leave it blank to Keep old one):',
            ''
        );
    }

    /**
     * @param OutputInterface $output
     * @param                 $msg
     *
     * @return mixed
     */
    protected function getNonEmptyInput(OutputInterface $output, $msg)
    {
        return $this->getHelper('dialog')->askAndValidate(
            $output,
            $msg,
            function ($value) {
                if (empty($value)) {
                    throw new Exception('Value can not be empty');
                }

                return $value;
            }
        );
    }
}
