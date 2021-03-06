<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SubscriptionRepository")
 */
class Subscription
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"subscriptions", "adminSubscriptions", "user","profile"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"subscriptions", "adminSubscriptions", "user","profile"})
     * @Assert\NotBlank()
     * @Assert\Length(max=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"subscriptions", "adminSubscriptions", "user","profile"})
     * @Assert\NotBlank()
     * @Assert\Length(max=255)
     */
    private $slogan;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"subscriptions", "adminSubscriptions", "user","profile"})
     * @Assert\Length(max=255)
     */
    private $url;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\User", mappedBy="subscription")
     * @Groups({"adminSubscriptions"})
     */
    private $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

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

    public function getSlogan(): ?string
    {
        return $this->slogan;
    }

    public function setSlogan(string $slogan): self
    {
        $this->slogan = $slogan;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
            $user->setSubscription($this);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->users->contains($user)) {
            $this->users->removeElement($user);
            // set the owning side to null (unless already changed)
            if ($user->getSubscription() === $this) {
                $user->setSubscription(null);
            }
        }

        return $this;
    }
}
