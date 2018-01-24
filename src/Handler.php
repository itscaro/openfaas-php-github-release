<?php

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class Handler
{
    private const CONTENT_TYPE_JSON = 'application/json';
    private const CONTENT_TYPE_BINARY = 'binary/octet-stream';

    private $client;
    private $example;

    public function __construct() {
        $this->client = new Client([]);

        $this->example = json_encode([
            'repo' => 'owner/repo',
            'arch' => 'linux,windows,arm64,armhf,darwin',
        ]);
    }

    /**
     * @param string $data
     */
    public function handle(string $data): void {
        $download = $_SERVER['Http_Content_Type'] === self::CONTENT_TYPE_BINARY;
        if (\in_array($_SERVER['Http_Content_Type'], [self::CONTENT_TYPE_JSON, self::CONTENT_TYPE_BINARY], true)) {
            $data = json_decode($data, true);
            if ($data === null || empty($data['repo'])) {
                echo $this->example;
                return;
            } else {
                $repo = $data['repo'];
                $arch = $data['arch'] ?? null;
            }
        } else {
            echo "Please use JSON in this format:\n" . $this->example;
            return;
        }

        try {
            $response = $this->client->get('https://api.github.com/repos/'.$repo.'/releases/latest');
            $jsonResponse = json_decode($response->getBody()->getContents(), true);

            if ($jsonResponse === null || empty($jsonResponse['tag_name'])) {
                echo json_encode([
                    'error' => 'Could not find tag'
                ]);
                return;
            }

            $resData = [
                'version' => $jsonResponse['tag_name'],
            ];

            foreach ($jsonResponse['assets'] as $asset) {
                if ($this->isArch($arch, $asset['browser_download_url'])) {
                    $resData['name'] = $asset['name'];
                    $resData['url'] = $asset['browser_download_url'];

                    if ($download && !empty($resData['url'])) {
                        $tmpFile = sprintf("/tmp/%s-%s-%s", $resData['name'], $resData['version'], md5($resData['url']));

                        if (!file_exists($tmpFile)) {
                            $this->client->get($resData['url'], [\GuzzleHttp\RequestOptions::SINK => $tmpFile]);
                        }

                        echo file_get_contents($tmpFile);
                    }
                }
            }

            echo json_encode($resData);
        } catch (RequestException $e) {
            echo $e->getMessage();
        }
    }

    /**
     * @param string $arch
     * @param string $asset
     *
     * @return bool
     */
    private function isArch($arch, string $asset): bool {
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
