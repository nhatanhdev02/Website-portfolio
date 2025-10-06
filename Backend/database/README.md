# Database Factories and Seeders Documentation

This document provides comprehensive information about the model factories and database seeders implemented for the Laravel Admin Backend project.

## Overview

The database layer includes:
- **Model Factories**: Generate realistic fake data for all models with various states
- **Database Seeders**: Populate the database with initial and test data
- **Factory States**: Specialized variations of models for different scenarios
- **Relationships**: Complex data structures with proper relationships

## Model Factories

### AdminFactory

Creates admin users with authentication capabilities.

**Basic Usage:**
```php
Admin::factory()->create();
Admin::factory(5)->create(); // Create 5 admins
```

**Available States:**
- `neverLoggedIn()` - Admin who has never logged in
- `recentLogin()` - Admin with recent login activity
- `withCredentials($username, $email, $password)` - Admin with specific credentials

**Examples:**
```php
// Create admin with specific credentials
Admin::factory()->withCredentials('admin', 'admin@example.com', 'password123')->create();

// Create admin who never logged in
Admin::factory()->neverLoggedIn()->create();
```

### HeroFactory

Creates hero section content with bilingual support.

**Available States:**
- `nhatAnhDev()` - Specific content for Nhật Anh Dev portfolio

**Examples:**
```php
// Create random hero content
Hero::factory()->create();

// Create specific Nhật Anh Dev hero content
Hero::factory()->nhatAnhDev()->create();
```

### ServiceFactory

Creates service offerings with proper ordering and styling.

**Available States:**
- `webDevelopment()` - Web development service
- `mobileDevelopment()` - Mobile development service
- `apiDevelopment()` - API development service
- `withOrder($order)` - Service with specific order

**Examples:**
```php
// Create web development service with order 1
Service::factory()->webDevelopment()->withOrder(1)->create();

// Create random services
Service::factory(5)->create();
```

### ProjectFactory

Creates portfolio projects with technologies and categorization.

**Available States:**
- `featured()` - Featured project (higher priority)
- `notFeatured()` - Regular project
- `webProject()` - Web development project
- `mobileProject()` - Mobile development project
- `apiProject()` - API development project
- `withCategory($category)` - Project with specific category
- `withOrder($order)` - Project with specific order

**Examples:**
```php
// Create featured web projects
Project::factory(3)->featured()->webProject()->create();

// Create regular mobile projects
Project::factory(5)->notFeatured()->mobileProject()->create();
```

### BlogPostFactory

Creates blog posts with markdown content and publishing workflow.

**Available States:**
- `published()` - Published blog post
- `draft()` - Draft blog post
- `recentPublished()` - Recently published post
- `scheduled()` - Scheduled for future publication
- `laravelTutorial()` - Laravel-specific tutorial
- `withTags($tags)` - Post with specific tags

**Examples:**
```php
// Create published Laravel tutorials
BlogPost::factory(5)->published()->laravelTutorial()->create();

// Create draft posts with specific tags
BlogPost::factory(3)->draft()->withTags(['PHP', 'Backend'])->create();
```

### ContactMessageFactory

Creates contact messages with realistic inquiry scenarios.

**Available States:**
- `unread()` - Unread message
- `read()` - Read message
- `recentUnread()` - Recent unread message
- `urgent()` - Urgent message
- `businessInquiry()` - Business inquiry message
- `fromDomain($domain)` - Message from specific email domain

**Examples:**
```php
// Create unread business inquiries
ContactMessage::factory(5)->unread()->businessInquiry()->create();

// Create messages from specific domain
ContactMessage::factory(3)->fromDomain('company.com')->create();
```

### AboutFactory

Creates about section content with skills and experience.

**Available States:**
- `nhatAnhDev()` - Specific content for Nhật Anh Dev
- `backendFocused()` - Backend developer profile
- `frontendFocused()` - Frontend developer profile
- `withSkills($skills)` - Profile with specific skills

**Examples:**
```php
// Create Nhật Anh Dev about content
About::factory()->nhatAnhDev()->create();

// Create backend-focused developer profile
About::factory()->backendFocused()->create();
```

### ContactInfoFactory

Creates contact information with social media links.

**Available States:**
- `nhatAnhDev()` - Specific contact info for Nhật Anh Dev
- `minimalSocial()` - Minimal social media presence
- `allSocialPlatforms()` - All social media platforms

**Examples:**
```php
// Create Nhật Anh Dev contact info
ContactInfo::factory()->nhatAnhDev()->create();

// Create contact info with all social platforms
ContactInfo::factory()->allSocialPlatforms()->create();
```

### SystemSettingsFactory

Creates system configuration settings with different data types.

**Available States:**
- `stringType()` - String type setting
- `booleanType()` - Boolean type setting
- `integerType()` - Integer type setting
- `jsonType()` - JSON type setting
- `siteConfig()` - Site configuration setting
- `themeSettings()` - Theme configuration setting

**Examples:**
```php
// Create theme settings
SystemSettings::factory()->themeSettings()->create();

// Create various setting types
SystemSettings::factory(3)->stringType()->create();
SystemSettings::factory(2)->booleanType()->create();
```

## Database Seeders

### DatabaseSeeder (Main Seeder)

The main seeder that orchestrates all other seeders.

**Usage:**
```bash
php artisan db:seed
```

**What it does:**
- Runs AdminSeeder for admin users
- Runs ContentSeeder for main content
- Runs TestDataSeeder (only in local/testing environments)

### AdminSeeder

Creates admin users for the system.

**Production Data:**
- Main admin user (admin@nhatanhdev.com)
- Nhật Anh's personal admin account

**Test Data (local/testing only):**
- Various admin users with different login states
- Test credentials for development

### ContentSeeder

Creates the main content for the portfolio.

**Production Data:**
- Hero section content
- About section content
- Core services (Web, Mobile, API development)
- Sample projects and blog posts
- Contact information
- System settings

**Features:**
- Uses specific factory states for production-ready content
- Creates realistic sample data
- Proper ordering and categorization

### TestDataSeeder

Creates comprehensive test data (only runs in local/testing environments).

**What it creates:**
- Multiple admin variations
- Various content scenarios
- Different project categories
- Blog posts with different states
- Contact messages from various scenarios
- System settings variations

### ProductionSeeder

Minimal seeder for production environments.

**Usage:**
```bash
php artisan db:seed --class=ProductionSeeder
```

**What it creates:**
- Essential admin user
- Core content only
- Minimal system settings

## Usage Examples

### Development Setup

```bash
# Run all seeders (includes test data in local environment)
php artisan db:seed

# Run specific seeder
php artisan db:seed --class=AdminSeeder
php artisan db:seed --class=ContentSeeder
```

### Production Setup

```bash
# Run production seeder only
php artisan db:seed --class=ProductionSeeder

# Or run main seeder (TestDataSeeder won't run in production)
php artisan db:seed
```

### Testing

```bash
# Create test data in tests
Admin::factory(5)->create();
Project::factory(10)->featured()->create();
BlogPost::factory(5)->published()->laravelTutorial()->create();
```

### Custom Data Creation

```php
// Create a complete project portfolio
$webProjects = Project::factory(5)->featured()->webProject()->create();
$mobileProjects = Project::factory(3)->featured()->mobileProject()->create();
$regularProjects = Project::factory(10)->notFeatured()->create();

// Create blog content
$tutorials = BlogPost::factory(8)->published()->laravelTutorial()->create();
$drafts = BlogPost::factory(4)->draft()->create();

// Create contact scenarios
$businessInquiries = ContactMessage::factory(10)->businessInquiry()->unread()->create();
$urgentMessages = ContactMessage::factory(3)->urgent()->unread()->create();
```

## Factory Relationships

The factories are designed to work together and create realistic relationships:

1. **Admin → Content**: Admins can be associated with content creation
2. **Projects → Categories**: Projects are properly categorized
3. **Blog Posts → Tags**: Posts have relevant tag combinations
4. **Services → Ordering**: Services maintain proper display order
5. **Settings → Types**: Settings have appropriate data types

## Best Practices

1. **Use States**: Always use appropriate factory states for specific scenarios
2. **Realistic Data**: Factories generate realistic, varied data for better testing
3. **Environment Awareness**: Test data only creates in appropriate environments
4. **Proper Relationships**: Use factories that create proper model relationships
5. **Consistent Ordering**: Use ordering states for models that require sequence

## Testing the Factories

Run the factory validation tests:

```bash
php artisan test tests/Unit/FactoryValidationTest.php
```

This test suite validates:
- Factory attribute generation
- Factory state functionality
- Data type correctness
- Realistic data variation
- Relationship integrity

## Customization

To customize factories for your specific needs:

1. **Add New States**: Create new factory states for specific scenarios
2. **Modify Data**: Update factory definitions for different data patterns
3. **Create Relationships**: Add factory relationships for complex data structures
4. **Environment-Specific**: Use environment checks for different data sets

## Troubleshooting

**Common Issues:**

1. **Database Connection**: Ensure database is properly configured
2. **Missing Dependencies**: Run `composer install` for required packages
3. **Migration Issues**: Run migrations before seeding
4. **Permission Issues**: Check database permissions

**Debug Commands:**
```bash
# Check factory definitions
php artisan tinker
>>> Admin::factory()->definition()

# Test specific factory states
>>> Admin::factory()->neverLoggedIn()->make()
```
