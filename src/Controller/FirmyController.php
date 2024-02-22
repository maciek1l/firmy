<?php

namespace App\Controller;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
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
        $token = 'Bearer eyJraWQiOiJjZWlkZyIsImFsZyI6IkhTNTEyIn0.eyJnaXZlbl9uYW1lIjoiT2xnYSIsInBlc2VsIjoiNjcwNjE1MDU3NjMiLCJpYXQiOjE2OTQ1OTc2MjQsImZhbWlseV9uYW1lIjoiV2l0Y3phayAiLCJjbGllbnRfaWQiOiJVU0VSLTY3MDYxNTA1NzYzLU9MR0EtV0lUQ1pBSyAifQ.0Znz2vzVJe96l3Eg62NCfwBDy3vOR_yx7EuPfFm_ghax2hanXhqnd89a-NMOHvI1Mq6fvxaQFdak1-bNwywR2A';
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
                    'Authorization' => $token
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
    public function eksport(HttpClientInterface $client): Response
    {
        $all = [];
        $pagesCount = 1;

        $token = 'Bearer eyJraWQiOiJjZWlkZyIsImFsZyI6IkhTNTEyIn0.eyJnaXZlbl9uYW1lIjoiT2xnYSIsInBlc2VsIjoiNjcwNjE1MDU3NjMiLCJpYXQiOjE2OTQ1OTc2MjQsImZhbWlseV9uYW1lIjoiV2l0Y3phayAiLCJjbGllbnRfaWQiOiJVU0VSLTY3MDYxNTA1NzYzLU9MR0EtV0lUQ1pBSyAifQ.0Znz2vzVJe96l3Eg62NCfwBDy3vOR_yx7EuPfFm_ghax2hanXhqnd89a-NMOHvI1Mq6fvxaQFdak1-bNwywR2A';
        $query = array_filter($_GET);

        for ($i = 0; $i < $pagesCount; $i++) {
            $query['page'] = $i;
            $response = $client->request(
                'GET',
                'https://dane.biznes.gov.pl/api/ceidg/v2/firmy',
                [
                    'headers' => [
                        'Authorization' => $token
                    ],
                    'query' => $query,
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

            $all = array_merge($all, $content['firmy']);

            $pagesCount = ceil($content['count'] / 25);
        }
        // dd($all);

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

        return $this->file($this->getParameter('kernel.project_dir') . '\public\eksport.xlsx');
    }

    #[Route('/firma/{id}', name: 'firma')]
    public function firma($id, HttpClientInterface $client): Response
    {
        $token = 'Bearer eyJraWQiOiJjZWlkZyIsImFsZyI6IkhTNTEyIn0.eyJnaXZlbl9uYW1lIjoiT2xnYSIsInBlc2VsIjoiNjcwNjE1MDU3NjMiLCJpYXQiOjE2OTQ1OTc2MjQsImZhbWlseV9uYW1lIjoiV2l0Y3phayAiLCJjbGllbnRfaWQiOiJVU0VSLTY3MDYxNTA1NzYzLU9MR0EtV0lUQ1pBSyAifQ.0Znz2vzVJe96l3Eg62NCfwBDy3vOR_yx7EuPfFm_ghax2hanXhqnd89a-NMOHvI1Mq6fvxaQFdak1-bNwywR2A';
        $response = $client->request(
            'GET',
            "https://dane.biznes.gov.pl/api/ceidg/v2/firma/$id",
            [
                'headers' => [
                    'Authorization' => $token
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
