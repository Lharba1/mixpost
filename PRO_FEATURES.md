# Mixpost Pro Features - User Documentation

## Table of Contents
1. [Variables](#variables)
2. [Hashtag Groups](#hashtag-groups)
3. [Post Templates](#post-templates)
4. [Posting Schedule](#posting-schedule)
5. [AI Assistant](#ai-assistant)
6. [Analytics](#analytics)
7. [Approvals](#approvals)
8. [Languages](#languages)
9. [Workspaces](#workspaces)
10. [Webhooks](#webhooks)
11. [API Tokens](#api-tokens)
12. [White Label](#white-label)

---

## Variables

**Location:** Sidebar → Pro Features → Variables

Variables allow you to insert dynamic text into your posts that gets replaced at publish time.

### System Variables
- `{{date}}` - Current date
- `{{time}}` - Current time
- `{{day}}` - Day of week
- `{{month}}` - Month name
- `{{year}}` - Current year
- `{{day_of_month}}` - Numeric day
- `{{week_number}}` - Week of year

### Custom Variables
1. Click **Add Variable**
2. Enter a **Name** (display name)
3. Enter a **Key** (e.g., `company_name`)
4. Enter the **Value**
5. Click **Create Variable**

**Usage:** Type `{{your_key}}` in your post content.

---

## Hashtag Groups

**Location:** Sidebar → Pro Features → Hashtag Groups

Organize hashtags into reusable groups.

### Creating a Group
1. Click **New Group**
2. Enter a **Group Name**
3. Add hashtags (with or without #)
4. Click **Create Group**

### Using in Posts
In the post editor, click the hashtag icon to insert a group.

---

## Post Templates

**Location:** Sidebar → Pro Features → Post Templates

Save and reuse common post structures.

### Creating a Template
1. Click **New Template**
2. Enter a **Template Name**
3. Optionally add a **Description**
4. Choose **Category**
5. Write your **Post Content** (supports variables)
6. Click **Create Template**

### Using Templates
In the post editor, click the template icon to select and insert a template.

---

## Posting Schedule

**Location:** Sidebar → Pro Features → Posting Schedule

Define optimal posting times for each day.

### Setup
1. Toggle days on/off
2. Add time slots for each day
3. Posts added to queue will use these times

---

## AI Assistant

**Location:** In the post editor toolbar

AI-powered content generation.

### Features
- Generate content from prompts
- Improve existing text
- Change tone/style
- Create variations
- Shorten/extend content

---

## Analytics

**Location:** Sidebar → Pro Features → Analytics

View performance metrics across accounts.

### Available Metrics
- Followers growth
- Engagement rate
- Top performing posts
- Best posting times

---

## Approvals

**Location:** Sidebar → Pro Features → Approvals

Workflow for reviewing posts before publishing.

### Setting Up
1. Create an **Approval Workflow**
2. Add **Approvers**
3. Set as **Default** for new posts
4. Posts require approval before scheduling

---

## Languages

**Location:** Sidebar → Pro Features → Languages

Manage available languages for multilingual posts.

### Adding a Language
1. Click **Add Language**
2. Quick-select from common languages, or
3. Enter code, name, and native name
4. Toggle RTL if needed
5. Click **Add Language**

---

## Workspaces

**Location:** Sidebar → Enterprise → Workspaces

Organize accounts and team members.

### Creating a Workspace
1. Click **Create Workspace**
2. Enter name and description
3. Choose color
4. Add members with roles

---

## Webhooks

**Location:** Sidebar → Enterprise → Webhooks

Receive real-time notifications.

### Creating a Webhook
1. Click **Add Webhook**
2. Enter **URL** endpoint
3. Add optional **Secret** for verification
4. Select **Events** to trigger on
5. Toggle **Active**

---

## API Tokens

**Location:** Sidebar → Enterprise → API Tokens

Create tokens for external integrations.

### Creating a Token
1. Click **Create Token**
2. Enter a **Name**
3. Select **Scopes** (permissions)
4. Click **Create**
5. **Copy the token immediately** (shown only once)

---

## White Label

**Location:** Sidebar → Enterprise → White Label

Customize branding.

### Options
- **Application Name**
- **Logo** (light/dark modes)
- **Favicon**
- **Primary Color**
- **Secondary Color**
- **Custom CSS**

---

## Running the Queue

For scheduled posts and queue to work, add to your cron:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

This runs every minute and processes:
- Scheduled posts
- Queued posts
- Data imports
- Metrics processing
