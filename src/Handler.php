<?php

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class Handler
{
    private $client;

    public function __construct() {
        $this->client = new Client([
            'base_uri' => 'https://api.github.com',
        ]);
    }

    public function handle(string $data): void {
        if ($_SERVER['Http_Content_Type'] === 'application/json') {
            $data = json_decode($data, true);
            if ($data === null) {
                echo json_encode([
                    'repo' => 'owner/repo'
                ]);
                return;
            } else {
                $repo = $data['repo'];
            }
        } else {
            $repo = $data;
        }

        try {
            $response = $this->client->get('/repos/'.$repo.'/releases/latest');
            $jsonResponse = json_decode($response->getBody()->getContents(), true);

            printf('%s'."\n", $jsonResponse['tag_name']);

            foreach ($jsonResponse['assets'] as $asset) {
                printf('%s - %s'."\n", $asset['name'], $asset['browser_download_url']);
            }
        } catch (RequestException $e) {
            echo $e->getMessage();
        }
    }
}
