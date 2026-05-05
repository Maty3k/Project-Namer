import type { CandidateMatch, MatcherPlugin } from "deepsec/config";
import { regexMatcher } from "deepsec/config";

/**
 * Mass-assignment via untrusted request payload. Eloquent's `$fillable`
 * is the only protection between request-shaped arrays and DB columns;
 * passing `$request->all()` (or `->input()` with no key, or `->except()`
 * which is equally trusting) into `create`/`update`/`fill`/`forceFill`
 * lets an attacker set any column listed in `$fillable` — typically
 * `is_admin`, `user_id`, `verified_at`, `role`, etc.
 *
 * `forceFill` and `forceCreate` skip `$fillable` entirely and are
 * **always** a finding when reached from request input.
 *
 * Skips Form Request classes (which validate before passing through)
 * and the Laravel auth scaffolding under `app/Http/Controllers/Auth/`.
 */
export const laravelMassAssignment: MatcherPlugin = {
  slug: "laravel-mass-assignment",
  description: "Eloquent create/update/fill called with $request->all()/->input()/->except()/forceFill",
  noiseTier: "precise",
  filePatterns: [
    "app/**/*.php",
    "routes/*.php",
  ],
  match(content, filePath): CandidateMatch[] {
    if (/\/(tests?)\//.test(filePath)) return [];
    if (/app\/Http\/Requests\//.test(filePath)) return [];

    return regexMatcher(
      "laravel-mass-assignment",
      [
        {
          regex:
            /->\s*(?:create|update|fill|updateOrCreate|firstOrCreate)\s*\(\s*\$request\s*->\s*(?:all|input|except)\s*\(/,
          label: "Eloquent write taking $request->all()/input()/except()",
        },
        {
          regex: /->\s*forceFill\s*\(/,
          label: "Eloquent forceFill (bypasses $fillable)",
        },
        {
          regex: /::\s*forceCreate\s*\(/,
          label: "Eloquent forceCreate (bypasses $fillable)",
        },
      ],
      content,
    );
  },
};
