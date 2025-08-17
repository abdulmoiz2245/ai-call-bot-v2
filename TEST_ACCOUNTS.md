# Test Accounts & Data

This document contains all the test accounts and sample data created for testing the Call-Bot SaaS platform.

## 🔐 Test User Accounts

### Super Admin Account
- **Email**: `super@callbot.com`
- **Password**: `password`
- **Role**: PARENT_SUPER_ADMIN
- **Access**: Full access to all companies and system management

---

## 🏢 Test Companies & Users

### 1. TechGear Store (E-commerce)
**Company Type**: E-commerce  
**Timezone**: America/New_York

#### Users:
- **Admin**: `admin@techgear.com` / `password` (COMPANY_ADMIN)
- **Agent**: `sarah@techgear.com` / `password` (AGENT)
- **Viewer**: `mike@techgear.com` / `password` (VIEWER)

#### AI Agents:
- **Sales Agent Sarah**: Friendly sales agent for e-commerce follow-ups
- **Support Agent Alex**: Customer support for order issues

#### Sample Data:
- **5 Contacts**: Premium customers, regular customers, VIP customers
- **3 Orders**: With order items (headphones, cases, cables, etc.)
- **1 Campaign**: "Order Follow-up Campaign" (Draft status)

---

### 2. HealthFirst Clinic (Healthcare)
**Company Type**: Healthcare  
**Timezone**: America/Chicago

#### Users:
- **Admin**: `admin@healthfirst.com` / `password` (COMPANY_ADMIN)
- **Agent**: `lisa@healthfirst.com` / `password` (AGENT)

#### AI Agents:
- **Health Assistant Emma**: Professional healthcare assistant for appointment reminders

#### Sample Data:
- **3 Contacts**: Regular patients, new patients, follow-up patients
- **1 Campaign**: "Appointment Reminders" (Draft status)

---

### 3. Elite Properties (Real Estate)
**Company Type**: Real Estate  
**Timezone**: America/Los_Angeles

#### Users:
- **Admin**: `admin@eliteproperties.com` / `password` (COMPANY_ADMIN)
- **Agent**: `jessica@eliteproperties.com` / `password` (AGENT)

#### AI Agents:
- **Property Agent Marcus**: Professional real estate agent for property inquiries

#### Sample Data:
- **3 Contacts**: Home buyers, investors, first-time buyers
- **1 Campaign**: "Property Interest Follow-up" (Draft status)

---

### 4. Marketing Solutions Inc (Generic)
**Company Type**: Generic  
**Timezone**: America/Denver

#### Users:
- **Admin**: `admin@marketingsolutions.com` / `password` (COMPANY_ADMIN)
- **Agent**: `david@marketingsolutions.com` / `password` (AGENT)

#### AI Agents:
- **Marketing Agent Kelly**: Dynamic marketing agent for lead follow-ups

#### Sample Data:
- **3 Contacts**: Leads, prospects, hot leads
- **1 Campaign**: "Lead Nurturing Campaign" (Draft status)

---

## 📊 Sample Data Summary

### Contacts by Company:
- **TechGear Store**: 5 contacts (e-commerce customers)
- **HealthFirst Clinic**: 3 contacts (patients)
- **Elite Properties**: 3 contacts (property seekers)
- **Marketing Solutions**: 3 contacts (marketing leads)

### Orders (E-commerce Only):
- **TechGear Store**: 3 sample orders with multiple items each
  - Order numbers: ORD-001001, ORD-001002, ORD-001003
  - Various statuses: pending, shipped, delivered
  - Products: Wireless headphones, smartphone cases, USB cables, etc.

### AI Agents:
- **Total**: 5 AI agents across all companies
- **Roles**: Sales, Support, Healthcare, Real Estate, Marketing
- **Features**: Custom personas, scripts, voice settings

### Campaigns:
- **Total**: 4 campaigns (one per company)
- **Types**: Order follow-up, appointment reminders, property follow-up, lead nurturing
- **Status**: All in draft status for testing
- **Scheduling**: Different time windows and days for each company type

---

## 🧪 Testing Scenarios

### Multi-Tenancy Testing:
1. Log in with different company admin accounts
2. Verify data isolation between companies
3. Test user role permissions (Admin vs Agent vs Viewer)

### E-commerce Workflow:
1. Login as `admin@techgear.com`
2. View orders and order items
3. Create campaigns targeting orders
4. Test order import functionality

### Campaign Management:
1. Activate draft campaigns
2. Test scheduling settings
3. Monitor campaign progress

### Contact Management:
1. Import new contacts via CSV
2. Segment contacts by type
3. Manage DNC (Do Not Call) lists

### Analytics & Reporting:
1. View campaign performance metrics
2. Test agent performance tracking
3. Export data in various formats

---

## 🚀 Quick Start Testing

1. **Start the development server**:
   ```bash
   php artisan serve
   npm run dev
   ```

2. **Login as Super Admin**:
   - Email: `super@callbot.com`
   - Password: `password`
   - Access all companies and manage system settings

3. **Test Company-specific Features**:
   - Login as any company admin (see accounts above)
   - Explore campaigns, contacts, agents, and analytics
   - Test the order management for TechGear Store

4. **Test Different User Roles**:
   - Login as different role types to verify permissions
   - Agents should only see assigned campaigns
   - Viewers should have read-only access

---

## 📝 Notes

- All passwords are set to `password` for testing convenience
- Sample data includes realistic business scenarios for each company type
- Phone numbers use the +1234567xxx format for testing
- All campaigns are in draft status and ready for activation
- Orders include proper customer information and product details
- AI agents have pre-configured scripts and personas for each industry

**Remember**: This is test data for development purposes only. In production, use secure passwords and real business data.
