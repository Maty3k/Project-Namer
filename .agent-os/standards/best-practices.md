# Development Best Practices

> Version: 1.0.0
> Last updated: 2025-03-02
> Scope: Global development standards

## Context

This file is part of the Agent OS standards system. These global best practices are referenced by all product codebases and provide default development guidelines. Individual projects may extend or override these practices in their `.agent-os/product/dev-best-practices.md` file.

## Core Principles

### Keep It Simple
- Implement code in the fewest lines possible
- Avoid over-engineering solutions
- Choose straightforward approaches over clever ones

### Optimize for Readability
- Prioritize code clarity over micro-optimizations
- Write self-documenting code with clear variable names

### DRY (Don't Repeat Yourself)
- Extract repeated business logic to private methods if it's only used in one class
- Extract repeated business logic into invokable classes in App\Actions if it's used in more than one class
- Extract repeated UI markup to Blade components

## Dependencies

### Choose Libraries Wisely
We never add dependencies during the task execution process that were not decided upon during the planning process.

When suggesting third-party dependencies:
- Select the most popular and actively maintained option
- Check the library's GitHub repository for:
  - Recent commits (within last 6 months)
  - Active issue resolution
  - Number of stars/downloads
  - Clear documentation

Make sure you call out your recommendation to add a third-party package when creating the spec so that we can agree on
it before the building process begins. Where there is more than one good candidate for a third-party dependency, list
the good options with their strengths and weaknesses and tell me which one you recommend.

## Code Organization

### Folder Structure

Use the default Laravel skeleton structure, grouping files by their type. For non-core Laravel files, use the following:

- Verbs Events: App\Events
- Verbs States: App\States
- Actions: App\Actions
- Enums: App\Enums

### File Structure
- Keep files focused on a single responsibility
- Group related functionality together
- Use consistent naming conventions

### Testing
- Write tests for new functionality
- When modifying existing code, make sure it is under test. If it is not, write a test first before modifying the code.
- When fixing a bug, write a test first to demonstrate the bug, then fix the bug and ensure the test passes.
- Maintain existing test coverage
- Test edge cases and error conditions
- Never skip or delete a test unless specifically instructed to do so
- Never mock any class that offers test affordances like `fake()` in most Laravel facades

## Git

- The GitHub CLI (`gh`) is available to you, and you should use it for git operations wherever it makes sense.
- **Important -** Do not do any work on the main branch. Create a branch for the task you are assigned and then create a pull request.
- 
### Commits
- Commit frequently, with clear and descriptive messages
- Do not amend or reset any commits. Create a new commit describing what you undid and why.

### Pull Requests
- When opening a PR, make it a Draft PR. I'll tell you when to mark it ready for review.

## Code Quality Checks
- If `composer ready` does not exist in the project, stop and tell me about it. You cannot do any other work until it's there.
- Run `composer ready` frequently and fix any errors that appear
- Never have PHPStan ignore an error unless I tell you to
- Never skip a failing test unless I tell you to
- Call out any errors surfaced by `composer ready` that you are unable to fix without breaking one of the other rules.
- Any errors surfaced by `composer ready` will block CI, so keeping its output error-free is a top priority

