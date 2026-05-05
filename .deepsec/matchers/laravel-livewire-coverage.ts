import type { CandidateMatch, MatcherPlugin } from "deepsec/config";

/**
 * Entry-point coverage for Livewire 3 components and Volt single-file
 * components. Livewire actions are network-reachable from the client
 * (any `public` method on the component class can be invoked via
 * `wire:click="method"`), so they're effectively public endpoints with
 * no route-table entry — and deepsec's default globs miss them entirely.
 *
 * The match is on `public function …` because that's the action surface,
 * plus the Volt `$action = fn() => …` shorthand. `mount`, `render`,
 * lifecycle hooks (`updated*`, `hydrate*`, `dehydrate*`), and getters
 * are filtered out so the AI sees the actual user-callable methods.
 */
export const laravelLivewireCoverage: MatcherPlugin = {
  slug: "laravel-livewire-coverage",
  description: "Livewire/Volt component action method (network-reachable)",
  noiseTier: "noisy",
  filePatterns: [
    "app/Livewire/**/*.php",
    "resources/views/livewire/**/*.blade.php",
  ],
  match(content, filePath): CandidateMatch[] {
    if (/\/(tests?)\//.test(filePath)) return [];

    const lines = content.split("\n");
    const matches: CandidateMatch[] = [];
    const actionRe =
      /^\s*public\s+function\s+(?!mount\b|render\b|boot\b|booted\b|hydrate|dehydrate|updating|updated|placeholder\b|rules\b|messages\b|validationAttributes\b|getListeners\b|__)([A-Za-z_][A-Za-z0-9_]*)\s*\(/;
    const voltActionRe = /\$\w+\s*=\s*(?:fn\b|function\b)/;

    for (let i = 0; i < lines.length; i++) {
      const a = actionRe.exec(lines[i]);
      const v = !a && voltActionRe.test(lines[i]);
      if (!a && !v) continue;
      const start = Math.max(0, i - 2);
      const end = Math.min(lines.length, i + 8);
      matches.push({
        vulnSlug: "laravel-livewire-coverage",
        lineNumbers: [i + 1],
        snippet: lines.slice(start, end).join("\n"),
        matchedPattern: a
          ? `livewire action: ${a[1]}`
          : "volt action closure",
      });
    }
    return matches;
  },
};
