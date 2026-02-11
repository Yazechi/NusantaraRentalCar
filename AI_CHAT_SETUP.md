# AI Chat Feature Setup Guide

## Overview

The AI chat feature now supports **Google Gemini AI** with automatic fallback to enhanced keyword matching. This provides intelligent, context-aware responses about cars in your database.

---

## Features

### With Gemini AI (Recommended)
- Natural language understanding
- Intelligent responses about any car in database
- Context-aware recommendations
- Answers complex questions like:
  - "What's the best car for a family of 6?"
  - "Show me automatic cars under 500k per day"
  - "Which cars have good fuel efficiency?"
  - "Compare Toyota and Honda options"

### Keyword Fallback (No API key needed)
- Works without API configuration
- Basic keyword matching
- Responds to simple queries:
  - Family cars, cheap cars, luxury cars
  - Automatic/manual transmission
  - Price inquiries
  - Available cars

---

## Setup Instructions

### Step 1: Get Gemini API Key (Free)

1. **Go to Google AI Studio:**
   https://makersuite.google.com/app/apikey

2. **Sign in** with your Google account

3. **Click "Get API Key"** or "Create API Key"

4. **Copy the API key** (starts with `AIzaSy...`)

### Step 2: Configure in Admin Panel

1. **Login to admin panel:**
   ```
   http://localhost/NusantaraRentalCar/admin/
   ```

2. **Go to Settings** (from sidebar)

3. **Click "Site Settings" tab**

4. **Paste your Gemini API key** in the field

5. **Click "Save Settings"**

6. **Done!** The AI chat is now active

---

## Rate Limits (Free Tier)

### Gemini 1.5 Flash (Model Used)
- **Requests per minute:** 15
- **Requests per day:** 1,500
- **Cost:** FREE

### What Happens When Limit Is Hit?
- System automatically falls back to keyword matching
- No errors shown to users
- Chat continues to work normally

---

## Testing the AI Chat

### Open the chat on your website:
1. Go to: http://localhost/NusantaraRentalCar/
2. Click the chat icon (bottom right)
3. Try these questions:

**Example Questions:**
```
- Hi, what cars do you have?
- I need a car for a family trip
- Show me the cheapest automatic car
- What luxury cars are available?
- I want a 7-seater SUV
- Compare Honda and Toyota options
- What's the price range for electric cars?
- Do you have manual transmission cars?
```

---

## How It Works

### AI Mode (When API Key Is Configured)

1. User sends message
2. System loads ALL available cars from database
3. Sends car data + user question to Gemini AI
4. Gemini provides intelligent, contextual answer
5. If rate limit hit: Falls back to keywords

### Keyword Mode (No API Key or Fallback)

1. User sends message
2. System matches keywords (family, cheap, luxury, etc.)
3. Queries database for matching cars
4. Returns pre-formatted response

---

## Database Context Sent to AI

The AI receives information about:
- Car brand and model
- Year
- Number of seats
- Transmission type (manual/automatic)
- Fuel type (petrol/diesel/electric/hybrid)
- Price per day
- Description (if available)
- Availability status

---

## Troubleshooting

### AI Not Responding Intelligently?
- Check if API key is saved in Settings
- Verify API key is correct
- Check rate limits at: https://ai.google.dev/

### Chat Not Working At All?
- Check browser console for errors (F12)
- Verify database connection
- Make sure cars exist in database

### Rate Limit Exceeded Message?
- Wait 1 minute (15 requests/min limit)
- System will automatically use fallback
- Consider upgrading API quota if needed

---

## Cost Information

### Free Tier (Current)
- **0 USD** per month
- 1,500 requests/day
- Perfect for small to medium sites

### If You Need More:
- Gemini has paid tiers with higher limits
- Current setup handles ~50 conversations/day easily
- Check pricing: https://ai.google.dev/pricing

---

## Advanced Configuration

### Change AI Model

Edit `api/chat.php` line with URL:
```php
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;
```

Available models:
- `gemini-1.5-flash` (Current - Fast, recommended)
- `gemini-1.5-pro` (More capable, slower)
- `gemini-1.0-pro` (Older, still good)

### Adjust Response Length

Edit `api/chat.php` in `generationConfig`:
```php
'maxOutputTokens' => 200,  // Increase for longer responses
```

### Change Temperature (Creativity)

```php
'temperature' => 0.7,  // 0.0 = Focused, 1.0 = Creative
```

---

## Security Notes

- API key is stored in database (site_settings table)
- Only admins can view/edit API key
- API calls are made server-side (key not exposed to users)
- Rate limiting prevents abuse
- Chat history is logged for analysis

---

## Support

For issues or questions:
1. Check documentation above
2. Review `api/chat.php` for logic
3. Check browser console for JavaScript errors
4. Verify database connection

---

**Last Updated:** February 11, 2026
**Version:** 2.0 (Gemini AI Integration)
