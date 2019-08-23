<?php


namespace App\Utils;


use App\Entity\Card;
use App\Entity\Subscription;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class Utils
{
    public static function getRandomString(int $length, string $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'): string
    {
        $randomString = '';
        $charactersCount = mb_strlen($characters);
        for($i = 0; $i < $length; ++$i) {
            $randomString .= $characters[random_int(0, $charactersCount-1)];
        }
        return $randomString;
    }

    public static function generateApiKey(): string
    {
        return self::getRandomString(32, '0123456789abcdef');
    }

    public static function truncateDatabaseTables(EntityManagerInterface $em)
    {
        $cardCmd = $em->getClassMetadata(Card::class);
        $userCmd = $em->getClassMetadata(User::class);
        $subscriptionCmd = $em->getClassMetadata(Subscription::class);
        $connection = $em->getConnection();
        $dbPlatform = $connection->getDatabasePlatform();
        $connection->query('SET FOREIGN_KEY_CHECKS=0');
        $cardQuery = $dbPlatform->getTruncateTableSQL($cardCmd->getTableName());
        $userQuery = $dbPlatform->getTruncateTableSQL($userCmd->getTableName());
        $subscriptionQuery = $dbPlatform->getTruncateTableSQL($subscriptionCmd->getTableName());
        $connection->executeUpdate($cardQuery);
        $connection->executeUpdate($userQuery);
        $connection->executeUpdate($subscriptionQuery);
        $connection->query('SET FOREIGN_KEY_CHECKS=1');
    }
}