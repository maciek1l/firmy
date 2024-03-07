<?php

namespace App\Command;

use App\Entity\Firma;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

define('TOKEN', 'Bearer eyJraWQiOiJjZWlkZyIsImFsZyI6IkhTNTEyIn0.eyJnaXZlbl9uYW1lIjoiT2xnYSIsInBlc2VsIjoiNjcwNjE1MDU3NjMiLCJpYXQiOjE2OTQ1OTc2MjQsImZhbWlseV9uYW1lIjoiV2l0Y3phayAiLCJjbGllbnRfaWQiOiJVU0VSLTY3MDYxNTA1NzYzLU9MR0EtV0lUQ1pBSyAifQ.0Znz2vzVJe96l3Eg62NCfwBDy3vOR_yx7EuPfFm_ghax2hanXhqnd89a-NMOHvI1Mq6fvxaQFdak1-bNwywR2A');

#[AsCommand(
    name: 'app:eksport-firmy',
    description: 'Add a short description for your command',
)]
class EksportFirmyCommand extends Command
{
    public function __construct(private HttpClientInterface $client, private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description');
    }

    protected function eksport(HttpClientInterface $client, EntityManagerInterface $em): int
    {
        $query = json_decode(file_get_contents('public/query.json'), true);
        $progres = file_get_contents('public/progres.txt');
        if ($progres != "completed" && $progres != "error") {
            $progres = floor($progres / 25);
        } else {
            $progres = 0;
        }
        $pageCount = $progres + 1;

        for ($i = $progres; $i < $pageCount; $i++) {
            $query['page'] = $i;
            $response = $client->request(
                'GET',
                'https://dane.biznes.gov.pl/api/ceidg/v2/firmy',
                [
                    'headers' => [
                        'Authorization' => TOKEN
                    ],
                    'query' => $query,
                ]
            );
            $statusCode = $response->getStatusCode();
            if ($statusCode != 200) {
                echo "error: $statusCode\n";
                if ($statusCode == 429) { // zbyt wiele zapytań
                    $i--;
                    usleep(3600000);
                    continue;
                } else {
                    file_put_contents('public/progres.txt', "error");
                    return $statusCode;
                }
            }

            $content = $response->toArray();

            file_put_contents('public/pageCount.txt', $content['count']);

            $count = 0;
            $link = "";
            foreach ($content['firmy'] as $key => $firmaaa) {
                $id = $firmaaa['id'];
                // pomiń jeżeli już w bazie
                $exist = $em->getRepository(Firma::class)->findOneBy(['Identyfikator' => $id]);
                if ($exist) {
                    echo "pomijam: $id" . "\n";
                    continue;
                }
                // generowanie linku do szczegółów 5 firm
                $count++;
                $link .= "ids=" . $id . "&";
                if ($count % 5 && $key + 1 != count($content['firmy'])) continue;


                $statusCode2 = 429;
                while ($statusCode2 == 429) { // powtórz jeżeli przekroczono limit zapytań
                    $start = microtime(true);
                    try {
                    $response2 = $client->request(
                        'GET',
                        "https://dane.biznes.gov.pl/api/ceidg/v2/firma?$link",
                        [
                            'headers' => [
                                'Authorization' => TOKEN
                            ],
                        ]
                    );
                    } catch(Exception $e) {
                        echo "error:" . $e->getMessage();
                        usleep(3600000);
                        continue;
                    }
                    $end = microtime(true);
                    $timeToSpeep =  3600000 - ((1 - ($end - $start)) * 1000000);
                    if ($timeToSpeep < 0) $timeToSpeep = 0;

                    $statusCode2 = $response2->getStatusCode();
                    if ($statusCode2 != 200) {
                        echo "error: $statusCode2\n";
                        if ($statusCode2 == 429) { // zbyt wiele zapytań
                            usleep(3600000);
                            continue;
                        } else {
                            file_put_contents('public/progres.txt', "error");
                            return $statusCode2;
                        }
                    }
                }

                $content2 = $response2->toArray();
                foreach ($content2['firma'] as $firma) {
                    $newFirma = new Firma;
                    $newFirma->setIdentyfikator($firma['id']);
                    $newFirma->setNazwa($firma['nazwa']);
                    $newFirma->setStatus($firma['status']);
                    if (array_key_exists('pkdGlowny', $firma)) $newFirma->setPKD($firma['pkdGlowny']);
                    if (array_key_exists('email', $firma)) $newFirma->setEmail($firma['email']);
                    if (array_key_exists('telefon', $firma)) $newFirma->setTelefon($firma['telefon']);
                    if (array_key_exists('wlasciciel', $firma)) {
                        if (array_key_exists('nip', $firma['wlasciciel'])) $newFirma->setNIP($firma['wlasciciel']['nip']);
                    }
                    if (array_key_exists('adresDzialalnosci', $firma)) {
                        if (array_key_exists('kod', $firma['adresDzialalnosci'])) $newFirma->setKodPocztowy($firma['adresDzialalnosci']['kod']);
                    }
                    $em->persist($newFirma);
                }

                $progres = $i * 25 + $key + 1;
                echo "$i-$key $progres/{$content['count']}\n";
                file_put_contents('public/progres.txt', $progres);
                $link = "";
                usleep($timeToSpeep);
            }

            $em->flush();
            $em->clear();

            $pageCount = ceil($content['count'] / 25);
            usleep(3600000);
        }

        file_put_contents('public/progres.txt', 'completed');
        return 200;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->note("started");
        $statusCode = $this->eksport($this->client, $this->em);

        if ($statusCode == 200) {
            $io->success('sukces');
            return Command::SUCCESS;
        } else {
            $io->note(sprintf('error: %s', $statusCode));
            return Command::FAILURE;
        }
    }
}
