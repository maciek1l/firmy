<?php

namespace App\Controller;

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
