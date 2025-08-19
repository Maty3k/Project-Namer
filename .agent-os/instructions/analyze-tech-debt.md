---
description: Analyze, Describe, and Prioritize Tech Debt
globs:
alwaysApply: false
version: 1.0
encoding: UTF-8
---

# Analyze, Describe, and Prioritize Tech Debt

<ai_meta>
  <parsing_rules>
    - Process XML blocks first for structured data
    - Execute instructions in sequential order
    - Use templates as exact patterns
    - Analyze existing code before generating documentation
  </parsing_rules>
  <file_conventions>
    - encoding: UTF-8
    - line_endings: LF
    - indent: 2 spaces
    - markdown_headers: no indentation
  </file_conventions>
</ai_meta>

## Overview

<purpose>
  - Run deterministic tooling to analyze code quality
  - Identify areas of technical debt in the codebase
  - Prioritize technical debt based on potential impact
  - Create an actionable inventory of technical debt items
</purpose>

<context>
  - Part of Agent OS framework
  - Optimized for Laravel, PHP, JavaScript, Tailwind CSS, and Blade
  - Focuses on code quality and maintainability
  - Helps teams systematically address technical debt
</context>

<prerequisites>
  - Existing product codebase
  - Write access to project root
  - PHP and Composer installed
  - Node.js and npm installed (for JavaScript analysis)
</prerequisites>

<process_flow>

<step number="1" name="run_code_quality_tools">

### Step 1: Run Code Quality Analysis Tools

<step_metadata>
  <action>run deterministic code analysis tools</action>
  <purpose>gather objective metrics on code quality</purpose>
</step_metadata>

<analysis_tools>
  <php_tools>
    - **PHPStan**: Static analysis for PHP (`composer stan` or `./vendor/bin/phpstan analyze`)
    - **PHP Mess Detector**: Identifies complex code, possible bugs (`./vendor/bin/phpmd app text codesize,unusedcode,naming`)
    - **PHP Code Sniffer**: Coding standards violations (`./vendor/bin/phpcs --standard=PSR12 app`)
    - **Pint**: Laravel's PHP code style fixer (`composer lint` or `./vendor/bin/pint --test`)
    - **Larastan**: Laravel-specific static analysis (`./vendor/bin/phpstan analyze --configuration=phpstan.neon`)
  </php_tools>
  <test_coverage>
    - **PestPHP Coverage**: Test coverage analysis (`composer test --coverage` or `./vendor/bin/pest --coverage`)
    - **PHPUnit Coverage**: Alternative test coverage (`./vendor/bin/phpunit --coverage-text`)
  </test_coverage>
  <javascript_tools>
    - **ESLint**: JavaScript code quality (`npx eslint resources/js`)
    - **Jest Coverage**: JavaScript test coverage (`npx jest --coverage`)
  </javascript_tools>
  <blade_tools>
    - **Blade Formatter**: Check Blade template formatting (`npx blade-formatter --check resources/views`)
  </blade_tools>
  <tailwind_tools>
    - **Tailwind Config Validator**: Validate Tailwind configuration (`npx tailwindcss-cli build --help`)
    - **Tailwind Linter**: Check for Tailwind best practices (`npx tlint resources/views`)
  </tailwind_tools>
  <dependency_tools>
    - **Composer Outdated**: Check for outdated PHP dependencies (`composer outdated --direct`)
    - **npm Outdated**: Check for outdated JavaScript dependencies (`npm outdated`)
  </dependency_tools>
</analysis_tools>

<tool_execution_template>
  ```bash
  # PHP Analysis
  echo "Running PHP Static Analysis..."
  composer stan || ./vendor/bin/phpstan analyze
  
  echo "Running PHP Mess Detector..."
  ./vendor/bin/phpmd app text codesize,unusedcode,naming
  
  echo "Checking Code Style..."
  composer lint --test || ./vendor/bin/pint --test
  
  # Test Coverage Analysis
  echo "Analyzing Test Coverage..."
  composer test --coverage || ./vendor/bin/pest --coverage || ./vendor/bin/phpunit --coverage-text
  
  # JavaScript Analysis
  echo "Running JavaScript Linting..."
  npx eslint resources/js
  
  # Dependency Analysis
  echo "Checking for Outdated Dependencies..."
  composer outdated --direct
  npm outdated
  ```
</tool_execution_template>

<instructions>
  ACTION: Run code quality analysis tools
  DOCUMENT: Capture all output from tools
  IDENTIFY: Areas where code doesn't meet quality standards
  NOTE: Missing tests, complexity issues, and outdated dependencies
</instructions>

</step>

<step number="2" name="identify_tech_debt">

### Step 2: Identify Technical Debt

<step_metadata>
  <supplements>code quality analysis</supplements>
  <gathers>comprehensive tech debt inventory</gathers>
</step_metadata>

<tech_debt_categories>
  <code_quality>
    - **High Complexity**: Methods/classes with high cyclomatic complexity
    - **Code Smells**: Identified code smells and anti-patterns
    - **Style Violations**: Deviations from coding standards
    - **Static Analysis Issues**: Type errors and potential bugs
  </code_quality>
  <test_coverage>
    - **Missing Tests**: Untested or poorly tested code
    - **Fragile Tests**: Tests that frequently break
    - **Slow Tests**: Tests that take too long to run
  </test_coverage>
  <architecture>
    - **Tight Coupling**: Highly interdependent components
    - **Poor Separation of Concerns**: Mixed responsibilities
    - **Inconsistent Patterns**: Varying approaches to similar problems
  </architecture>
  <dependencies>
    - **Outdated Libraries**: Dependencies needing updates
    - **Security Vulnerabilities**: Known security issues
    - **Deprecated APIs**: Usage of deprecated functionality
  </dependencies>
  <performance>
    - **N+1 Queries**: Inefficient database access patterns
    - **Unoptimized Assets**: Large JS/CSS bundles
    - **Slow Response Times**: Endpoints with poor performance
  </performance>
</tech_debt_categories>

<tech_debt_template>
  ## Technical Debt Inventory

  ### Code Quality Issues
  
  | Issue | Location | Description | Tool | Severity |
  |-------|----------|-------------|------|----------|
  | [ISSUE_TYPE] | [FILE_PATH:LINE] | [DESCRIPTION] | [TOOL_NAME] | [HIGH/MEDIUM/LOW] |
  
  ### Test Coverage Gaps
  
  | Missing Coverage | Location | Current Coverage | Target Coverage |
  |------------------|----------|------------------|----------------|
  | [COMPONENT_NAME] | [FILE_PATH] | [CURRENT_PERCENTAGE] | 80% |
  
  ### Architectural Concerns
  
  | Concern | Affected Area | Description | Impact |
  |---------|---------------|-------------|--------|
  | [CONCERN_TYPE] | [COMPONENT/MODULE] | [DESCRIPTION] | [IMPACT_DESCRIPTION] |
  
  ### Dependency Issues
  
  | Dependency | Current Version | Latest Version | Risk |
  |------------|----------------|---------------|------|
  | [PACKAGE_NAME] | [CURRENT_VERSION] | [LATEST_VERSION] | [RISK_LEVEL] |
  
  ### Performance Problems
  
  | Problem | Location | Current Metric | Target Metric |
  |---------|----------|---------------|--------------|
  | [PROBLEM_TYPE] | [ENDPOINT/COMPONENT] | [CURRENT_PERFORMANCE] | [TARGET_PERFORMANCE] |
</tech_debt_template>

<instructions>
  ACTION: Categorize all issues from analysis tools
  DOCUMENT: Create comprehensive tech debt inventory
  IDENTIFY: Patterns and systemic issues
  NOTE: Severity and impact of each item
</instructions>

</step>

<step number="3" name="prioritize_tech_debt">

### Step 3: Prioritize Technical Debt

<step_metadata>
  <uses>tech debt inventory</uses>
  <produces>prioritized action plan</produces>
</step_metadata>

<prioritization_criteria>
  <impact_factors>
    - **Bug Risk**: Likelihood of causing bugs (1-5)
    - **Performance Impact**: Effect on system performance (1-5)
    - **Development Friction**: Slows down new feature development (1-5)
    - **Maintenance Burden**: Increases ongoing maintenance cost (1-5)
    - **User Experience**: Impacts end-user experience (1-5)
  </impact_factors>
  <effort_estimation>
    - **Time to Fix**: Estimated hours to address (S/M/L/XL)
    - **Risk of Change**: Potential for introducing new issues (Low/Medium/High)
    - **Dependencies**: Requires other changes first (Yes/No)
  </effort_estimation>
  <business_context>
    - **Affected Features**: Business-critical features impacted
    - **Strategic Alignment**: Relevance to product roadmap
    - **Technical Roadblocks**: Blocks planned features
  </business_context>
</prioritization_criteria>

<prioritization_formula>
  Priority Score = (Bug Risk + Performance Impact + Development Friction + Maintenance Burden + User Experience) * (1 / Effort Factor)
  
  Where:
  - Each impact factor is rated 1-5
  - Effort Factor is derived from Time to Fix (S=1, M=2, L=3, XL=4)
  - Higher scores indicate higher priority
</prioritization_formula>

<prioritized_inventory_template>
  ## Prioritized Technical Debt

  ### Critical Priority (Address Immediately)
  
  | Issue | Location | Impact | Effort | Priority Score | Rationale |
  |-------|----------|--------|--------|---------------|-----------|
  | [ISSUE_DESCRIPTION] | [LOCATION] | [IMPACT_SCORE] | [EFFORT] | [PRIORITY_SCORE] | [RATIONALE] |
  
  ### High Priority (Address in Next Sprint)
  
  | Issue | Location | Impact | Effort | Priority Score | Rationale |
  |-------|----------|--------|--------|---------------|-----------|
  | [ISSUE_DESCRIPTION] | [LOCATION] | [IMPACT_SCORE] | [EFFORT] | [PRIORITY_SCORE] | [RATIONALE] |
  
  ### Medium Priority (Address in Next Quarter)
  
  | Issue | Location | Impact | Effort | Priority Score | Rationale |
  |-------|----------|--------|--------|---------------|-----------|
  | [ISSUE_DESCRIPTION] | [LOCATION] | [IMPACT_SCORE] | [EFFORT] | [PRIORITY_SCORE] | [RATIONALE] |
  
  ### Low Priority (Address When Convenient)
  
  | Issue | Location | Impact | Effort | Priority Score | Rationale |
  |-------|----------|--------|--------|---------------|-----------|
  | [ISSUE_DESCRIPTION] | [LOCATION] | [IMPACT_SCORE] | [EFFORT] | [PRIORITY_SCORE] | [RATIONALE] |
</prioritized_inventory_template>

<instructions>
  ACTION: Apply prioritization formula to tech debt inventory
  DOCUMENT: Create prioritized action plan
  IDENTIFY: Critical issues that need immediate attention
  NOTE: Rationale for prioritization decisions
</instructions>

</step>

<step number="4" name="create_tech_debt_report">

### Step 4: Create Technical Debt Report

<step_metadata>
  <combines>analysis and prioritization</combines>
  <produces>comprehensive tech debt report</produces>
</step_metadata>

<report_sections>
  <executive_summary>
    - Overall code health assessment
    - Key metrics and trends
    - Critical issues requiring attention
    - Recommended next actions
  </executive_summary>
  <detailed_analysis>
    - Complete tech debt inventory
    - Prioritized action items
    - Detailed metrics from all tools
    - Code quality heat maps
  </detailed_analysis>
  <remediation_plan>
    - Suggested timeline for addressing issues
    - Estimated effort for each category
    - Potential quick wins
    - Long-term architectural improvements
  </remediation_plan>
  <prevention_strategies>
    - Recommended process improvements
    - Additional tooling suggestions
    - Code review checklist updates
    - Developer training opportunities
  </prevention_strategies>
</report_sections>

<report_template>
  # Technical Debt Analysis Report

  ## Executive Summary

  Overall Code Health: [HEALTH_SCORE]/100

  [SUMMARY_PARAGRAPH]

  ### Key Metrics
  - Test Coverage: [COVERAGE_PERCENTAGE]%
  - Average Cyclomatic Complexity: [COMPLEXITY_SCORE]
  - Static Analysis Issues: [ISSUE_COUNT]
  - Outdated Dependencies: [OUTDATED_COUNT]

  ### Critical Issues
  1. [CRITICAL_ISSUE_1]
  2. [CRITICAL_ISSUE_2]
  3. [CRITICAL_ISSUE_3]

  ## Detailed Analysis

  [INCLUDE_FULL_TECH_DEBT_INVENTORY]

  ## Remediation Plan

  ### Immediate Actions (Next Sprint)
  - [ACTION_ITEM_1]
  - [ACTION_ITEM_2]
  - [ACTION_ITEM_3]

  ### Short-term Improvements (Next Quarter)
  - [IMPROVEMENT_1]
  - [IMPROVEMENT_2]
  - [IMPROVEMENT_3]

  ### Long-term Strategic Changes
  - [STRATEGIC_CHANGE_1]
  - [STRATEGIC_CHANGE_2]
  - [STRATEGIC_CHANGE_3]

  ## Prevention Strategies

  ### Process Improvements
  - [PROCESS_IMPROVEMENT_1]
  - [PROCESS_IMPROVEMENT_2]

  ### Recommended Tooling
  - [TOOL_RECOMMENDATION_1]
  - [TOOL_RECOMMENDATION_2]

  ### Developer Guidelines
  - [GUIDELINE_1]
  - [GUIDELINE_2]
</report_template>

<instructions>
  ACTION: Compile comprehensive tech debt report
  DOCUMENT: Include all analysis and prioritization
  IDENTIFY: Clear next steps and recommendations
  NOTE: Both tactical and strategic improvements
</instructions>

</step>

<step number="5" name="final_verification">

### Step 5: Final Verification and Summary

<step_metadata>
  <verifies>analysis completeness</verifies>
  <provides>next steps for addressing tech debt</provides>
</step_metadata>

<verification_checklist>
  - [ ] All code quality tools executed successfully
  - [ ] Technical debt inventory is comprehensive
  - [ ] Prioritization applied consistently
  - [ ] Report includes actionable recommendations
  - [ ] Prevention strategies are practical and relevant
</verification_checklist>

<summary_template>
  ## ✅ Technical Debt Analysis Complete

  I've analyzed your [PROJECT_TYPE] codebase and created a comprehensive technical debt inventory.

  ### What I Found

  - **Overall Health**: [HEALTH_SCORE]/100
  - **Critical Areas**: [TOP_3_PROBLEM_AREAS]
  - **Quick Wins**: [TOP_3_QUICK_WINS]
  - **Strategic Concerns**: [TOP_3_STRATEGIC_CONCERNS]

  ### What Was Created

  - ✓ Technical debt inventory with [ITEM_COUNT] items
  - ✓ Prioritized action plan with clear rationale
  - ✓ Comprehensive technical debt report
  - ✓ Prevention strategies to reduce future debt

  ### Next Steps

  1. Review the technical debt report
  2. Schedule the critical priority items for your next sprint
  3. Incorporate the prevention strategies into your development process
  4. Re-run this analysis quarterly to track progress

  Your technical debt is now mapped and prioritized! 🚀
</summary_template>

<instructions>
  ACTION: Verify analysis completeness
  SUMMARIZE: Key findings and recommendations
  PROVIDE: Clear next steps for addressing tech debt
</instructions>

</step>

</process_flow>

## Error Handling

<error_scenarios>
  <scenario name="missing_tools">
    <condition>Required analysis tools not installed</condition>
    <action>Provide installation instructions for missing tools</action>
  </scenario>
  <scenario name="analysis_failure">
    <condition>One or more analysis tools fail to run</condition>
    <action>Document errors and continue with available tools</action>
  </scenario>
  <scenario name="large_codebase">
    <condition>Codebase too large for complete analysis</condition>
    <action>Focus on core components and sample representative areas</action>
  </scenario>
</error_scenarios>

## Execution Summary

<final_checklist>
  <verify>
    - [ ] Code quality tools executed successfully
    - [ ] Technical debt inventory created
    - [ ] Prioritization applied effectively
    - [ ] Comprehensive report generated
    - [ ] Next steps clearly defined
  </verify>
</final_checklist>