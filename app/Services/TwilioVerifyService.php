<?php
/*
 * LaraClassifier - Classified Ads Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com
 * Author: Mayeul Akpovi (BeDigit - https://bedigit.com)
 *
 * LICENSE
 * -------
 * This software is provided under a license agreement and may only be used or copied
 * in accordance with its terms, including the inclusion of the above copyright notice.
 * As this software is sold exclusively on CodeCanyon,
 * please review the full license details here: https://codecanyon.net/licenses/standard
 */

namespace App\Services;

use Twilio\Rest\Client;
use Throwable;

class TwilioVerifyService extends BaseService
{
    protected ?Client $client = null;
    protected ?string $verifyServiceSid = null;

    public function __construct()
    {
        parent::__construct();
        
        $accountSid = config('twilio-notification-channel.account_sid');
        $authToken = config('twilio-notification-channel.auth_token');
        $this->verifyServiceSid = config('twilio-notification-channel.verify_service_sid');

        if ($accountSid && $authToken && $this->verifyServiceSid) {
            $this->client = new Client($accountSid, $authToken);
        }
    }

    /**
     * Check if Twilio Verify service is configured and available
     */
    public function isAvailable(): bool
    {
        return !empty($this->client) && !empty($this->verifyServiceSid);
    }

    /**
     * Send verification code via Twilio Verify API
     */
    public function sendVerificationCode(string $phoneNumber): array
    {
        if (!$this->isAvailable()) {
            return [
                'success' => false,
                'message' => 'Twilio Verify service not configured'
            ];
        }

        try {
            $verification = $this->client->verify
                ->v2
                ->services($this->verifyServiceSid)
                ->verifications
                ->create($phoneNumber, 'sms');

            return [
                'success' => true,
                'sid' => $verification->sid,
                'status' => $verification->status
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Verify code using Twilio Verify API
     */
    public function verifyCode(string $phoneNumber, string $code): array
    {
        if (!$this->isAvailable()) {
            return [
                'success' => false,
                'message' => 'Twilio Verify service not configured'
            ];
        }

        try {
            $verificationCheck = $this->client->verify
                ->v2
                ->services($this->verifyServiceSid)
                ->verificationChecks
                ->create([
                    'to' => $phoneNumber,
                    'code' => $code
                ]);

            return [
                'success' => $verificationCheck->status === 'approved',
                'status' => $verificationCheck->status,
                'sid' => $verificationCheck->sid
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}