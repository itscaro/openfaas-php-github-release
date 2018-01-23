<?php

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class Handler
{
    private $client;
    private $example;

    public function __construct() {
        $this->client = new Client([
            'base_uri' => 'https://api.github.com',
        ]);

        $this->example = json_encode([
            'repo' => 'owner/repo',
            'arch' => 'linux,windows,arm64,armhf,darwin',
        ]);
    }

    public function handle(string $data): void {
        if ($_SERVER['Http_Content_Type'] === 'application/json') {
            $data = json_decode($data, true);
            if ($data === null || !isset($data['repo'])) {
                echo $this->example;
                return;
            } else {
                $repo = $data['repo'];
                $arch = $data['arch'];
            }
        } else {
            echo "Please use JSON in this format:\n" . $this->example;
            return;
        }

        try {
            $response = $this->client->get('/repos/'.$repo.'/releases/latest');
            $jsonResponse = json_decode($response->getBody()->getContents(), true);

            $data = [
                'version' => $jsonResponse['tag_name'],
            ];

            foreach ($jsonResponse['assets'] as $asset) {
                if ($this->isArch($arch, $asset['browser_download_url'])) {
                    $data['name'] = $asset['name'];
                    $data['url'] = $asset['browser_download_url'];
                }
            }

            echo json_encode($data);
        } catch (RequestException $e) {
            echo $e->getMessage();
        }
    }

    private function isArch($arch, $asset) {
        switch ($arch) {
            case 'windows':
                return strpos($asset, '.exe') !== false;
            case 'darwin':
            case 'arm64':
            case 'armhf':
                return strpos($asset, $arch) !== false;
            case 'linux':
                return !preg_match("/(\.exe|darwin|arm)/", $asset);
        }
    }
}
