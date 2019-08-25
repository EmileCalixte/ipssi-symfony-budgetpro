<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CardRepository")
 */
class Card
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"cards", "card", "user","profile","profileCards","adminCards"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"cards", "card", "user","profile","profileCards","adminCards"})
     * @Assert\NotBlank()
     * @Assert\Length(max=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"cards", "card", "user","profile","profileCards","adminCards"})
     * @Assert\NotBlank()
     * @Assert\Length(max=255)
     */
    private $creditCardType;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Groups({"card","profile","profileCards","adminCards"})
     * @Assert\NotBlank()
     * @Assert\Length(max=255)
     */
    private $creditCardNumber;

    /**
     * @ORM\Column(type="string", length=3)
     * @Groups({"card","profile","profileCards","adminCards"})
     * @Assert\NotBlank()
     * @Assert\Length(max=3)
     */
    private $currencyCode;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"card","profile","profileCards","adminCards"})
     * @Assert\NotBlank()
     */
    private $value;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="cards")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"card","adminCards"})
     * @Assert\NotBlank()
     */
    private $user;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCreditCardType(): ?string
    {
        return $this->creditCardType;
    }

    public function setCreditCardType(string $creditCardType): self
    {
        $this->creditCardType = $creditCardType;

        return $this;
    }

    public function getCreditCardNumber(): ?string
    {
        return $this->creditCardNumber;
    }

    public function setCreditCardNumber(string $creditCardNumber): self
    {
        $this->creditCardNumber = $creditCardNumber;

        return $this;
    }

    public function getCurrencyCode(): ?string
    {
        return $this->currencyCode;
    }

    public function setCurrencyCode(string $currencyCode): self
    {
        $this->currencyCode = $currencyCode;

        return $this;
    }

    public function getValue(): ?int
    {
        return $this->value;
    }

    public function setValue(int $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
