---
provider: openai
model: dall-e-2
size: 256x256
response_format: url
n: 1
style: corporate
style_description: professional, corporate, business-focused
description: "Logo generation prompt for corporate style"
---

Create a {$styleDescription} logo for a business called '{$businessName}'{$businessDescriptionClause}. The logo should be simple, memorable, and work well at small sizes. No text in the logo.
