<?php

namespace Core\Mod\Web\Mcp;

use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

/**
 * MCP prompt for guided biolink page creation.
 *
 * Provides step-by-step instructions for creating a complete biolink page
 * with blocks, theme, and optional tracking pixels.
 *
 * Part of TASK-011 Phase 12: MCP Tools Expansion for BioHost (AC53).
 */
class CreateBioPagePrompt extends Prompt
{
    protected string $name = 'create_biolink_page';

    protected string $title = 'Create Bio Link Page';

    protected string $description = 'Step-by-step guide for creating a complete biolink page with blocks, theme, and tracking';

    /**
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument(
                name: 'purpose',
                description: 'The purpose of the biolink page (e.g., personal brand, business, event, product launch)',
                required: true
            ),
            new Argument(
                name: 'links_count',
                description: 'Approximate number of links to include (default: 5)',
                required: false
            ),
            new Argument(
                name: 'include_social',
                description: 'Whether to include social media icons (yes/no)',
                required: false
            ),
        ];
    }

    public function handle(): Response
    {
        return Response::text(<<<'PROMPT'
# Create a Bio Link Page

Follow these steps to create a complete biolink page for your user.

## Step 1: Gather Information

Ask the user for:
- **Page name/title**: What should the page be called?
- **URL slug**: What URL path should it use? (e.g., "john-smith" for link.host.uk.com/john-smith)
- **Profile image**: Do they have a profile image URL?
- **Bio text**: A short description or tagline

## Step 2: Create the Biolink

Use the `biolink` tool with action `create`:
```json
{
  "action": "create",
  "user_id": <user_id>,
  "url": "<url-slug>",
  "title": "<page-title>",
  "type": "biolink"
}
```

## Step 3: Add Profile Block

Add a profile/avatar block first:
```json
{
  "action": "add_block",
  "biolink_id": <biolink_id>,
  "block_type": "avatar",
  "settings": {
    "image_url": "<profile-image-url>",
    "name": "<display-name>",
    "bio": "<short-bio>"
  }
}
```

## Step 4: Add Link Blocks

For each link the user wants to add:
```json
{
  "action": "add_block",
  "biolink_id": <biolink_id>,
  "block_type": "link",
  "settings": {
    "name": "<link-text>",
    "url": "<destination-url>",
    "icon": "<optional-icon>"
  }
}
```

Common block types:
- `link` - Standard clickable link button
- `heading` - Section heading text
- `paragraph` - Text block
- `image` - Display image
- `video` - YouTube/Vimeo embed
- `socials` - Social media icon row
- `email_collector` - Email signup form
- `phone_collector` - Phone number collection
- `contact_collector` - Full contact form

## Step 5: Add Social Icons (Optional)

If the user wants social media icons:
```json
{
  "action": "add_block",
  "biolink_id": <biolink_id>,
  "block_type": "socials",
  "settings": {
    "platforms": {
      "twitter": "https://twitter.com/username",
      "instagram": "https://instagram.com/username",
      "linkedin": "https://linkedin.com/in/username"
    }
  }
}
```

## Step 6: Apply a Theme (Optional)

List available themes:
```json
{
  "action": "list_themes",
  "user_id": <user_id>
}
```

Apply a theme:
```json
{
  "action": "apply_theme",
  "biolink_id": <biolink_id>,
  "theme_id": <theme_id>
}
```

## Step 7: Add Tracking (Optional)

If the user wants analytics tracking, create and attach a pixel:
```json
{
  "action": "create_pixel",
  "user_id": <user_id>,
  "type": "google_analytics",
  "pixel_id": "G-XXXXXXXXXX",
  "name": "Google Analytics"
}
```

Then attach it:
```json
{
  "action": "attach_pixel",
  "biolink_id": <biolink_id>,
  "pixel_id": <pixel_id>
}
```

## Step 8: Verify and Share

Get the final biolink details:
```json
{
  "action": "get",
  "biolink_id": <biolink_id>
}
```

Share the full URL with the user and confirm all blocks are in place.

---

**Tips:**
- Blocks appear in the order they are added
- Use `update_block` with `order` parameter to reorder
- Use `update_block` with `is_enabled: false` to temporarily hide blocks
- Generate a QR code with `generate_qr` action for print materials
PROMPT
        );
    }
}
