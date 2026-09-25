<?php
namespace App\Services;

use Illuminate\Support\Facades\Log;
use KHQR\BakongKHQR;
use KHQR\Exceptions\KHQRException;
use KHQR\Helpers\KHQRData;
use KHQR\Models\IndividualInfo;

class BakongService
{
    protected string $token;
    protected string $accountId;
    protected string $merchantName;
    protected string $merchantCity;
    protected int $currency;
    protected bool $isTest;

    public function __construct()
    {
        $this->token = trim((string) config('bakong.token', ''));

        $this->accountId = trim(
            (string) config('bakong.account_id', '')
        );

        $this->merchantName = trim(
            (string) config('bakong.merchant_name', 'KhShop')
        );

        $this->merchantCity = trim(
            (string) config('bakong.merchant_city', 'Phnom Penh')
        );

        $this->currency = (int) config(
            'bakong.currency',
            KHQRData::CURRENCY_USD
        );

        $this->isTest = (bool) config(
            'bakong.is_test',
            true
        );
    }

    /**
     * Check KHQR generation configuration.
     */
    public function getMissingKhqrConfig(): array
    {
        $missing = [];

        if ($this->accountId === '') {
            $missing[] = 'BAKONG_ACCOUNT_ID';
        }

        if ($this->merchantName === '') {
            $missing[] = 'BAKONG_MERCHANT_NAME';
        }

        if ($this->merchantCity === '') {
            $missing[] = 'BAKONG_MERCHANT_CITY';
        }

        return $missing;
    }

    /**
     * Validate Bakong account ID.
     *
     * Expected format:
     * account@bank
     */
    public function getInvalidKhqrConfig(): array
    {
        $invalid = [];

        if (
            $this->accountId !== '' &&
            substr_count($this->accountId, '@') !== 1
        ) {
            $invalid[] =
                'BAKONG_ACCOUNT_ID must use the format: account@bank';
        }

        return $invalid;
    }

    /**
     * Check API configuration.
     *
     * Token is NOT required for local KHQR generation.
     */
    public function getMissingApiConfig(): array
    {
        $missing = [];

        if (!$this->hasUsableApiToken()) {
            $missing[] = 'BAKONG_TOKEN';
        }

        return $missing;
    }

    /**
     * Token must be present and not a docs placeholder.
     */
    public function hasUsableApiToken(): bool
    {
        if ($this->token === '') {
            return false;
        }

        // .env.example / docs placeholders are not real tokens.
        if (preg_match('/^YOUR[_-]/i', $this->token) === 1) {
            return false;
        }

        return true;
    }

    /**
     * True when the failure is Bakong/network upstream (403/5xx/timeout),
     * not an application configuration problem.
     */
    public function isUpstreamUnavailableMessage(string $message): bool
    {
        $markers = [
            'CloudFront',
            'cURL error',
            'Connection refused',
            'Could not resolve',
            'Operation timed out',
            'timed out',
            'Request timed out',
            'UPSTREAM_UNAVAILABLE',
            ' 403',
            '403 ERROR',
            ' 429',
            ' 500',
            ' 502',
            ' 503',
            ' 504',
        ];

        foreach ($markers as $marker) {
            if (stripos($message, $marker) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Short, safe log line (avoid dumping full CloudFront HTML on every poll).
     */
    protected function shortError(string $message): string
    {
        $message = trim(preg_replace('/\s+/', ' ', $message) ?? $message);

        if (stripos($message, 'CloudFront') !== false) {
            return 'CloudFront 403 from Bakong upstream (geo-block/WAF or wrong host)';
        }

        if (strlen($message) > 240) {
            return substr($message, 0, 240) . '…';
        }

        return $message;
    }

    /**
     * Return safe configuration information for logs.
     */
    public function configPresence(): array
    {
        return [
            'token'         => $this->token !== '',
            'account_id'    => $this->accountId !== '',
            'merchant_name' => $this->merchantName !== '',
            'merchant_city' => $this->merchantCity !== '',
            'currency'      => $this->currency,
            'is_test'       => $this->isTest,
            'khqr_type'     => 'individual',
        ];
    }

    /**
     * Payment timeout in seconds (BAKONG_PAYMENT_TIMEOUT).
     */
    public function getPaymentTimeoutSeconds(): int
    {
        $timeout = (int) config('bakong.payment_timeout', 600);

        return $timeout > 0 ? $timeout : 600;
    }

    /**
     * Read Tag 99 create timestamp (sub-tag 00) from an SDK QR.
     *
     * Banking apps reject a rewritten Tag 99 (double timestamp). Never
     * mutate the QR — only parse for expires_at in the API response.
     *
     * Tag 99 value (SDK standard): 0013{created_ms}  (17 chars)
     */
    protected function parseQrCreatedAtMs(string $qr): int
    {
        $body = substr($qr, 0, -8);
        $pos = 0;
        $length = strlen($body);

        while ($pos + 4 <= $length) {
            $tag = substr($body, $pos, 2);
            $valueLength = (int) substr($body, $pos + 2, 2);
            $valueStart = $pos + 4;

            if ($valueStart + $valueLength > $length) {
                break;
            }

            if ($tag === '99') {
                $value = substr($body, $valueStart, $valueLength);

                if (preg_match('/^0013(\d{13})/', $value, $matches)) {
                    return (int) $matches[1];
                }

                break;
            }

            $pos = $valueStart + $valueLength;
        }

        return (int) floor(microtime(true) * 1000);
    }

    /**
     * Generate dynamic Individual KHQR.
     *
     * No Bakong API request is made here.
     *
     * @return array{
     *     qr: string,
     *     md5: string,
     *     expires_at: int
     * }
     */
    public function generateKhqr(
        float $amount,
        string $orderRef
    ): array {
        /*
         * ---------------------------------------------------------
         * 1. Validate configuration
         * ---------------------------------------------------------
         */
        $missing = $this->getMissingKhqrConfig();

        if (!empty($missing)) {
            throw new \RuntimeException(
                'Bakong KHQR is not configured. Missing: '
                . implode(', ', $missing)
            );
        }

        $invalid = $this->getInvalidKhqrConfig();

        if (!empty($invalid)) {
            throw new \InvalidArgumentException(
                'Invalid Bakong KHQR configuration: '
                . implode(', ', $invalid)
            );
        }

        /*
         * ---------------------------------------------------------
         * 2. Validate payment amount
         * ---------------------------------------------------------
         */
        if ($amount <= 0) {
            throw new \InvalidArgumentException(
                'KHQR payment amount must be greater than 0.'
            );
        }

        /*
         * ---------------------------------------------------------
         * 3. Log safe information only
         * ---------------------------------------------------------
         */
        Log::info('Generating Individual KHQR', [
            'order_ref' => $orderRef,
            'amount'    => $amount,
            'currency'  => $this->currency,
            'is_test'   => $this->isTest,
            'config'    => $this->configPresence(),
        ]);

        try {
            /*
             * -----------------------------------------------------
             * 4. Generate Individual KHQR
             *
             * We intentionally do NOT use MerchantInfo here.
             *
             * This means:
             * - no merchant ID
             * - no acquiring bank
             * - no empty merchant fields
             * -----------------------------------------------------
             */
            $billNumber = substr($orderRef, 0, 25);
            $nowMs = (int) floor(microtime(true) * 1000);
            $expirationTimestamp = strval(
                $nowMs + ($this->getPaymentTimeoutSeconds() * 1000)
            );

            $individualInfo = new IndividualInfo(
                bakongAccountID: $this->accountId,
                merchantName: $this->merchantName,
                merchantCity: $this->merchantCity,
                currency: $this->currency,
                amount: $amount,
                billNumber: $billNumber !== '' ? $billNumber : null,
                expirationTimestamp: $expirationTimestamp,
            );

            $response = BakongKHQR::generateIndividual(
                $individualInfo
            );

            /*
             * -----------------------------------------------------
             * 5. Validate SDK response
             * -----------------------------------------------------
             */
            $statusCode = $response->status['code'] ?? null;
            $statusMessage = $response->status['message'] ?? null;

            if ((int) $statusCode !== 0) {
                throw new \RuntimeException(
                    'KHQR generation failed: '
                    . ($statusMessage ?: 'Unknown SDK error')
                    . ' (code: '
                    . ($statusCode ?? 'n/a')
                    . ')'
                );
            }

            /*
             * -----------------------------------------------------
             * 6. Extract QR + MD5
             * -----------------------------------------------------
             */
            $data = $response->data;

            if (!is_array($data)) {
                throw new \RuntimeException(
                    'KHQR generation failed: SDK returned invalid data.'
                );
            }

            $qr = $data['qr'] ?? null;
            $md5 = $data['md5'] ?? null;

            if (!is_string($qr) || trim($qr) === '') {
                throw new \RuntimeException(
                    'KHQR generation failed: QR is empty.'
                );
            }

            if (!is_string($md5) || trim($md5) === '') {
                throw new \RuntimeException(
                    'KHQR generation failed: MD5 is empty.'
                );
            }

            $validation = BakongKHQR::verify($qr);

            if (!$validation->isValid) {
                throw new \RuntimeException(
                    'KHQR generation failed: SDK CRC verification failed.'
                );
            }

            /*
             * -----------------------------------------------------
             * 6b. Tag 99 is built by the SDK as
             *     0013{created_ms}0113{expires_ms} (34 chars).
             *     expires_at comes from that official expiration field.
             * -----------------------------------------------------
             */
            $createdMs = $this->parseQrCreatedAtMs($qr);
            $expiresAt = (int) $expirationTimestamp;
            if ($expiresAt < $createdMs) {
                $expiresAt = $createdMs + ($this->getPaymentTimeoutSeconds() * 1000);
            }

            $bodyForLog = substr($qr, 0, -8);
            $tag99Len = null;
            $logPos = 0;
            while ($logPos + 4 <= strlen($bodyForLog)) {
                $logTag = substr($bodyForLog, $logPos, 2);
                $logVl = (int) substr($bodyForLog, $logPos + 2, 2);
                if ($logTag === '99') {
                    $tag99Len = strlen(substr($bodyForLog, $logPos + 4, $logVl));
                    break;
                }
                $logPos += 4 + $logVl;
            }

            /*
             * -----------------------------------------------------
             * 7. Log only non-sensitive debug information
             * -----------------------------------------------------
             */
            Log::info('Individual KHQR generated successfully', [
                'order_ref'  => $orderRef,
                'amount'     => $amount,
                'currency'   => $this->currency,
                'md5'        => $md5,
                'qr_length'  => strlen($qr),
                'prefix'     => substr($qr, 0, 20),
                'tag99_len'  => $tag99Len,
                'expires_at' => $expiresAt,
                'timeout_s'  => $this->getPaymentTimeoutSeconds(),
                'is_test'    => $this->isTest,
            ]);

            return [
                'qr'         => $qr,
                'md5'        => $md5,
                'expires_at' => $expiresAt,
            ];

        } catch (KHQRException $e) {

            Log::error('KHQR SDK error', [
                'order_ref' => $orderRef,
                'amount'    => $amount,
                'exception' => $e::class,
                'code'      => $e->getCode(),
                'error'     => $e->getMessage(),
            ]);

            throw new \RuntimeException(
                'KHQR generation failed: ' . $e->getMessage()
            );

        } catch (\InvalidArgumentException $e) {
            throw $e;

        } catch (\RuntimeException $e) {
            throw $e;

        } catch (\Throwable $e) {

            Log::error('Unexpected KHQR generation error', [
                'order_ref' => $orderRef,
                'amount'    => $amount,
                'exception' => $e::class,
                'error'     => $e->getMessage(),
            ]);

            throw new \RuntimeException(
                'KHQR generation failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Check payment status using MD5.
     */
    public function checkPayment(string $md5): array
    {
        if (!$this->hasUsableApiToken()) {
            throw new \RuntimeException(
                'Bakong API is not configured. Missing: BAKONG_TOKEN.'
            );
        }

        $md5 = trim($md5);

        if ($md5 === '') {
            throw new \InvalidArgumentException(
                'Payment MD5 cannot be empty.'
            );
        }

        Log::info('Checking Bakong transaction', [
            'md5'     => $md5,
            'is_test' => $this->isTest,
        ]);

        try {
            $bakong = new BakongKHQR($this->token);

            $result = $bakong->checkTransactionByMD5(
                $md5,
                $this->isTest
            );

        } catch (KHQRException $e) {

            $raw = $e->getMessage();
            $isUpstream = $this->isUpstreamUnavailableMessage($raw)
                || $this->isUpstreamUnavailableMessage((string) $e->getCode());

            Log::error('Bakong transaction check failed', [
                'md5'       => $md5,
                'exception' => $e::class,
                'code'      => $e->getCode(),
                'error'     => $this->shortError($raw),
                'is_test'   => $this->isTest,
                'upstream'  => $isUpstream,
            ]);

            // Prefix lets the controller treat this as temporary (keep polling)
            // without mistaking it for missing config.
            throw new \RuntimeException(
                ($isUpstream ? 'UPSTREAM_UNAVAILABLE: ' : '')
                . 'Payment verification request failed: '
                . $this->shortError($raw)
            );

        } catch (\Throwable $e) {

            $raw = $e->getMessage();
            $isUpstream = true;

            Log::error('Bakong transaction check failed', [
                'md5'       => $md5,
                'exception' => $e::class,
                'error'     => $this->shortError($raw),
                'is_test'   => $this->isTest,
                'upstream'  => $isUpstream,
            ]);

            throw new \RuntimeException(
                'UPSTREAM_UNAVAILABLE: Payment verification request failed: '
                . $this->shortError($raw)
            );
        }

        if (!is_array($result)) {
            throw new \RuntimeException(
                'Payment verification returned an unexpected response.'
            );
        }

        $responseCode = $result['responseCode'] ?? null;
        $responseMessage = (string) ($result['responseMessage'] ?? '');
        $errorCode = $result['errorCode'] ?? null;
        $rawData = $result['data'] ?? null;
        $dataIsArray = is_array($rawData);

        // "Transaction could not be found" (responseCode 1 / errorCode 1,
        // data null) is the normal answer while the customer has not paid
        // yet — it is a status, NOT an upstream/server failure.
        $isNotFound =
            (int) $responseCode !== 0 &&
            (
                (int) $errorCode === 1 ||
                stripos($responseMessage, 'could not be found') !== false ||
                stripos($responseMessage, 'not found') !== false
            );

        if ($isNotFound) {
            Log::info('Bakong transaction not found (payment still pending)', [
                'md5'           => $md5,
                'response_code' => $responseCode,
                'error_code'    => $errorCode,
                'is_test'       => $this->isTest,
            ]);

            return [
                'status'         => 'pending',
                'transaction_id' => null,
                'raw'            => $result,
            ];
        }

        // API-level error (not a transaction status).
        // e.g. errorCode 17: "Daily request limit of 100 exceeded."
        // Must NOT fall through to status=pending — that hides real failures
        // and keeps the UI spinning as if the customer simply has not paid.
        if ((int) $responseCode !== 0) {
            $isRateLimit =
                (int) $errorCode === 17 ||
                stripos($responseMessage, 'daily request limit') !== false ||
                stripos($responseMessage, 'rate limit') !== false;

            Log::error('Bakong API error response', [
                'md5'              => $md5,
                'response_code'    => $responseCode,
                'response_message' => $responseMessage,
                'error_code'       => $errorCode,
                'data_is_array'    => $dataIsArray,
                'is_test'          => $this->isTest,
                'rate_limited'     => $isRateLimit,
            ]);

            // Prefix lets the controller return 503 temporary (keep polling
            // with backoff) instead of a fake 200 pending.
            throw new \RuntimeException(
                'UPSTREAM_UNAVAILABLE: Payment verification request failed: '
                . ($responseMessage !== ''
                    ? $responseMessage
                    : 'Bakong API error (responseCode='
                        . var_export($responseCode, true)
                        . ')')
            );
        }

        $data = $dataIsArray ? $rawData : [];

        $transactionId = $data['transactionId'] ?? $data['hash'] ?? null;
        $trackingStatus = $data['trackingStatus'] ?? null;
        $acknowledgedMs = $data['acknowledgedDateMs'] ?? null;

        $status = 'pending';

        if ((int) $trackingStatus === -1) {
            $status = 'failed';

        } elseif ((int) $trackingStatus === 1) {
            $status = 'completed';

        } elseif ($acknowledgedMs !== null && (int) $acknowledgedMs > 0) {
            // check_transaction_by_md5 leaves trackingStatus null even for
            // settled payments; acknowledgedDateMs is set once the receiver
            // has been credited — that is a completed payment.
            $status = 'completed';
        }

        Log::info('Bakong transaction result', [
            'md5'             => $md5,
            'response_code'   => $responseCode,
            'response_message'=> $responseMessage,
            'error_code'      => $errorCode,
            'data_is_array'   => $dataIsArray,
            'tracking_status' => $trackingStatus,
            'acknowledged_ms' => $acknowledgedMs,
            'status'          => $status,
            'transaction_id'  => $transactionId,
            'is_test'         => $this->isTest,
            'raw_keys'        => is_array($result) ? array_keys($result) : [],
            'data_keys'       => $dataIsArray ? array_keys($data) : [],
        ]);

        return [
            'status' => $status,
            'transaction_id' => is_string($transactionId)
                ? $transactionId
                : null,
            'raw' => $result,
        ];
    }
}

