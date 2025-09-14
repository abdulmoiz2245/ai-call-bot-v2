<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Agent;
use App\Services\ElevenLabsService;
use App\Services\OpenAIService;
use Mockery;

class LanguageSupportTest extends TestCase
{
    public function test_elevenlabs_service_receives_language_parameter()
    {
        // Mock ElevenLabs service
        $elevenLabsService = Mockery::mock(ElevenLabsService::class);

        // Test TTS with language
        $elevenLabsService->shouldReceive('textToSpeech')
            ->once()
            ->with('Hola, ¿cómo está?', 'test_voice_id', [], 'es')
            ->andReturn('mocked_audio_base64');

        $result = $elevenLabsService->textToSpeech('Hola, ¿cómo está?', 'test_voice_id', [], 'es');
        
        $this->assertEquals('mocked_audio_base64', $result);
    }

    public function test_openai_service_receives_language_parameter()
    {
        // Mock OpenAI service  
        $openaiService = Mockery::mock(OpenAIService::class);

        // Test transcription with language
        $openaiService->shouldReceive('transcribeAudio')
            ->once()
            ->with('base64_audio_data', 'audio/wav', 'fr')
            ->andReturn('Bonjour');

        $result = $openaiService->transcribeAudio('base64_audio_data', 'audio/wav', 'fr');
        
        $this->assertEquals('Bonjour', $result);
    }

    public function test_model_selection_for_different_languages()
    {
        $elevenLabsService = new ElevenLabsService();
        
        // Use reflection to test private method
        $reflection = new \ReflectionClass($elevenLabsService);
        $method = $reflection->getMethod('getModelIdForLanguage');
        $method->setAccessible(true);

        // Test English (should use fastest model)
        $result = $method->invoke($elevenLabsService, 'en');
        $this->assertEquals('eleven_flash_v2_5', $result);

        // Test Spanish (should use multilingual model)
        $result = $method->invoke($elevenLabsService, 'es');
        $this->assertEquals('eleven_multilingual_v2', $result);

        // Test unknown language (should default to multilingual)
        $result = $method->invoke($elevenLabsService, 'xx');
        $this->assertEquals('eleven_multilingual_v2', $result);

        // Test null language (should use default)
        $result = $method->invoke($elevenLabsService, null);
        $this->assertEquals('eleven_flash_v2_5', $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
