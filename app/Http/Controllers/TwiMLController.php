<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Campaign;
use App\Models\Agent;
use App\Services\ElevenLabsService;

class TwiMLController extends Controller
{
    public function __construct(
        private ElevenLabsService $elevenLabsService
    ) {}

    /**
     * Handle incoming call and generate TwiML response
     */
    public function call(Request $request): Response
    {
        $campaignId = $request->get('campaign_id');
        $metadata = json_decode(base64_decode($request->get('metadata', '')), true) ?? [];
        
        $campaign = Campaign::find($campaignId);
        if (!$campaign || !$campaign->agent) {
            return $this->errorResponse('Campaign or agent not found');
        }

        $agent = $campaign->agent;
        
        // Generate initial greeting TwiML
        $twiml = $this->generateGreetingTwiML($agent, $campaign);
        
        return response($twiml, 200, ['Content-Type' => 'text/xml']);
    }

    /**
     * Handle call flow and conversation
     */
    public function flow(Request $request): Response
    {
        $campaignId = $request->get('campaign_id');
        $step = $request->get('step', 'greeting');
        $speechResult = $request->get('SpeechResult', '');
        
        $campaign = Campaign::find($campaignId);
        if (!$campaign || !$campaign->agent) {
            return $this->errorResponse('Campaign or agent not found');
        }

        $agent = $campaign->agent;
        
        switch ($step) {
            case 'greeting':
                $twiml = $this->generateGreetingTwiML($agent, $campaign);
                break;
            case 'conversation':
                $twiml = $this->generateConversationTwiML($agent, $speechResult, $campaign);
                break;
            case 'closing':
                $twiml = $this->generateClosingTwiML($agent, $campaign);
                break;
            default:
                $twiml = $this->generateGreetingTwiML($agent, $campaign);
        }
        
        return response($twiml, 200, ['Content-Type' => 'text/xml']);
    }

    /**
     * Handle call status updates
     */
    public function status(Request $request): Response
    {
        // This endpoint receives call status updates from Twilio
        // The actual webhook handling is done in CallController
        return response('OK', 200);
    }

    /**
     * Handle recording completion
     */
    public function recording(Request $request): Response
    {
        // Handle recording webhooks
        return response('OK', 200);
    }

    private function generateGreetingTwiML(Agent $agent, Campaign $campaign): string
    {
        $greeting = $agent->prompt ?: "Hello! This is an automated call regarding your inquiry.";
        
        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<Response>
    <Say voice=\"{$this->getVoiceType($agent->voice_id)}\">{$greeting}</Say>
    <Gather input=\"speech\" action=\"" . route('twiml.flow', ['campaign_id' => $campaign->id, 'step' => 'conversation']) . "\" 
            method=\"POST\" speechTimeout=\"3\" timeout=\"10\">
        <Say voice=\"{$this->getVoiceType($agent->voice_id)}\">Please respond when you're ready.</Say>
    </Gather>
    <Redirect>" . route('twiml.flow', ['campaign_id' => $campaign->id, 'step' => 'closing']) . "</Redirect>
</Response>";
    }

    private function generateConversationTwiML(Agent $agent, string $speechResult, Campaign $campaign): string
    {
        // In a real implementation, this would use AI to generate dynamic responses
        // For now, we'll use a simple response system
        
        $response = $this->generateAgentResponse($speechResult, $agent);
        
        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<Response>
    <Say voice=\"{$this->getVoiceType($agent->voice_id)}\">{$response}</Say>
    <Gather input=\"speech\" action=\"" . route('twiml.flow', ['campaign_id' => $campaign->id, 'step' => 'conversation']) . "\" 
            method=\"POST\" speechTimeout=\"3\" timeout=\"10\" numDigits=\"1\">
        <Say voice=\"{$this->getVoiceType($agent->voice_id)}\">Is there anything else I can help you with?</Say>
    </Gather>
    <Redirect>" . route('twiml.flow', ['campaign_id' => $campaign->id, 'step' => 'closing']) . "</Redirect>
</Response>";
    }

    private function generateClosingTwiML(Agent $agent, Campaign $campaign): string
    {
        $closing = "Thank you for your time. Have a great day!";
        
        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<Response>
    <Say voice=\"{$this->getVoiceType($agent->voice_id)}\">{$closing}</Say>
    <Hangup/>
</Response>";
    }

    private function generateAgentResponse(string $userInput, Agent $agent): string
    {
        // Simple keyword-based responses
        // In a real implementation, this would integrate with OpenAI or similar AI service
        
        $lowerInput = strtolower($userInput);
        
        if (str_contains($lowerInput, 'yes') || str_contains($lowerInput, 'interested')) {
            return "That's great! I'll make sure someone from our team follows up with you soon.";
        }
        
        if (str_contains($lowerInput, 'no') || str_contains($lowerInput, 'not interested')) {
            return "I understand. Thank you for your time today.";
        }
        
        if (str_contains($lowerInput, 'information') || str_contains($lowerInput, 'details')) {
            return "I'd be happy to provide more information. Let me connect you with a specialist.";
        }
        
        return "I understand. Let me make sure I have the right information for you.";
    }

    private function getVoiceType(string $voiceId): string
    {
        // Map ElevenLabs voice IDs to Twilio voice types
        // In a real implementation, you might use ElevenLabs for TTS generation
        // and serve the audio files, but for simplicity we're using Twilio's voices
        
        return 'alice'; // Default Twilio voice
    }

    private function errorResponse(string $message): Response
    {
        $twiml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<Response>
    <Say>Sorry, there was an error processing your call. Please try again later.</Say>
    <Hangup/>
</Response>";
        
        return response($twiml, 200, ['Content-Type' => 'text/xml']);
    }
}
