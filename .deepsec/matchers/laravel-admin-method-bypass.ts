import type { CandidateMatch, MatcherPlugin } from "deepsec/config";

/**
 * Project-Namer-specific footgun. `App\Models\User::isAdmin()` returns
 * true if the user's email contains the substring `admin` or matches a
 * hard-coded list. The real authorization boundary is the `is_admin`
 * column (checked by `EnsureUserIsAdmin` middleware). Any code path that
 * authorizes on `$user->isAdmin()` instead of `$user->is_admin` /
 * middleware is bypassable by registering `attacker+admin@…`.
 *
 * The matcher flags `->isAdmin()` calls anywhere except the method's own
 * definition site. The AI judges whether the call is an authorization
 * gate (TP) or a UI-display hint (FP).
 */
export const laravelAdminMethodBypass: MatcherPlugin = {
  slug: "laravel-admin-method-bypass",
  description: "Call to User::isAdmin() (email-substring check, bypassable)",
  noiseTier: "precise",
  filePatterns: ["app/**/*.php", "routes/*.php", "database/seeders/**/*.php"],
  match(content, filePath): CandidateMatch[] {
    if (/\/(tests?)\//.test(filePath)) return [];
    if (/app\/Models\/User\.php$/.test(filePath)) return [];

    const lines = content.split("\n");
    const matches: CandidateMatch[] = [];
    const callRe = /->isAdmin\s*\(\s*\)/;

    for (let i = 0; i < lines.length; i++) {
      if (!callRe.test(lines[i])) continue;
      const start = Math.max(0, i - 1);
      const end = Math.min(lines.length, i + 4);
      matches.push({
        vulnSlug: "laravel-admin-method-bypass",
        lineNumbers: [i + 1],
        snippet: lines.slice(start, end).join("\n"),
        matchedPattern: "User::isAdmin() invocation",
      });
    }
    return matches;
  },
};
