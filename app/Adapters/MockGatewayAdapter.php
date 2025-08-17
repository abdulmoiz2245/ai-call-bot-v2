<?php

namespace App\Adapters;

class MockGatewayAdapter implements GatewayAdapterInterface
{
    public function originateCall(string $to, string $from, int $campaignId, array $metadata = []): array
    {
        // Simulate a real call gateway response
        $callId = 'mock_call_' . uniqid();
        
        return [
            'success' => true,
            'call_id' => $callId,
            'status' => 'initiated',
            'to' => $to,
            'from' => $from,
            'started_at' => now()->toISOString(),
            'metadata' => $metadata,
        ];
    }
    
    public function getCallStatus(string $callId): array
    {
        // Simulate random call outcomes for testing
        $statuses = ['ringing', 'answered', 'busy', 'no-answer', 'failed'];
        $randomStatus = $statuses[array_rand($statuses)];
        
        return [
            'call_id' => $callId,
            'status' => $randomStatus,
            'duration' => $randomStatus === 'answered' ? rand(30, 300) : 0,
            'cost' => $randomStatus === 'answered' ? rand(5, 50) / 100 : 0,
        ];
    }
    
    public function getCallCost(string $callId): float
    {
        // Simulate cost calculation
        return rand(5, 50) / 100; // $0.05 to $0.50
    }
    
    public function terminateCall(string $callId): bool
    {
        // Simulate call termination
        return true;
    }
    
    public function handleWebhook(array $data): array
    {
        // Process webhook data and return normalized format
        return [
            'call_id' => $data['call_id'] ?? null,
            'status' => $data['status'] ?? 'unknown',
            'duration' => $data['duration'] ?? 0,
            'cost' => $data['cost'] ?? 0,
            'timestamp' => now()->toISOString(),
        ];
    }
}
