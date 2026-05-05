import type { CandidateMatch, MatcherPlugin } from "deepsec/config";

/**
 * Entry-point coverage for Laravel HTTP controllers. Each public action
 * method becomes a candidate so the AI sees the request flow, the model
 * lookup pattern, and whether `$this->authorize(...)` / a policy gate is
 * present. The default deepsec matcher set has no glob for app/Http/Controllers,
 * so without this no controller is read.
 *
 * Goal: catch missing ownership checks on `{project}` / `{share}` /
 * `{moodBoard}` route bindings, mass-assignment via `$request->all()`,
 * and unsanitized passthrough of user input into AI prompts.
 */
export const laravelControllerCoverage: MatcherPlugin = {
  slug: "laravel-controller-coverage",
  description: "Public controller action method in app/Http/Controllers",
  noiseTier: "noisy",
  filePatterns: ["app/Http/Controllers/**/*.php"],
  match(content, filePath): CandidateMatch[] {
    if (/\/(tests?|stubs)\//i.test(filePath)) return [];

    const lines = content.split("\n");
    const matches: CandidateMatch[] = [];
    const actionRe =
      /^\s*public\s+function\s+(?!__construct\b)([A-Za-z_][A-Za-z0-9_]*)\s*\(/;

    for (let i = 0; i < lines.length; i++) {
      const m = actionRe.exec(lines[i]);
      if (!m) continue;
      const start = Math.max(0, i - 2);
      const end = Math.min(lines.length, i + 8);
      matches.push({
        vulnSlug: "laravel-controller-coverage",
        lineNumbers: [i + 1],
        snippet: lines.slice(start, end).join("\n"),
        matchedPattern: `controller action: ${m[1]}`,
      });
    }
    return matches;
  },
};
