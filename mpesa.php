<?php
// Mpesa.php
class Mpesa
{
    private $consumerKey;
    private $consumerSecret;
    private $shortcode;
    private $baseUrl;

    public function __construct(array $config)
    {
        $this->consumerKey    = $config['consumer_key'];
        $this->consumerSecret = $config['consumer_secret'];
        $this->shortcode      = $config['shortcode'];
        $this->baseUrl        = rtrim($config['base_url'], '/');
    }

    /** Request an OAuth token */
    public function getAccessToken(): string
    {
        $credentials = base64_encode("{$this->consumerKey}:{$this->consumerSecret}");
        $ch = curl_init("{$this->baseUrl}/oauth/v1/generate?grant_type=client_credentials");
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => ["Authorization: Basic {$credentials}"],
            CURLOPT_RETURNTRANSFER => true,
        ]);
        $resp = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (empty($resp['access_token'])) {
            throw new RuntimeException("Mpesa OAuth error: " . json_encode($resp));
        }
        return $resp['access_token'];
    }

    /**
     * Simulate a C2B payment in the sandbox
     *
     * @param string $msisdn       12‑digit phone number, e.g. 254712345678
     * @param string $billRef      Student number or account reference
     * @param float  $amount       Amount to pay
     */
    public function simulateC2B(string $msisdn, string $billRef, float $amount): array
    {
        $token = $this->getAccessToken();
        $payload = [
            'ShortCode'     => $this->shortcode,
            'CommandID'     => 'CustomerPayBillOnline',
            'Amount'        => (string)$amount,
            'Msisdn'        => $msisdn,
            'BillRefNumber' => $billRef,
        ];

        $ch = curl_init("{$this->baseUrl}/mpesa/c2b/v1/simulate");
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => [
                "Authorization: Bearer {$token}",
                'Content-Type: application/json'
            ],
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
        ]);
        $resp = json_decode(curl_exec($ch), true);
        curl_close($ch);

        return $resp;
    }
}
