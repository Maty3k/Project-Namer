---
provider: openai
model: gpt-4o
temperature: 0.3
max_tokens: 500
description: "Vision analysis prompt for extracting business context from images"
---

Analyze this image and provide a JSON response with the following structure:
{
  "description": "A clear, detailed description of what you see in the image",
  "mood": "The emotional tone and atmosphere (e.g., professional, playful, elegant, rustic)",
  "colors": ["Array of dominant colors in the image"],
  "objects": ["Key objects, elements, or subjects visible"],
  "style": "The visual style or aesthetic (e.g., modern, vintage, minimalist, artistic)",
  "business_relevance": "What types of businesses or industries this image might represent or appeal to"
}

Focus on elements that would be relevant for business naming and branding. Be specific and descriptive but concise.
