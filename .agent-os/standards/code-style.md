# Code Style Guide

> Version: 1.0.0
> Last Updated: 2025-04-24

# Context

This file is part of the Agent OS standards system. These global code style rules are referenced by all product codebases and provide default formatting guidelines. Individual projects may extend or override these rules in their `.agent-os/product/code-style.md` file.

## PHP
- Write modern PHP. The minimum version required for each project is defined in its composer.json, so make sure that everything you write is considered best practices for that version.

## Comments in PHP

- Do not use comments inside methods.
- Every method must have a docblock with a one or two-sentence description.
- Every class must have a docblock with a one or two-sentence description.
- Method docblocks must not contain `@return` tags unless the return of the method is not specific enough to satisfy the PHPStan type checking.
- Method docblocks must not contain `@param` tags unless the parameter is not specific enough to satisfy the PHPStan type checking.

## Naming in PHP

- Use `PascalCase` for class names.
- Use `camelCase` for method names.
- Use `snake_case` for variable names unless the variable holds to a callable, in which case use `camelCase`.
- Use `PascalCase` for enum case names
- Names must be descriptive. Long names are perfectly acceptable if the length is required to fully convey what the variable or method does.

## PHP Class Structure

- Strongly prefer constructor property promotion
- Any property that does not need to be `public` should be `protected` rather than `private`
- If a class can be marked readonly, do so
- Classes in application code should be `final` by default
- Classes in library or package code should never be `final`
- No static methods except for static constructors where they make sense.

## Laravel

- We always use the current version of Laravel.
- Lean on the framework to do anything that it offers.
- Prefer facades over helper methods, except for `route()`
- Do not use the root facade aliases. Always use the full facade namespace.
- Prefer PHP Attributes whenever they are an option to solve the problem.

### Routing
- All routes must be named
- Use route groups to bundle routes that use the same combination of middleware
- Whenver referring to an internal route, use Laravel's `route()` with the route name rather than the URI

## Creating Files

- Whenever creating a file, always use the `php artisan make:...` command if one exists for the file type that you are creating.
- When creating Livewire components, always use `--pest` to create a corresponding Pest test.

### Naming New Files

- Follow standard Laravel naming conventions where those are established in the documentation
- Enums should be plural (AccountRoles vs AccountRole)
- Names should be self-documenting and unambiguous. It is fine to use extra characters to make it clear what a class does.

## Enums
- Enums must be backed, either by a string or integer.
- When no backing strategy is obvious based on the use case, the value should be a slug representation of the case name.

## String Formatting
- Use double quotes for single-line strings
- Use heredocs for multi-line strings
- Use brackets for string interpolation: `"Hello {$name}!"` even where they are optional.
- Use the Laravel Str facade for complex string operations

## Blade
- Keep all business logic inside of the class.
- Never use the `@php` directive.
- Minimize the use of conditionals `@if` and `@else`
- Never use `@elseif`. If you feel like you need `@elseif` find another approach that doesn't require it
- Always use `route()` for internal links


## HTML/Template Formatting

### Structure Rules
- Use 4 spaces for indentation
- Place nested elements on new lines with proper indentation
- Content between tags should be on its own line when multi-line

### Attribute Formatting
- Place each HTML attribute on its own line
- Align attributes vertically
- Keep the closing `>` on the same line as the last attribute

### Example HTML Structure

```html
<div class="container">
  <header class="flex flex-col space-y-2
                 md:flex-row md:space-y-0 md:space-x-4">
    <h1 class="text-primary dark:text-primary-300">
      Page Title
    </h1>
    <nav class="flex flex-col space-y-2
                md:flex-row md:space-y-0 md:space-x-4">
      <a href="{{ route('welcome') }}"
         class="btn-ghost">
        Home
      </a>
      <a href="{{ route('about') }}"
         class="btn-ghost">
        About
      </a>
    </nav>
  </header>
</div>
```

## Tailwind CSS preferences

### Multi-line CSS classes in markup

- We use a unique multi-line formatting style when writing Tailwind CSS classes in HTML markup and ERB tags, where the classes for each responsive size are written on their own dedicated line.
- The top-most line should be the smallest size (no responsive prefix). Each line below it should be the next responsive size up.
- Each line of CSS classes should be aligned vertically.
- focus and hover classes should be on their own additional dedicated lines.
- We implement one additional responsive breakpoint size called 'xs' which represents 400px.
- If there are any custom CSS classes being used, those should be included at the start of the first line.

**Example of multi-line Tailwind CSS classes:**

<div class="custom-cta bg-gray-50 dark:bg-gray-900 p-4 rounded cursor-pointer w-full
            hover:bg-gray-100 dark:hover:bg-gray-800
            xs:p-6
            sm:p-8 sm:font-medium
            md:p-10 md:text-lg
            lg:p-12 lg:text-xl lg:font-semibold lg:2-3/5
            xl:p-14 xl:text-2xl
            2xl:p-16 2xl:text-3xl 2xl:font-bold 2xl:w-3/4">
  I'm a call-to-action!
</div>
