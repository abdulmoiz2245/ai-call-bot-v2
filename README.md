# Call-Bot SaaS Platform

A comprehensive multi-tenant SaaS platform for AI-powered automated calling campaigns built with Laravel 12.0 and Vue.js 3.5.

## 🚀 Features

### Core Platform
- **Multi-tenant Architecture**: Complete company isolation with tenant-scoped data
- **Campaign Management**: Create, manage, and monitor calling campaigns
- **AI Agent Management**: Configure voice agents with ElevenLabs integration
- **Contact Management**: Import, manage, and segment contact lists
- **Call Management**: Real-time call tracking, recording, and analytics
- **Analytics Dashboard**: Comprehensive reporting and performance metrics

### Technical Features
- **Laravel 12.0** backend with modern PHP 8.3+ features
- **Vue.js 3.5** frontend with TypeScript and Composition API
- **Inertia.js** for seamless SPA experience
- **shadcn-vue** component library for beautiful UI
- **Multi-gateway calling** support (Twilio + Mock for development)
- **Real-time events** system for live updates
- **Job queue** system for background processing
- **Comprehensive API** with 64+ endpoints

## 🏗️ Architecture

### Backend (Laravel)
```
app/
├── Http/Controllers/     # API Controllers
├── Models/              # Eloquent Models (8 core models)
├── Services/            # Business Logic Services
├── Jobs/                # Background Jobs
├── Middleware/          # Custom Middleware
├── Adapters/            # Gateway Adapters
└── Providers/           # Service Providers
```

### Frontend (Vue.js)
```
resources/js/
├── components/          # Reusable Components
├── composables/         # Vue Composables
├── layouts/             # Page Layouts
├── pages/               # Page Components
├── lib/                 # Utilities
└── types/               # TypeScript Definitions
```

### Database Schema
- **Companies**: Multi-tenant isolation
- **Users**: User management with roles
- **Agents**: AI voice agents configuration
- **Campaigns**: Calling campaigns with settings
- **Contacts**: Contact management with DNC support
- **Calls**: Call records with full lifecycle tracking
- **Events**: Audit trail and real-time events
- **Settings**: Company-specific configuration

## 🛠️ Installation

### Prerequisites
- PHP 8.3+
- Node.js 18+
- Composer
- SQLite/MySQL/PostgreSQL

### Setup Steps

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd call-bot-saas
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Environment configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Database setup**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

6. **Build frontend assets**
   ```bash
   npm run build
   # or for development
   npm run dev
   ```

## 🧪 Test Accounts

The seeder creates comprehensive test data for development and testing:

### Quick Login Credentials:
- **Super Admin**: `super@callbot.com` / `password`
- **E-commerce Admin**: `admin@techgear.com` / `password`
- **Healthcare Admin**: `admin@healthfirst.com` / `password`
- **Real Estate Admin**: `admin@eliteproperties.com` / `password`
- **Generic Admin**: `admin@marketingsolutions.com` / `password`

### Test Data Includes:
- **4 Companies** with different business types
- **10 Users** across all roles (Admin, Agent, Viewer)
- **5 AI Agents** with industry-specific configurations
- **14 Contacts** segmented by company type
- **3 E-commerce Orders** with order items
- **4 Sample Campaigns** ready for testing

📋 **Complete test account details**: See [TEST_ACCOUNTS.md](./TEST_ACCOUNTS.md)

## ⚙️ Configuration

### Environment Variables

#### Core Application
```env
APP_NAME="Call-Bot SaaS"
APP_URL=http://localhost:8000
DB_CONNECTION=sqlite
```

#### Third-party Services
```env
# ElevenLabs (Voice Generation)
ELEVENLABS_API_KEY=your_api_key
ELEVENLABS_BASE_URL=https://api.elevenlabs.io/v1

# Twilio (Calling Gateway)
TWILIO_ACCOUNT_SID=your_account_sid
TWILIO_AUTH_TOKEN=your_auth_token
TWILIO_FROM_NUMBER=+1234567890
TWILIO_WEBHOOK_URL=https://yourdomain.com/webhooks/calls

# Call Gateway Configuration
CALL_GATEWAY_DEFAULT=mock  # Use 'twilio' for production
```

### Gateway Configuration

The platform supports multiple calling gateways:

- **Mock Gateway**: For development and testing
- **Twilio Gateway**: For production calling

## 🔌 API Documentation

### Authentication
All API endpoints require authentication except webhooks and TwiML endpoints.

### Key Endpoints

#### Campaigns
- `GET /campaigns` - List campaigns
- `POST /campaigns` - Create campaign
- `GET /campaigns/{id}` - Get campaign details
- `PUT /campaigns/{id}` - Update campaign
- `POST /campaigns/{id}/activate` - Start campaign

#### Contacts
- `GET /contacts` - List contacts
- `POST /contacts` - Create contact
- `POST /contacts/import` - Bulk import
- `GET /contacts/export` - Export contacts

#### Calls
- `GET /calls` - List calls with filtering
- `GET /calls/{id}` - Get call details
- `POST /calls/initiate` - Manual call initiation
- `POST /calls/{id}/retry` - Retry failed call

#### Analytics
- `GET /analytics` - Get analytics dashboard
- `GET /analytics/export` - Export analytics data

## 🎯 Usage Examples

### Creating a Campaign
```php
$campaign = Campaign::create([
    'name' => 'Q4 Sales Outreach',
    'agent_id' => $agent->id,
    'status' => 'draft',
    'max_retries' => 3,
    'retry_delay' => 300,
    'call_window_start' => '09:00',
    'call_window_end' => '17:00',
]);
```

### Initiating Calls
```php
// Through the calling service
$call = app(CallingService::class)->scheduleCall($campaign, $contact);

// Via API
POST /calls/initiate
{
  "campaign_id": 1,
  "contact_id": 123
}
```

### Handling Webhooks
```php
// Twilio webhook handling
POST /webhooks/calls
{
  "CallSid": "CAxxxx",
  "CallStatus": "completed",
  "CallDuration": "45",
  "RecordingUrl": "https://..."
}
```

## 🧪 Testing

### Running Tests
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run with coverage
php artisan test --coverage
```

### Test Structure
- **Unit Tests**: Model logic, services, and utilities
- **Feature Tests**: API endpoints and integration tests
- **Database Tests**: Migration and seeder tests

## 🚀 Deployment

### Production Checklist

1. **Environment Configuration**
   - Set `APP_ENV=production`
   - Configure production database
   - Set up Redis for caching/sessions
   - Configure mail settings

2. **Third-party Services**
   - Set up ElevenLabs API account
   - Configure Twilio account and phone numbers
   - Set up webhook URLs

3. **Performance Optimization**
   - Enable caching: `php artisan optimize`
   - Queue workers: Set up queue workers for background jobs
   - CDN: Configure asset delivery

4. **Security**
   - SSL/TLS certificates
   - Firewall configuration
   - Rate limiting
   - CSRF protection (enabled by default)

### Docker Deployment
```dockerfile
# Example Docker setup available in docker-compose.yml
docker-compose up -d
```

## 🔧 Development

### Code Style
- **PHP**: Laravel Pint for code formatting
- **Vue/TypeScript**: ESLint + Prettier
- **Database**: Laravel migrations and seeders

### Development Commands
```bash
# Start development server
php artisan serve

# Watch frontend assets
npm run dev

# Run code formatting
./vendor/bin/pint
npm run lint:fix

# Queue worker (for background jobs)
php artisan queue:work
```

## 📊 Monitoring & Analytics

### Built-in Analytics
- Call volume and success rates
- Agent performance metrics
- Campaign effectiveness
- Contact engagement tracking
- Cost analysis and ROI

### Real-time Monitoring
- Live call status updates
- Campaign progress tracking
- System health monitoring
- Error tracking and alerting

## 🤝 Integration

### ElevenLabs Integration
- Voice synthesis for AI agents
- Voice cloning capabilities
- Custom voice settings per agent

### Twilio Integration
- Outbound calling
- Call recording
- Real-time call status
- Cost tracking

### Webhook System
- Real-time call status updates
- Recording completion notifications
- Custom event handling

## 📝 License

This Call-Bot SaaS platform is proprietary software. All rights reserved.

## 🆘 Support

For support and questions:
- Check the documentation
- Review the codebase comments
- Test with the mock gateway first
- Ensure all environment variables are set

---

**Built with ❤️ using Laravel 12.0 and Vue.js 3.5**
