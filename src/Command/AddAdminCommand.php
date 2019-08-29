<?php

namespace App\Command;

use App\Entity\Subscription;
use App\Entity\User;
use App\Provider\SubscriptionProvider;
use App\Provider\UserProvider;
use App\Utils\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class AddAdminCommand extends Command
{
    protected static $defaultName = 'app:add-admin';

    const ROLE_USER = 'ROLE_USER';
    const ROLE_ADMIN = 'ROLE_ADMIN';

    const USER_FIRSTNAME_MAX_LENGTH = 255;
    const USER_LASTNAME_MAX_LENGTH = 255;
    const USER_COUNTRY_MAX_LENGTH = 255;
    const USER_ADDRESS_MAX_LENGTH = 255;

    private $userProvider;
    private $subscriptionProvider;
    private $em;

    public function __construct(UserProvider $userProvider, SubscriptionProvider $subscriptionProvider, EntityManagerInterface $em, $name = null)
    {
        $this->userProvider = $userProvider;
        $this->subscriptionProvider = $subscriptionProvider;
        $this->em = $em;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setDescription('Set a user as admin or create a new admin user')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');

        $emailQuestion = new Question('Enter the email address of the new admin user: ');
        do {
            $userEmail = $helper->ask($input, $output, $emailQuestion);
            $userEmailIsValid = true;
            if(is_null($userEmail)) {
                $userEmailIsValid = false;
                $io->writeln('You must specify an email address!');
            } elseif(!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
                $userEmailIsValid = false;
                $io->writeln('This email address is not valid');
            }
        } while(!$userEmailIsValid);

        $user = $this->userProvider->getUserByEmail($userEmail);

        if(is_null($user)) {
            $createUserQuestion = new ConfirmationQuestion('This user does not exist yet. Do you want to create it ? [y/n]', false);

            if(!$helper->ask($input, $output, $createUserQuestion)) {
                return;
            }

            $user = $this->createUser($userEmail, $helper, $input, $output, $io);

            $this->em->persist($user);
            $this->em->flush();

            $io->success(sprintf('User created! API key : %s', $user->getApiKey()));
        }

    }

    private function createUser(string $userEmail, QuestionHelper $helper, InputInterface $input, OutputInterface $output, SymfonyStyle $io): User
    {
        $userFirstname = $this->askUserFirstname($helper, $input, $output, $io);
        $userLastname = $this->askUserLastname($helper, $input, $output, $io);
        $userCountry = $this->askUserCountry($helper, $input, $output, $io);
        $userAddress = $this->askUserAddress($helper, $input, $output, $io);
        $userSubscription = $this->askUserSubscription($helper, $input, $output, $io);

        $user = new User();
        $user->setFirstname($userFirstname);
        $user->setLastname($userLastname);
        $user->setEmail($userEmail);
        $user->setCountry($userCountry);
        $user->setAddress($userAddress);
        $user->setSubscription($userSubscription);

        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setApiKey(Utils::generateApiKey());
        $user->setRoles([self::ROLE_USER, self::ROLE_ADMIN]);

        return $user;
    }

    private function askUserFirstname(QuestionHelper $helper, InputInterface $input, OutputInterface $output, SymfonyStyle $io): string
    {
        $firstnameQuestion = new Question('Enter the firstname of this new admin user: ');
        do {
            $userFirstname = trim($helper->ask($input, $output, $firstnameQuestion));
            $userFirstnameIsValid = true;
            if(empty($userFirstname)) {
                $userFirstnameIsValid = false;
                $io->writeln('You must enter a firstname');
            } elseif(mb_strlen($userFirstname) > self::USER_FIRSTNAME_MAX_LENGTH) {
                $userFirstnameIsValid = false;
                $io->writeln('The firstname cannot exceed 255 characters');
            }
        } while(!$userFirstnameIsValid);

        return $userFirstname;
    }

    private function askUserLastname(QuestionHelper $helper, InputInterface $input, OutputInterface $output, SymfonyStyle $io): string
    {
        $lastnameQuestion = new Question('Enter the lastname of this new admin user: ');
        do {
            $userLastname = trim($helper->ask($input, $output, $lastnameQuestion));
            $userLastnameIsValid = true;
            if(empty($userLastname)) {
                $userLastnameIsValid = false;
                $io->writeln('You must enter a lastname');
            } elseif(mb_strlen($userLastname) > self::USER_LASTNAME_MAX_LENGTH) {
                $userLastnameIsValid = false;
                $io->writeln('The lastname cannot exceed 255 characters');
            }
        } while(!$userLastnameIsValid);

        return $userLastname;
    }

    private function askUserCountry(QuestionHelper $helper, InputInterface $input, OutputInterface $output, SymfonyStyle $io): ?string
    {
        $countryQuestion = new Question('Enter the country of this new admin user (optional): ');
        do {
            $userCountry = trim($helper->ask($input, $output, $countryQuestion));
            $userCountryIsValid = true;
            if(mb_strlen($userCountry) > self::USER_COUNTRY_MAX_LENGTH) {
                $userCountryIsValid = false;
                $io->writeln('The country cannot exceed 255 characters');
            }
        } while(!$userCountryIsValid);

        return $userCountry;
    }

    private function askUserAddress(QuestionHelper $helper, InputInterface $input, OutputInterface $output, SymfonyStyle $io): ?string
    {
        $addressQuestion = new Question('Enter the address of this new admin user (optional): ');
        do {
            $userAddress = trim($helper->ask($input, $output, $addressQuestion));
            $userAddressIsValid = true;
            if(mb_strlen($userAddress) > self::USER_ADDRESS_MAX_LENGTH) {
                $userAddressIsValid = false;
                $io->writeln('The address cannot exceed 255 characters');
            }
        } while(!$userAddressIsValid);

        return $userAddress;
    }

    private function askUserSubscription(QuestionHelper $helper, InputInterface $input, OutputInterface $output, SymfonyStyle $io): Subscription
    {
        $subscriptionsIds = $this->subscriptionProvider->getAllSubscriptionsIds();
        $subscriptionQuestion = new Question(sprintf('Enter the subscription ID of this new admin user (available IDs: %s): ', implode(', ', $subscriptionsIds)));
        do {
            $userSubscriptionId = $helper->ask($input, $output, $subscriptionQuestion);
            $userSubscriptionIsValid = true;
            if(!in_array($userSubscriptionId, $subscriptionsIds)) {
                $userSubscriptionIsValid = false;
                $io->writeln('This ID is not valid.');
            }
        } while(!$userSubscriptionIsValid);

        return $this->subscriptionProvider->getSubscriptionById($userSubscriptionId);
    }
}
