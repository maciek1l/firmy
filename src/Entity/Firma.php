<?php

namespace App\Entity;

use App\Repository\FirmaRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FirmaRepository::class)]
class Firma
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $Nazwa = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $NIP = null;

    #[ORM\Column(length: 6, nullable: true)]
    private ?string $KodPocztowy = null;

    #[ORM\Column(length: 16, nullable: true)]
    private ?string $Telefon = null;

    #[ORM\Column(length: 320, nullable: true)]
    private ?string $Email = null;

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $PKD = null;

    #[ORM\Column(length: 36)]
    private ?string $Identyfikator = null;

    #[ORM\Column(length: 35)]
    private ?string $Status = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNazwa(): ?string
    {
        return $this->Nazwa;
    }

    public function setNazwa(?string $Nazwa): static
    {
        $this->Nazwa = $Nazwa;

        return $this;
    }

    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getNIP(): ?string
    {
        return $this->NIP;
    }

    public function setNIP(?string $NIP): static
    {
        $this->NIP = $NIP;

        return $this;
    }

    public function getKodPocztowy(): ?string
    {
        return $this->KodPocztowy;
    }

    public function setKodPocztowy(?string $KodPocztowy): static
    {
        $this->KodPocztowy = $KodPocztowy;

        return $this;
    }

    public function getTelefon(): ?string
    {
        return $this->Telefon;
    }

    public function setTelefon(?string $Telefon): static
    {
        $this->Telefon = $Telefon;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->Email;
    }

    public function setEmail(?string $Email): static
    {
        $this->Email = $Email;

        return $this;
    }

    public function getPKD(): ?string
    {
        return $this->PKD;
    }

    public function setPKD(string $PKD): static
    {
        $this->PKD = $PKD;

        return $this;
    }

    public function getIdentyfikator(): ?string
    {
        return $this->Identyfikator;
    }

    public function setIdentyfikator(string $Identyfikator): static
    {
        $this->Identyfikator = $Identyfikator;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->Status;
    }

    public function setStatus(string $Status): static
    {
        $this->Status = $Status;

        return $this;
    }
}
