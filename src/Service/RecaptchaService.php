<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class RecaptchaService
{
    private string $secretKey;

    public function __construct(
        private HttpClientInterface $httpClient,
        string $recaptchaSecretKey
    ) {
        $this->secretKey = $recaptchaSecretKey;
    }

    /**
     * Vérifie le token reCAPTCHA v3 et retourne le score (0.0 à 1.0)
     * Score >= 0.5 = humain, < 0.5 = bot probable
     */
    public function verify(string $token, string $action = ''): array
    {
        if (empty($token)) {
            return ['success' => false, 'score' => 0.0, 'error' => 'Token manquant'];
        }

        $response = $this->httpClient->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
            'body' => [
                'secret'   => $this->secretKey,
                'response' => $token,
            ],
        ]);

        $data = $response->toArray();

        return [
            'success' => $data['success'] ?? false,
            'score'   => $data['score'] ?? 0.0,
            'action'  => $data['action'] ?? '',
            'error'   => $data['error-codes'][0] ?? null,
        ];
    }

    public function isHuman(string $token, float $minScore = 0.5): bool
    {
        $result = $this->verify($token);
        return $result['success'] && $result['score'] >= $minScore;
    }
}
