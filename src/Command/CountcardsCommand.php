<?php

namespace App\Command;

use App\Provider\UserProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CountcardsCommand extends Command
{
    protected static $defaultName = 'app:countcards';

    private $userProvider;

    public function __construct(UserProvider $userProvider, $name = null)
    {
        $this->userProvider = $userProvider;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setDescription('Displays the number of cards for the specified user')
            ->addArgument('email', InputArgument::REQUIRED, 'User email')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');

        $user = $this->userProvider->getUserByEmail($email);

        if(is_null($user)) {
            $io->error('User not found');
            return;
        }

        $cardCount = $user->getCards()->count();

        $io->success(sprintf('This user has %s cards.', $cardCount));
    }
}
