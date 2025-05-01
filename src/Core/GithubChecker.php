<?php

namespace Comba\Core;

use function Comba\Functions\sanitizeID;

class GithubChecker extends Cache
{
    protected int $lifetime = 60 * 60 * 3;

    public function getCachePrefix(): string
    {
        return 'GithubChecker';
    }

    /** Повертає version з composer.json проекту з github
     * @param string $name
     * @param string $githubRepo
     * @param string|null $localVersion
     * @return string|null
     */
    public function checkLatestGithubVersion(string $name, string $githubRepo, ?string $localVersion): ?string
    {
        $this->setFilename(sanitizeID($githubRepo));
        $remoteVersion = $this->get();

        if (empty($remoteVersion)) {

            $apiUrl = "https://api.github.com/repos/{$githubRepo}/contents/composer.json";

            $options = [
                'http' => [
                    'method' => 'GET',
                    'header' => [
                        'User-Agent: PHP'
                    ]
                ]
            ];

            $context = stream_context_create($options);

            $response = file_get_contents($apiUrl, false, $context);
            if ($response === FALSE) {
                return null;
            }

            $data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return null;
            }

            $composerContent = base64_decode($data['content']);
            $composerJson = json_decode($composerContent, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return null;
            }

            $remoteVersion = $composerJson['version'] ?? '0.0.0';

            $this->setLifetime($this->lifetime)
                ->set(json_encode(["version" => $remoteVersion]));
        } else {
            $composerJson = json_decode($remoteVersion, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $remoteVersion = $composerJson['version'] ?? '0.0.0';
            }
        }

        if (version_compare($remoteVersion, $localVersion, '>')) {
            return $remoteVersion;
        }

        return null;
    }
}
