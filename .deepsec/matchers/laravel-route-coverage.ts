import type { CandidateMatch, MatcherPlugin } from "deepsec/config";

/**
 * Entry-point coverage for Laravel route files. Every Route::* definition
 * in routes/*.php becomes a candidate so the AI reads the full route
 * declaration and the middleware stack around it. Without this, deepsec's
 * default (TS/Next.js-tilted) globs miss Laravel routing entirely.
 *
 * Goal: catch routes that should require `auth`, `verified`, or `admin`
 * middleware but don't, plus any debug / unauthenticated `Route::get()`
 * calls reintroduced over time.
 */
export const laravelRouteCoverage: MatcherPlugin = {
  slug: "laravel-route-coverage",
  description: "Laravel Route::* declaration in routes/*.php",
  noiseTier: "noisy",
  filePatterns: ["routes/*.php"],
  match(content, filePath): CandidateMatch[] {
    if (/\/(tests?|database\/seeders)\//.test(filePath)) return [];

    const lines = content.split("\n");
    const matches: CandidateMatch[] = [];
    const routeRe =
      /Route::(get|post|put|patch|delete|any|match|redirect|view|resource|apiResource|group|middleware|prefix)\s*\(/;

    for (let i = 0; i < lines.length; i++) {
      if (!routeRe.test(lines[i])) continue;
      const start = Math.max(0, i - 1);
      const end = Math.min(lines.length, i + 5);
      matches.push({
        vulnSlug: "laravel-route-coverage",
        lineNumbers: [i + 1],
        snippet: lines.slice(start, end).join("\n"),
        matchedPattern: "Laravel route declaration",
      });
    }
    return matches;
  },
};
