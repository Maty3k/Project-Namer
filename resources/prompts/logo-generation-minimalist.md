---
provider: openai
model: gpt-image-1
size: 1024x1024
quality: low
response_format: b64_json
n: 1
style: minimalist
style_description: minimalist, clean, simple geometric shapes
description: "Logo generation prompt for minimalist style"
---

Design a professional minimalist logo for '{$businessName}'{$businessDescriptionClause}.

STYLE REQUIREMENTS:
- Clean, simple geometric shapes with precise lines
- Maximum 2-3 colors, high contrast
- Elegant negative space usage
- Modern sans-serif typography
- Balanced, centered composition

TEXT REQUIREMENTS:
- Include the business name '{$businessName}' in the logo
- Use clean, modern, highly readable sans-serif font
- Text must be perfectly legible and professionally typeset
- Letter spacing should be optimal for readability

COMPOSITION:
- Icon element can be integrated with or separate from text
- Perfect symmetry and alignment
- White or light background
- Professional presentation suitable for business cards and websites

The logo must look polished, professional, and ready to use immediately.
