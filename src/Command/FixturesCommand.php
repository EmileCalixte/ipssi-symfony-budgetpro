<?php

namespace App\Command;

use App\Entity\Card;
use App\Entity\Subscription;
use App\Entity\User;
use App\Utils\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class FixturesCommand extends Command
{
    protected static $defaultName = 'app:fixtures';

    private $em;

    public function __construct(EntityManagerInterface $em, string $name = null)
    {
        $this->em = $em;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setDescription('Insert fake data in database')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Database will be cleared and 10 users, 20 credit cards and 3 subscriptions will be inserted. Type \'yes\' or \'y\' to continue: ', false);

        if(!$helper->ask($input, $output, $question)) {
            return;
        }

        Utils::truncateDatabaseTables($this->em);

        $subscriptions = [];
        $users = [];
        $cards = [];

        for($i = 0; $i < 3; ++$i) {
            $subscription = new Subscription();
            $subscription->setName(Utils::getRandomString(8));
            $subscription->setSlogan(Utils::getRandomString(random_int(24, 32)));
            $this->em->persist($subscription);
            $subscriptions[] = $subscription;
            $output->writeln(sprintf('Created subscription - Name: %s ; Slogan: %s', $subscription->getName(), $subscription->getSlogan()));
        }

        for($i = 0; $i < 10; ++$i) {
            $user = new User();
            $user->setFirstname(Utils::getRandomString(random_int(4, 10)));
            $user->setLastname(Utils::getRandomString(random_int(4, 10)));
            $user->setEmail(strtolower(Utils::getRandomString(random_int(8, 16)) . '@' . Utils::getRandomString(random_int(5, 8)) . '.' . Utils::getRandomString(random_int(2, 3))));
            $user->setApiKey(Utils::generateApiKey());
            $user->setCreatedAt(new \DateTimeImmutable());
            $user->setAddress(Utils::getRandomString(random_int(16, 24)));
            $user->setCountry(Utils::getRandomString(random_int(4, 10)));
            $user->setRoles(['ROLE_USER']);
            $user->setSubscription($subscriptions[random_int(0, count($subscriptions)-1)]);
            $this->em->persist($user);
            $users[] = $user;
            $output->writeln(sprintf('Created user - Name: %s %s ; Subscription name: %s', $user->getFirstname(), $user->getLastname(), $user->getSubscription()->getName()));
        }

        for($i = 0; $i < 20; ++$i) {
            $card = new Card();
            $card->setName(Utils::getRandomString(random_int(4, 8)));
            $card->setCreditCardType(Utils::getRandomString(random_int(4, 6)));
            $card->setCreditCardNumber(Utils::getRandomString(16, '123456789'));
            $card->setCurrencyCode(strtoupper(Utils::getRandomString(3)));
            $card->setValue(random_int(0, 100000));
            $card->setUser($users[random_int(0, count($users)-1)]);
            $this->em->persist($card);
            $cards[] = $card;
            $output->writeln(sprintf('Created card - Name: %s ; User name: %s %s', $card->getName(), $card->getUser()->getFirstname(), $card->getUser()->getLastname()));
        }

        $users[0]->setRoles(['ROLE_USER', 'ROLE_ADMIN']);

        $this->em->flush();

        $io->success(sprintf('Data inserted! Admin API key: %s, a user API key: %s', $users[0]->getApiKey(), $users[1]->getApiKey()));
    }
}
