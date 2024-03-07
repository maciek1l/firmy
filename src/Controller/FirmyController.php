<?php

namespace App\Controller;

use App\Repository\FirmaRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FirmyController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('index.html.twig');
    }

    #[Route('/firmy', name: 'firmy')]
    public function firmy(HttpClientInterface $client): Response
    {
        // $query = $_GET;
        // if (!$query['status']) {
        //     unset($query['status']);
        // }
        $query = array_filter($_GET);
        $response = $client->request(
            'GET',
            'https://dane.biznes.gov.pl/api/ceidg/v2/firmy',
            [
                'headers' => [
                    'Authorization' => TOKEN
                ],
                'query' => $query
            ]
        );
        $statusCode = $response->getStatusCode();
        if ($statusCode != 200) {
            switch ($statusCode) {
                case 204:
                    return new Response("Znaleziono 0 wyników");
                    break;
                case 429:
                    return new Response("Spróbuj ponownie później");
                    break;
                default:
                    return new Response($statusCode);
                    break;
            }
        }

        if (!$response->getContent()) {
            return new Response('Nie ma');
        }
        $content = $response->toArray();

        return $this->render('firmy.html.twig', [
            'content' => $content,
            'query' => $query
        ]);
    }

    #[Route('/eksport', name: 'eksport')]
    public function eksport(KernelInterface $kernel): Response
    {
        $progres = file_get_contents('progres.txt');
        if ($progres != "completed" && $progres != "error") {
            return new Response('<a href="/pobierz">Już pobieram</a>, spróbuj ponownie później.');
        }

        file_put_contents('query.json', json_encode(array_filter($_GET)));
        file_put_contents('progres.txt', "0");

        $process = new Process(['php', '../bin/console', 'app:eksport-firmy']);
        $process->setOptions(['create_new_console' => true]);
        $process->start();
        return $this->redirectToRoute('pobierz');

        // $application = new Application($kernel);
        // $application->setAutoExit(false);

        // $input = new ArrayInput([
        //     'command' => 'app:eksport-firm',
        // ]);

        // // You can use NullOutput() if you don't need the output
        // $output = new BufferedOutput();
        // $application->run($input, $output);

        // // return the output, don't use if you used NullOutput()
        // $content = $output->fetch();
        // return new Response($content);
    }

    #[Route('/pobierz', name: 'pobierz')]
    public function pobierz(): Response
    {
        $progres = file_get_contents('progres.txt');

        $pageCount = file_get_contents('pageCount.txt');
        if ($progres == "error") {
            return new Response('error');
        }
        if ($progres == "completed") {
            $progres = $pageCount;
        }
        $procent = floor($progres / $pageCount * 100);

        return $this->render('pobierz.html.twig', [
            'progres' => $progres,
            'pageCount' => $pageCount,
            'procent' => $procent,
        ]);
    }

    #[Route('/convert', name: 'convert')]
    public function convert(FirmaRepository $repo): Response
    {
        $all = $repo->findAll();

        $spreadsheet = new Spreadsheet();
        $activeWorksheet = $spreadsheet->getActiveSheet();
        foreach ($all as $i => $v) {
            $activeWorksheet->setCellValue('A' . $i + 1, $v->getNazwa());
            $activeWorksheet->setCellValue('B' . $i + 1, $v->getNIP());
            $activeWorksheet->setCellValue('C' . $i + 1, $v->getStatus());
            $activeWorksheet->setCellValue('D' . $i + 1, $v->getEmail());
            $activeWorksheet->setCellValue('E' . $i + 1, $v->getTelefon());
            $activeWorksheet->setCellValue('F' . $i + 1, $v->getKodPocztowy());
            $activeWorksheet->setCellValue('G' . $i + 1, $v->getPKD());
        }
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(64);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(14);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(8);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(8);
        $writer = new Xlsx($spreadsheet);
        $writer->save('eksport.xlsx');

        // return new Response('<a href="/eksport.xlsx">pobierz</a>');
        return $this->file('eksport.xlsx');
    }

    #[Route('/firma/{id}', name: 'firma')]
    public function firma($id, HttpClientInterface $client): Response
    {
        $response = $client->request(
            'GET',
            "https://dane.biznes.gov.pl/api/ceidg/v2/firma/$id",
            [
                'headers' => [
                    'Authorization' => TOKEN
                ],
            ]
        );

        $statusCode = $response->getStatusCode();
        if ($statusCode != 200) {
            return new Response($statusCode);
        }
        if (!$response->getContent()) {
            return new Response('Nie ma');
        }
        $content = $response->toArray();

        return $this->render('firma.html.twig', [
            'content' => $content,
        ]);
    }
}
