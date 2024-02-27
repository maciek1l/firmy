<?php

namespace App\Command;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:eksport-firmy',
    description: 'Add a short description for your command',
)]
class EksportFirmyCommand extends Command
{
    public function __construct(private HttpClientInterface $client)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description');
    }

    protected function eksport(HttpClientInterface $client): int
    {
        $all = [];
        $pageCount = 1;

        $query = json_decode(file_get_contents('query.json'), true);

        for ($i = 0; $i < $pageCount; $i++) {
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
                if ($statusCode == 429) { // zbyt wiele zapytań
                    $i--;
                    continue;
                } else {
                    file_put_contents('progres.txt', 'error');
                    return $statusCode;
                }
            }

            $content = $response->toArray();

            $all = array_merge($all, $content['firmy']);
            file_put_contents('all.json', json_encode($all));

            file_put_contents('progres.txt', $i);

            $pageCount = ceil($content['count'] / 25);
            file_put_contents('pageCount.txt', $pageCount);
            sleep(3);
        }

        $spreadsheet = new Spreadsheet();
        $activeWorksheet = $spreadsheet->getActiveSheet();
        foreach ($all as $i => $v) {
            $activeWorksheet->setCellValue('A' . $i + 1, $v['nazwa']);
            $activeWorksheet->setCellValue('B' . $i + 1, $v['id']);
            $activeWorksheet->setCellValue('C' . $i + 1, $v['status']);
            $activeWorksheet->setCellValue('D' . $i + 1, isset($v['adresDzialalnosci']['wojewodztwo']) ? $v['adresDzialalnosci']['wojewodztwo'] : 'brak');
            $activeWorksheet->setCellValue('E' . $i + 1, isset($v['adresDzialalnosci']['miasto']) ? $v['adresDzialalnosci']['miasto'] : 'brak');
            $activeWorksheet->setCellValue('F' . $i + 1, $v['dataRozpoczecia']);
        }
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(64);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(14);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(16);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(12);
        $writer = new Xlsx($spreadsheet);
        $writer->save('eksport.xlsx');

        file_put_contents('progres.txt', 'completed');
        return 200;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->note("started");
        $statusCode = $this->eksport($this->client);
        $io->note("completed");

        if ($statusCode == 200) {
            $io->success('sukces');
            return Command::SUCCESS;
        } else {
            $io->note(sprintf('error: %s', $statusCode));
            return Command::FAILURE;
        }
    }
}
