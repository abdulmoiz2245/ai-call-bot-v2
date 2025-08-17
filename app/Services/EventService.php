<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class EventService
{
    public function emit(string $type, $subject = null, int $userId = null, array $data = []): Event
    {
        $event = Event::create([
            'company_id' => $this->getCompanyId(),
            'user_id' => $userId ?? Auth::id(),
            'type' => $type,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'data' => $data,
            'metadata' => [
                'timestamp' => now()->toISOString(),
                'user_agent' => Request::header('User-Agent'),
                'ip_address' => Request::ip(),
            ],
            'ip_address' => Request::ip(),
            'user_agent' => Request::header('User-Agent'),
        ]);

        return $event;
    }

    public function getEvents(int $companyId = null, array $filters = [])
    {
        $query = Event::query();

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['subject_type'])) {
            $query->where('subject_type', $filters['subject_type']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->with(['user', 'company'])
            ->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 25);
    }

    private function getCompanyId(): ?int
    {
        $user = Auth::user();
        return $user?->company_id;
    }
}
