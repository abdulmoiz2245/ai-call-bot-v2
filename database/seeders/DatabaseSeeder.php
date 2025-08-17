<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CompanyTypeSeeder::class,
        ]);

        // Get company types
        $ecommerceType = \App\Models\CompanyType::where('slug', 'ecommerce')->first();
        $healthcareType = \App\Models\CompanyType::where('slug', 'healthcare')->first();
        $realEstateType = \App\Models\CompanyType::where('slug', 'real-estate')->first();
        $genericType = \App\Models\CompanyType::where('slug', 'generic')->first();

        // Create Super Admin User (no company)
        $superAdmin = \App\Models\User::create([
            'name' => 'Super Admin',
            'email' => 'super@callbot.com',
            'password' => bcrypt('password'),
            'role' => 'PARENT_SUPER_ADMIN',
            'is_active' => true,
        ]);

        // 1. E-commerce Company
        $ecommerceCompany = \App\Models\Company::create([
            'company_type_id' => $ecommerceType->id,
            'name' => 'TechGear Store',
            'slug' => 'techgear-store',
            'email' => 'admin@techgear.com',
            'phone' => '+1234567890',
            'timezone' => 'America/New_York',
            'business_hours' => $ecommerceType->default_settings['business_hours'],
            'call_settings' => $ecommerceType->default_settings['call_settings'],
            'default_language' => 'en',
            'currency' => 'USD',
            'is_active' => true,
        ]);

        // E-commerce users
        $ecommerceAdmin = \App\Models\User::create([
            'name' => 'Tech Admin',
            'email' => 'admin@techgear.com',
            'password' => bcrypt('password'),
            'company_id' => $ecommerceCompany->id,
            'role' => 'COMPANY_ADMIN',
            'is_active' => true,
        ]);

        $ecommerceAgent = \App\Models\User::create([
            'name' => 'Sarah Johnson',
            'email' => 'sarah@techgear.com',
            'password' => bcrypt('password'),
            'company_id' => $ecommerceCompany->id,
            'role' => 'AGENT',
            'is_active' => true,
        ]);

        $ecommerceViewer = \App\Models\User::create([
            'name' => 'Mike Viewer',
            'email' => 'mike@techgear.com',
            'password' => bcrypt('password'),
            'company_id' => $ecommerceCompany->id,
            'role' => 'VIEWER',
            'is_active' => true,
        ]);

        // 2. Healthcare Company
        $healthcareCompany = \App\Models\Company::create([
            'company_type_id' => $healthcareType->id,
            'name' => 'HealthFirst Clinic',
            'slug' => 'healthfirst-clinic',
            'email' => 'admin@healthfirst.com',
            'phone' => '+1234567891',
            'timezone' => 'America/Chicago',
            'business_hours' => $healthcareType->default_settings['business_hours'],
            'call_settings' => $healthcareType->default_settings['call_settings'],
            'default_language' => 'en',
            'currency' => 'USD',
            'is_active' => true,
        ]);

        // Healthcare users
        $healthcareAdmin = \App\Models\User::create([
            'name' => 'Dr. Emily Carter',
            'email' => 'admin@healthfirst.com',
            'password' => bcrypt('password'),
            'company_id' => $healthcareCompany->id,
            'role' => 'COMPANY_ADMIN',
            'is_active' => true,
        ]);

        $healthcareAgent = \App\Models\User::create([
            'name' => 'Nurse Lisa',
            'email' => 'lisa@healthfirst.com',
            'password' => bcrypt('password'),
            'company_id' => $healthcareCompany->id,
            'role' => 'AGENT',
            'is_active' => true,
        ]);

        // 3. Real Estate Company
        $realEstateCompany = \App\Models\Company::create([
            'company_type_id' => $realEstateType->id,
            'name' => 'Elite Properties',
            'slug' => 'elite-properties',
            'email' => 'admin@eliteproperties.com',
            'phone' => '+1234567892',
            'timezone' => 'America/Los_Angeles',
            'business_hours' => $realEstateType->default_settings['business_hours'],
            'call_settings' => $realEstateType->default_settings['call_settings'],
            'default_language' => 'en',
            'currency' => 'USD',
            'is_active' => true,
        ]);

        // Real Estate users
        $realEstateAdmin = \App\Models\User::create([
            'name' => 'Robert Williams',
            'email' => 'admin@eliteproperties.com',
            'password' => bcrypt('password'),
            'company_id' => $realEstateCompany->id,
            'role' => 'COMPANY_ADMIN',
            'is_active' => true,
        ]);

        $realEstateAgent = \App\Models\User::create([
            'name' => 'Jessica Brown',
            'email' => 'jessica@eliteproperties.com',
            'password' => bcrypt('password'),
            'company_id' => $realEstateCompany->id,
            'role' => 'AGENT',
            'is_active' => true,
        ]);

        // 4. Generic Company
        $genericCompany = \App\Models\Company::create([
            'company_type_id' => $genericType->id,
            'name' => 'Marketing Solutions Inc',
            'slug' => 'marketing-solutions',
            'email' => 'admin@marketingsolutions.com',
            'phone' => '+1234567893',
            'timezone' => 'America/Denver',
            'business_hours' => $genericType->default_settings['business_hours'],
            'call_settings' => $genericType->default_settings['call_settings'],
            'default_language' => 'en',
            'currency' => 'USD',
            'is_active' => true,
        ]);

        // Generic company users
        $genericAdmin = \App\Models\User::create([
            'name' => 'Amanda Taylor',
            'email' => 'admin@marketingsolutions.com',
            'password' => bcrypt('password'),
            'company_id' => $genericCompany->id,
            'role' => 'COMPANY_ADMIN',
            'is_active' => true,
        ]);

        $genericAgent = \App\Models\User::create([
            'name' => 'David Martinez',
            'email' => 'david@marketingsolutions.com',
            'password' => bcrypt('password'),
            'company_id' => $genericCompany->id,
            'role' => 'AGENT',
            'is_active' => true,
        ]);

        // Create AI Agents for each company
        $this->createAgents($ecommerceCompany, $healthcareCompany, $realEstateCompany, $genericCompany);
        
        // Create sample contacts for each company
        $this->createContacts($ecommerceCompany, $healthcareCompany, $realEstateCompany, $genericCompany);
        
        // Create sample orders for e-commerce company
        $this->createOrders($ecommerceCompany);
        
        // Create sample campaigns
        $this->createCampaigns($ecommerceCompany, $healthcareCompany, $realEstateCompany, $genericCompany);
    }

    private function createAgents($ecommerceCompany, $healthcareCompany, $realEstateCompany, $genericCompany)
    {
        // E-commerce Agents
        \App\Models\Agent::create([
            'company_id' => $ecommerceCompany->id,
            'name' => 'Sales Agent Sarah',
            'description' => 'Friendly sales agent for e-commerce follow-ups and upselling',
            'role' => 'Sales Agent',
            'tone' => 'friendly',
            'persona' => 'Hi there! I\'m Sarah, your friendly sales assistant. I\'m here to help you with any questions about your recent order or explore new products that might interest you.',
            'voice_id' => 'elevenlabs_voice_sarah',
            'language' => 'en',
            'scripts' => [
                'greeting' => 'Hi {first_name}! This is Sarah from {company_name}. I\'m calling to follow up on your recent order #{order_number}.',
                'voicemail' => 'Hi {first_name}, this is Sarah from {company_name}. I wanted to follow up on your recent order. Please give us a call back at {phone} when you get a chance. Thanks!',
                'fallback' => 'I understand you might be busy right now. Would you prefer if I call back at a different time?'
            ],
            'is_active' => true,
        ]);

        \App\Models\Agent::create([
            'company_id' => $ecommerceCompany->id,
            'name' => 'Support Agent Alex',
            'description' => 'Customer support agent for order issues and inquiries',
            'role' => 'Support Agent',
            'tone' => 'helpful',
            'persona' => 'Hello! I\'m Alex from customer support. I\'m here to help resolve any issues with your order or answer any questions you might have.',
            'voice_id' => 'elevenlabs_voice_alex',
            'language' => 'en',
            'scripts' => [
                'greeting' => 'Hello {first_name}, this is Alex from {company_name} customer support. I\'m calling regarding your recent inquiry.',
                'voicemail' => 'Hi {first_name}, this is Alex from {company_name} support. Please call us back at {phone} so we can assist you with your inquiry.',
                'fallback' => 'I want to make sure we resolve your issue. Would it be better if I email you instead?'
            ],
            'is_active' => true,
        ]);

        // Healthcare Agent
        \App\Models\Agent::create([
            'company_id' => $healthcareCompany->id,
            'name' => 'Health Assistant Emma',
            'description' => 'Professional healthcare assistant for appointment reminders',
            'role' => 'Health Assistant',
            'tone' => 'professional',
            'persona' => 'Good day! I\'m Emma, a healthcare assistant from your medical clinic. I\'m calling to assist you with your healthcare needs.',
            'voice_id' => 'elevenlabs_voice_emma',
            'language' => 'en',
            'scripts' => [
                'greeting' => 'Good day {first_name}, this is Emma from {company_name}. I\'m calling to remind you about your upcoming appointment.',
                'voicemail' => 'Hello {first_name}, this is Emma from {company_name}. Please call us back at {phone} to confirm your appointment.',
                'fallback' => 'Your health is important to us. Would you prefer to reschedule for a more convenient time?'
            ],
            'is_active' => true,
        ]);

        // Real Estate Agent
        \App\Models\Agent::create([
            'company_id' => $realEstateCompany->id,
            'name' => 'Property Agent Marcus',
            'description' => 'Professional real estate agent for property inquiries',
            'role' => 'Property Agent',
            'tone' => 'confident',
            'persona' => 'Hello! I\'m Marcus, your dedicated property specialist. I\'m here to help you find the perfect home or investment property.',
            'voice_id' => 'elevenlabs_voice_marcus',
            'language' => 'en',
            'scripts' => [
                'greeting' => 'Hello {first_name}, this is Marcus from {company_name}. I\'m calling about the property inquiry you made.',
                'voicemail' => 'Hi {first_name}, this is Marcus from {company_name}. I have some exciting property options to share with you. Call me back at {phone}.',
                'fallback' => 'I have some great properties that match your criteria. Would you prefer to discuss them via email or schedule a viewing?'
            ],
            'is_active' => true,
        ]);

        // Generic Marketing Agent
        \App\Models\Agent::create([
            'company_id' => $genericCompany->id,
            'name' => 'Marketing Agent Kelly',
            'description' => 'Dynamic marketing agent for lead follow-ups',
            'role' => 'Marketing Agent',
            'tone' => 'enthusiastic',
            'persona' => 'Hi there! I\'m Kelly from the marketing team. I\'m excited to share some amazing opportunities with you!',
            'voice_id' => 'elevenlabs_voice_kelly',
            'language' => 'en',
            'scripts' => [
                'greeting' => 'Hi {first_name}! This is Kelly from {company_name}. I\'m calling about the marketing services you inquired about.',
                'voicemail' => 'Hi {first_name}, this is Kelly from {company_name}. I have some exciting marketing solutions for your business. Call me back at {phone}!',
                'fallback' => 'I understand you\'re busy running your business. Would you prefer if I send you information via email?'
            ],
            'is_active' => true,
        ]);
    }

    private function createContacts($ecommerceCompany, $healthcareCompany, $realEstateCompany, $genericCompany)
    {
        // E-commerce contacts
        $ecommerceContacts = [
            ['first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john.doe@email.com', 'phone' => '+1234567801', 'segment' => 'Premium Customer'],
            ['first_name' => 'Jane', 'last_name' => 'Smith', 'email' => 'jane.smith@email.com', 'phone' => '+1234567802', 'segment' => 'Regular Customer'],
            ['first_name' => 'Mike', 'last_name' => 'Johnson', 'email' => 'mike.johnson@email.com', 'phone' => '+1234567803', 'segment' => 'VIP Customer'],
            ['first_name' => 'Lisa', 'last_name' => 'Williams', 'email' => 'lisa.williams@email.com', 'phone' => '+1234567804', 'segment' => 'New Customer'],
            ['first_name' => 'Tom', 'last_name' => 'Brown', 'email' => 'tom.brown@email.com', 'phone' => '+1234567805', 'segment' => 'Premium Customer'],
        ];

        foreach ($ecommerceContacts as $contact) {
            \App\Models\Contact::create(array_merge($contact, [
                'company_id' => $ecommerceCompany->id,
                'status' => 'new',
                'tags' => ['customer', 'online-shopper'],
            ]));
        }

        // Healthcare contacts
        $healthcareContacts = [
            ['first_name' => 'Robert', 'last_name' => 'Davis', 'email' => 'robert.davis@email.com', 'phone' => '+1234567811', 'segment' => 'Regular Patient'],
            ['first_name' => 'Emily', 'last_name' => 'Wilson', 'email' => 'emily.wilson@email.com', 'phone' => '+1234567812', 'segment' => 'New Patient'],
            ['first_name' => 'David', 'last_name' => 'Miller', 'email' => 'david.miller@email.com', 'phone' => '+1234567813', 'segment' => 'Follow-up Patient'],
        ];

        foreach ($healthcareContacts as $contact) {
            \App\Models\Contact::create(array_merge($contact, [
                'company_id' => $healthcareCompany->id,
                'status' => 'new',
                'tags' => ['patient', 'healthcare'],
            ]));
        }

        // Real estate contacts
        $realEstateContacts = [
            ['first_name' => 'Sarah', 'last_name' => 'Anderson', 'email' => 'sarah.anderson@email.com', 'phone' => '+1234567821', 'segment' => 'Home Buyer'],
            ['first_name' => 'Chris', 'last_name' => 'Thompson', 'email' => 'chris.thompson@email.com', 'phone' => '+1234567822', 'segment' => 'Investor'],
            ['first_name' => 'Jennifer', 'last_name' => 'Garcia', 'email' => 'jennifer.garcia@email.com', 'phone' => '+1234567823', 'segment' => 'First-time Buyer'],
        ];

        foreach ($realEstateContacts as $contact) {
            \App\Models\Contact::create(array_merge($contact, [
                'company_id' => $realEstateCompany->id,
                'status' => 'new',
                'tags' => ['property-seeker', 'real-estate'],
            ]));
        }

        // Generic company contacts
        $genericContacts = [
            ['first_name' => 'Brian', 'last_name' => 'Martinez', 'email' => 'brian.martinez@email.com', 'phone' => '+1234567831', 'segment' => 'Lead'],
            ['first_name' => 'Amanda', 'last_name' => 'Rodriguez', 'email' => 'amanda.rodriguez@email.com', 'phone' => '+1234567832', 'segment' => 'Prospect'],
            ['first_name' => 'Kevin', 'last_name' => 'Lee', 'email' => 'kevin.lee@email.com', 'phone' => '+1234567833', 'segment' => 'Hot Lead'],
        ];

        foreach ($genericContacts as $contact) {
            \App\Models\Contact::create(array_merge($contact, [
                'company_id' => $genericCompany->id,
                'status' => 'new',
                'tags' => ['lead', 'marketing'],
            ]));
        }
    }

    private function createOrders($ecommerceCompany)
    {
        $contacts = \App\Models\Contact::where('company_id', $ecommerceCompany->id)->get();
        
        foreach ($contacts->take(3) as $index => $contact) {
            $order = \App\Models\Order::create([
                'company_id' => $ecommerceCompany->id,
                'order_number' => 'ORD-' . str_pad($index + 1001, 6, '0', STR_PAD_LEFT),
                'customer_name' => $contact->first_name . ' ' . $contact->last_name,
                'customer_email' => $contact->email,
                'customer_phone' => $contact->phone,
                'total_amount' => rand(50, 500),
                'status' => ['pending', 'shipped', 'delivered'][rand(0, 2)],
                'ordered_at' => now()->subDays(rand(1, 30)),
            ]);

            // Create order items
            $products = [
                ['name' => 'Wireless Headphones', 'sku' => 'WH-001', 'price' => 99.99],
                ['name' => 'Smartphone Case', 'sku' => 'SC-002', 'price' => 24.99],
                ['name' => 'USB Cable', 'sku' => 'UC-003', 'price' => 12.99],
                ['name' => 'Bluetooth Speaker', 'sku' => 'BS-004', 'price' => 149.99],
                ['name' => 'Power Bank', 'sku' => 'PB-005', 'price' => 39.99],
            ];

            $orderProducts = collect($products)->random(rand(1, 3));
            
            foreach ($orderProducts as $product) {
                \App\Models\OrderItem::create([
                    'order_id' => $order->id,
                    'product_name' => $product['name'],
                    'product_sku' => $product['sku'],
                    'quantity' => rand(1, 3),
                    'unit_price' => $product['price'],
                ]);
            }
        }
    }

    private function createCampaigns($ecommerceCompany, $healthcareCompany, $realEstateCompany, $genericCompany)
    {
        // E-commerce campaigns
        $ecommerceAgent = \App\Models\Agent::where('company_id', $ecommerceCompany->id)->first();
        \App\Models\Campaign::create([
            'company_id' => $ecommerceCompany->id,
            'agent_id' => $ecommerceAgent->id,
            'created_by' => \App\Models\User::where('company_id', $ecommerceCompany->id)->where('role', 'COMPANY_ADMIN')->first()->id,
            'name' => 'Order Follow-up Campaign',
            'description' => 'Follow up with customers who placed orders in the last 7 days',
            'status' => 'draft',
            'data_source_type' => 'orders',
            'schedule_settings' => [
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addDays(30)->format('Y-m-d'),
                'call_window_start' => '09:00',
                'call_window_end' => '17:00',
                'timezone' => 'America/New_York',
                'days_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            ],
            'max_retries' => 3,
            'max_concurrency' => 5,
            'call_order' => 'sequential',
            'record_calls' => true,
            'filter_criteria' => [
                'order_status' => ['shipped', 'delivered'],
                'order_date_from' => now()->subDays(7)->format('Y-m-d'),
            ],
        ]);

        // Healthcare campaign
        $healthcareAgent = \App\Models\Agent::where('company_id', $healthcareCompany->id)->first();
        \App\Models\Campaign::create([
            'company_id' => $healthcareCompany->id,
            'agent_id' => $healthcareAgent->id,
            'created_by' => \App\Models\User::where('company_id', $healthcareCompany->id)->where('role', 'COMPANY_ADMIN')->first()->id,
            'name' => 'Appointment Reminders',
            'description' => 'Remind patients about upcoming appointments',
            'status' => 'draft',
            'data_source_type' => 'contacts',
            'schedule_settings' => [
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addDays(60)->format('Y-m-d'),
                'call_window_start' => '08:00',
                'call_window_end' => '18:00',
                'timezone' => 'America/Chicago',
                'days_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            ],
            'max_retries' => 2,
            'max_concurrency' => 3,
            'call_order' => 'priority',
            'record_calls' => true,
            'filter_criteria' => [
                'segment' => ['Regular Patient', 'Follow-up Patient'],
            ],
        ]);

        // Real estate campaign
        $realEstateAgent = \App\Models\Agent::where('company_id', $realEstateCompany->id)->first();
        \App\Models\Campaign::create([
            'company_id' => $realEstateCompany->id,
            'agent_id' => $realEstateAgent->id,
            'created_by' => \App\Models\User::where('company_id', $realEstateCompany->id)->where('role', 'COMPANY_ADMIN')->first()->id,
            'name' => 'Property Interest Follow-up',
            'description' => 'Follow up with potential buyers who showed interest',
            'status' => 'draft',
            'data_source_type' => 'contacts',
            'schedule_settings' => [
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addDays(45)->format('Y-m-d'),
                'call_window_start' => '10:00',
                'call_window_end' => '19:00',
                'timezone' => 'America/Los_Angeles',
                'days_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
            ],
            'max_retries' => 4,
            'max_concurrency' => 8,
            'call_order' => 'priority',
            'record_calls' => true,
            'filter_criteria' => [
                'segment' => ['Home Buyer', 'Investor'],
            ],
        ]);

        // Generic campaign
        $genericAgent = \App\Models\Agent::where('company_id', $genericCompany->id)->first();
        \App\Models\Campaign::create([
            'company_id' => $genericCompany->id,
            'agent_id' => $genericAgent->id,
            'created_by' => \App\Models\User::where('company_id', $genericCompany->id)->where('role', 'COMPANY_ADMIN')->first()->id,
            'name' => 'Lead Nurturing Campaign',
            'description' => 'Nurture marketing leads and prospects',
            'status' => 'draft',
            'data_source_type' => 'contacts',
            'schedule_settings' => [
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addDays(90)->format('Y-m-d'),
                'call_window_start' => '09:00',
                'call_window_end' => '17:00',
                'timezone' => 'America/Denver',
                'days_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            ],
            'max_retries' => 3,
            'max_concurrency' => 6,
            'call_order' => 'random',
            'record_calls' => true,
            'filter_criteria' => [
                'segment' => ['Lead', 'Hot Lead'],
            ],
        ]);
    }
}
