<?php

namespace App\Adapters;

use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;

class TwilioGatewayAdapter implements GatewayAdapterInterface
{
    private Client $client;
    private string $fromNumber;
    private string $webhookUrl;

    public function __construct()
    {
        $config = config('services.twilio');
        
        $this->client = new Client(
            $config['account_sid'],
            $config['auth_token']
        );
        
        $this->fromNumber = $config['from_number'];
        $this->webhookUrl = $config['webhook_url'];
    }

    public function originateCall(string $to, string $from, int $campaignId, array $metadata = []): array
    {
        try {
            $call = $this->client->calls->create(
                $to, // To number
                $from ?: $this->fromNumber, // From number
                [
                    'url' => $this->buildTwiMLUrl($campaignId, $metadata),
                    'statusCallback' => $this->webhookUrl,
                    'statusCallbackEvent' => ['initiated', 'ringing', 'answered', 'completed'],
                    'statusCallbackMethod' => 'POST',
                    'record' => true,
                    'recordingStatusCallback' => $this->webhookUrl . '/recording',
                ]
            );

            return [
                'success' => true,
                'call_id' => $call->sid,
                'status' => $this->mapTwilioStatus($call->status),
                'to' => $call->to,
                'from' => $call->from,
                'started_at' => $call->dateCreated ? $call->dateCreated->format('c') : now()->toISOString(),
                'metadata' => $metadata,
                'provider_data' => [
                    'sid' => $call->sid,
                    'account_sid' => $call->accountSid,
                    'direction' => $call->direction,
                ],
            ];
        } catch (TwilioException $e) {
            throw new \Exception("Twilio API Error: " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getCallStatus(string $callId): array
    {
        try {
            $call = $this->client->calls($callId)->fetch();

            return [
                'call_id' => $call->sid,
                'status' => $this->mapTwilioStatus($call->status),
                'duration' => $call->duration ?: 0,
                'cost' => $this->getCallCost($callId),
                'started_at' => $call->startTime ? $call->startTime->format('c') : null,
                'answered_at' => $call->status === 'completed' && $call->startTime ? $call->startTime->format('c') : null,
                'ended_at' => $call->endTime ? $call->endTime->format('c') : null,
            ];
        } catch (TwilioException $e) {
            throw new \Exception("Failed to get call status: " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getCallCost(string $callId): float
    {
        try {
            $call = $this->client->calls($callId)->fetch();
            return (float) $call->price ?: 0.0;
        } catch (TwilioException $e) {
            // Return 0 if we can't fetch the cost
            return 0.0;
        }
    }

    public function terminateCall(string $callId): bool
    {
        try {
            $this->client->calls($callId)->update(['status' => 'completed']);
            return true;
        } catch (TwilioException $e) {
            return false;
        }
    }

    public function handleWebhook(array $data): array
    {
        // Map Twilio webhook data to our standard format
        return [
            'call_id' => $data['CallSid'] ?? '',
            'status' => $this->mapTwilioStatus($data['CallStatus'] ?? ''),
            'duration' => isset($data['CallDuration']) ? (int) $data['CallDuration'] : 0,
            'cost' => isset($data['CallPrice']) ? (float) $data['CallPrice'] : 0,
            'recording_url' => $data['RecordingUrl'] ?? null,
            'started_at' => $data['Timestamp'] ?? null,
            'from' => $data['From'] ?? '',
            'to' => $data['To'] ?? '',
            'provider_data' => $data,
        ];
    }

    private function mapTwilioStatus(string $twilioStatus): string
    {
        return match ($twilioStatus) {
            'queued', 'initiated' => 'queued',
            'ringing' => 'ringing',
            'in-progress' => 'answered',
            'completed' => 'completed',
            'busy' => 'busy',
            'no-answer' => 'no_answer',
            'failed', 'canceled' => 'failed',
            default => 'unknown',
        };
    }

    private function buildTwiMLUrl(int $campaignId, array $metadata): string
    {
        // Build URL to your TwiML endpoint that will handle the call flow
        $baseUrl = config('app.url');
        $params = http_build_query([
            'campaign_id' => $campaignId,
            'metadata' => base64_encode(json_encode($metadata)),
        ]);
        
        return "{$baseUrl}/api/twiml/call?{$params}";
    }
}
