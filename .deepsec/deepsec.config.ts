import { type DeepsecPlugin, defineConfig } from "deepsec/config";
import { laravelAdminMethodBypass } from "./matchers/laravel-admin-method-bypass.js";
import { laravelControllerCoverage } from "./matchers/laravel-controller-coverage.js";
import { laravelLivewireCoverage } from "./matchers/laravel-livewire-coverage.js";
import { laravelMassAssignment } from "./matchers/laravel-mass-assignment.js";
import { laravelRouteCoverage } from "./matchers/laravel-route-coverage.js";

const laravelPlugin: DeepsecPlugin = {
  name: "project-namer-laravel",
  matchers: [
    laravelRouteCoverage,
    laravelControllerCoverage,
    laravelLivewireCoverage,
    laravelAdminMethodBypass,
    laravelMassAssignment,
  ],
};

export default defineConfig({
  projects: [
    {
      id: "Project-Namer",
      root: "..",
      priorityPaths: [
        "app/Http/Controllers/",
        "app/Http/Middleware/",
        "app/Livewire/",
        "app/Services/",
        "app/Policies/",
        "routes/",
      ],
    },
    // <deepsec:projects-insert-above>
  ],
  plugins: [laravelPlugin],
});
