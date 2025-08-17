<?php

namespace App\Adapters;

interface GatewayAdapterInterface
{
    public function originateCall(string $to, string $from, int $campaignId, array $metadata = []): array;
    
    public function getCallStatus(string $callId): array;
    
    public function getCallCost(string $callId): float;
    
    public function terminateCall(string $callId): bool;
    
    public function handleWebhook(array $data): array;
}
