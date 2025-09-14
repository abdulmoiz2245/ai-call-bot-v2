# LiveKit Integration - Dynamic Agent with Variables

## Overview

This implementation provides a simplified LiveKit integration where a single Python agent dynamically adapts its behavior based on room metadata. The agent supports:

- **Dynamic Prompts** - Agent instructions change per call based on Laravel agent configuration
- **Variable Substitution** - Support for variables like `{caller_name}`, `{phone_number}`, etc.
- **Greeting Messages** - Automatic greeting when calls start
- **Multi-language Support** - Language-aware processing

## Key Features

### 1. Dynamic Prompt System
The agent reads its instructions from room metadata, allowing each call to have different behavior:

```json
{
  "prompt": "You are a sales assistant for {company_name}. Help {caller_name} with their inquiry.",
  "greeting_message": "Hello {caller_name}, thank you for calling {company_name}. How can I help you today?",
  "language": "en",
  "agent_name": "Sarah",
  "company_name": "TechCorp"
}
```

### 2. Variable Substitution
Supported variables that get automatically replaced:
- `{caller_name}` - Name of the caller
- `{phone_number}` - Caller's phone number  
- `{agent_name}` - Name of the AI agent
- `{company_name}` - Company name

### 3. Greeting Messages
Agents can send automatic greeting messages when calls start, with variable substitution support.

## Implementation Details

### Python Agent (`liveagent/src/agent.py`)

The existing LiveKit agent code is enhanced with dynamic features:

```python
async def get_room_config(room):
    """Extract agent configuration from room metadata"""
    try:
        metadata = room.metadata
        if metadata:
            config = json.loads(metadata)
            logger.info(f"Received room config: {config}")
            return config
        return None
    except Exception as e:
        logger.error(f"Failed to parse room metadata: {e}")
        return None

def replace_variables(text, config):
    """Replace variables in text with values from config"""
    if not text or not config:
        return text
    
    replacements = {
        '{caller_name}': config.get('caller_name', 'Unknown'),
        '{phone_number}': config.get('phone_number', 'Unknown'),
        '{agent_name}': config.get('agent_name', 'AI Assistant'),
        '{company_name}': config.get('company_name', 'Our Company'),
    }
    
    for variable, value in replacements.items():
        text = text.replace(variable, value)
    
    return text

class Assistant(Agent):
    def __init__(self, room_config=None) -> None:
        # Get dynamic instructions from room config
        if room_config and room_config.get('prompt'):
            dynamic_instructions = room_config.get('prompt')
            # Replace variables in the prompt
            dynamic_instructions = replace_variables(dynamic_instructions, room_config)
        else:
            dynamic_instructions = """Default agent instructions..."""
        
        super().__init__(instructions=dynamic_instructions)
        self.room_config = room_config or {}
        self.greeting_sent = False

    async def send_greeting(self, ctx: RunContext):
        """Send greeting message if configured"""
        greeting_message = self.room_config.get('greeting_message')
        if greeting_message and not self.greeting_sent:
            greeting_message = replace_variables(greeting_message, self.room_config)
            await ctx.session.generate_reply(
                instructions=f"Start the conversation with this greeting: {greeting_message}",
                user_msg=""
            )
            self.greeting_sent = True
```

### Enhanced Laravel LiveKit Service

```php
// Prepare agent configuration with variables
$agentConfig = [
    'call_id' => $voiceCall->id,
    'agent_id' => $agent->id,
    'prompt' => $agent->prompt ?? $agent->system_prompt,
    'greeting_message' => $agent->greeting_message,
    'language' => $agent->language,
    'agent_name' => $agent->name,
    'company_name' => $agent->company->name ?? 'Our Company',
    'phone_number' => $voiceCall->phone_number,
    'caller_name' => $voiceCall->caller_name,
    // ... other settings
];

return [
    'room_name' => $roomName,
    'url' => $this->livekitUrl,
    'tokens' => ['agent' => $agentToken, 'caller' => $callerToken],
    'agent_config' => $agentConfig,
    'room_metadata' => json_encode($agentConfig) // Sent to Python agent
];
```

## Usage Examples

### 1. Basic Agent with Greeting
```php
// Agent model configuration
$agent->prompt = "You are a helpful customer service agent for {company_name}.";
$agent->greeting_message = "Hello {caller_name}, welcome to {company_name}! How can I assist you today?";
```

### 2. Sales Agent with Variables
```php
$agent->prompt = "You are {agent_name}, a sales representative for {company_name}. Your goal is to help {caller_name} find the perfect product.";
$agent->greeting_message = "Hi {caller_name}! This is {agent_name} from {company_name}. I understand you're interested in our products. Let me help you find exactly what you need!";
```

### 3. Support Agent
```php
$agent->prompt = "You are a technical support specialist for {company_name}. Help {caller_name} resolve their technical issues with patience and expertise.";
$agent->greeting_message = "Hello {caller_name}, thank you for contacting {company_name} support. I'm here to help you resolve any technical issues. What can I assist you with today?";
```

## Configuration Flow

1. **Call Initiation** - Frontend starts call with phone number and caller name
2. **Agent Loading** - Laravel loads agent configuration with prompt and greeting
3. **Room Creation** - LiveKit room created with agent config as metadata
4. **Dynamic Setup** - Python agent reads metadata and configures itself
5. **Variable Replacement** - All variables in prompts/greetings are replaced
6. **Greeting Sent** - Automatic greeting sent after connection (if configured)
7. **Dynamic Conversation** - Agent behaves according to dynamic instructions

## Benefits

1. **Personalization** - Each call can be personalized with caller information
2. **Flexibility** - Different agents can have completely different behaviors
3. **Scalability** - Single Python agent handles all configurations
4. **Maintainability** - Easy to update agent behavior through Laravel admin
5. **Multi-tenant** - Each company can have unique agent personalities

This architecture provides a powerful, flexible system for creating personalized voice agents that adapt to each call context.
