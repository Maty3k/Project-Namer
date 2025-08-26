<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class ComponentMappingService
{
    /**
     * Component mapping configuration with upgrade paths
     */
    private array $componentMapping = [
        // High Priority Components (Core functionality)
        'button' => [
            'pro_variant' => 'button',
            'upgrade_priority' => 'high',
            'new_attributes' => ['variant', 'size', 'loading', 'disabled'],
            'deprecated_attributes' => [],
            'dependencies' => [],
            'notes' => 'Enhanced with loading states and better variants',
        ],
        'input' => [
            'pro_variant' => 'input',
            'upgrade_priority' => 'high',
            'new_attributes' => ['variant', 'size', 'error'],
            'deprecated_attributes' => [],
            'dependencies' => [],
            'notes' => 'Better validation states and styling',
        ],
        'select' => [
            'pro_variant' => 'select',
            'upgrade_priority' => 'high',
            'new_attributes' => ['variant', 'size', 'searchable'],
            'deprecated_attributes' => [],
            'dependencies' => [],
            'notes' => 'Enhanced with search functionality',
        ],
        'textarea' => [
            'pro_variant' => 'textarea',
            'upgrade_priority' => 'high',
            'new_attributes' => ['variant', 'size', 'resize'],
            'deprecated_attributes' => [],
            'dependencies' => [],
            'notes' => 'Better resize controls and validation',
        ],
        'checkbox' => [
            'pro_variant' => 'checkbox',
            'upgrade_priority' => 'high',
            'new_attributes' => ['variant', 'size', 'indeterminate'],
            'deprecated_attributes' => [],
            'dependencies' => [],
            'notes' => 'Enhanced with indeterminate state',
        ],
        'field' => [
            'pro_variant' => 'field',
            'upgrade_priority' => 'high',
            'new_attributes' => ['variant', 'error'],
            'deprecated_attributes' => [],
            'dependencies' => ['input', 'textarea', 'select', 'checkbox'],
            'notes' => 'Enhanced field wrapper with better validation',
        ],
        
        // Medium Priority Components (Enhanced functionality)
        'table' => [
            'pro_variant' => 'table',
            'upgrade_priority' => 'medium',
            'new_attributes' => ['sortable', 'filterable', 'pagination'],
            'deprecated_attributes' => [],
            'dependencies' => ['pagination', 'button'],
            'notes' => 'Advanced sorting and filtering capabilities',
        ],
        'modal' => [
            'pro_variant' => 'modal',
            'upgrade_priority' => 'medium',
            'new_attributes' => ['size', 'closable', 'backdrop-dismiss'],
            'deprecated_attributes' => [],
            'dependencies' => ['button'],
            'notes' => 'Enhanced modal with better accessibility',
        ],
        'card' => [
            'pro_variant' => 'card',
            'upgrade_priority' => 'medium',
            'new_attributes' => ['variant', 'hover', 'clickable'],
            'deprecated_attributes' => [],
            'dependencies' => [],
            'notes' => 'Enhanced with hover states and interactions',
        ],
        'callout' => [
            'pro_variant' => 'callout',
            'upgrade_priority' => 'medium',
            'new_attributes' => ['variant', 'dismissible'],
            'deprecated_attributes' => [],
            'dependencies' => ['button'],
            'notes' => 'Enhanced with dismissible functionality',
        ],
        'tooltip' => [
            'pro_variant' => 'tooltip',
            'upgrade_priority' => 'medium',
            'new_attributes' => ['placement', 'delay', 'interactive'],
            'deprecated_attributes' => [],
            'dependencies' => [],
            'notes' => 'Enhanced positioning and interactions',
        ],
        'tabs' => [
            'pro_variant' => 'tabs',
            'upgrade_priority' => 'medium',
            'new_attributes' => ['variant', 'orientation'],
            'deprecated_attributes' => [],
            'dependencies' => ['button'],
            'notes' => 'Available in Pro with vertical orientation',
        ],
        'pagination' => [
            'pro_variant' => 'pagination',
            'upgrade_priority' => 'medium',
            'new_attributes' => ['variant', 'show-info'],
            'deprecated_attributes' => [],
            'dependencies' => ['button'],
            'notes' => 'Enhanced pagination with more options',
        ],
        
        // Low Priority Components (Styling and layout)
        'separator' => [
            'pro_variant' => 'separator',
            'upgrade_priority' => 'low',
            'new_attributes' => ['orientation'],
            'deprecated_attributes' => [],
            'dependencies' => [],
            'notes' => 'Vertical orientation support in Pro',
        ],
        'badge' => [
            'pro_variant' => 'badge',
            'upgrade_priority' => 'low',
            'new_attributes' => ['variant', 'size', 'removable'],
            'deprecated_attributes' => [],
            'dependencies' => [],
            'notes' => 'Enhanced variants and removable functionality',
        ],
        'heading' => [
            'pro_variant' => 'heading',
            'upgrade_priority' => 'low',
            'new_attributes' => ['variant'],
            'deprecated_attributes' => [],
            'dependencies' => [],
            'notes' => 'Additional styling variants',
        ],
        'text' => [
            'pro_variant' => 'text',
            'upgrade_priority' => 'low',
            'new_attributes' => ['variant', 'truncate'],
            'deprecated_attributes' => [],
            'dependencies' => [],
            'notes' => 'Enhanced text truncation and variants',
        ],
        'icon' => [
            'pro_variant' => 'icon',
            'upgrade_priority' => 'low',
            'new_attributes' => ['size', 'variant'],
            'deprecated_attributes' => [],
            'dependencies' => [],
            'notes' => 'Consistent icon sizing system',
        ],
        'avatar' => [
            'pro_variant' => 'avatar',
            'upgrade_priority' => 'low',
            'new_attributes' => ['size', 'variant'],
            'deprecated_attributes' => [],
            'dependencies' => [],
            'notes' => 'Enhanced avatar with more size options',
        ],
        'brand' => [
            'pro_variant' => 'brand',
            'upgrade_priority' => 'low',
            'new_attributes' => ['variant'],
            'deprecated_attributes' => [],
            'dependencies' => [],
            'notes' => 'Additional branding variants',
        ],
        'breadcrumbs' => [
            'pro_variant' => 'breadcrumbs',
            'upgrade_priority' => 'low',
            'new_attributes' => ['variant', 'separator'],
            'deprecated_attributes' => [],
            'dependencies' => [],
            'notes' => 'Custom separators and variants',
        ],
        'navbar' => [
            'pro_variant' => 'navbar',
            'upgrade_priority' => 'low',
            'new_attributes' => ['variant', 'sticky'],
            'deprecated_attributes' => [],
            'dependencies' => [],
            'notes' => 'Enhanced navbar with sticky positioning',
        ],
        'profile' => [
            'pro_variant' => 'profile',
            'upgrade_priority' => 'low',
            'new_attributes' => ['variant', 'size'],
            'deprecated_attributes' => [],
            'dependencies' => ['avatar'],
            'notes' => 'Enhanced profile component variants',
        ],
        'dropdown' => [
            'pro_variant' => 'dropdown',
            'upgrade_priority' => 'medium',
            'new_attributes' => ['placement', 'offset', 'trigger'],
            'deprecated_attributes' => [],
            'dependencies' => ['button'],
            'notes' => 'Enhanced positioning and trigger options',
        ],
        
        // Layout Components
        'main' => [
            'pro_variant' => 'main',
            'upgrade_priority' => 'low',
            'new_attributes' => [],
            'deprecated_attributes' => [],
            'dependencies' => [],
            'notes' => 'Layout wrapper with semantic HTML',
        ],
        
        // New Pro-only components to consider
        'accordion' => [
            'pro_variant' => 'accordion',
            'upgrade_priority' => 'medium',
            'new_attributes' => ['variant', 'multiple', 'collapsible'],
            'deprecated_attributes' => [],
            'dependencies' => [],
            'notes' => 'Pro-only component for collapsible content',
        ],
        'autocomplete' => [
            'pro_variant' => 'autocomplete',
            'upgrade_priority' => 'medium',
            'new_attributes' => ['variant', 'multiple', 'async'],
            'deprecated_attributes' => [],
            'dependencies' => ['input'],
            'notes' => 'Pro-only advanced input with autocomplete',
        ],
        'command' => [
            'pro_variant' => 'command',
            'upgrade_priority' => 'medium',
            'new_attributes' => ['variant', 'keyboard-shortcuts'],
            'deprecated_attributes' => [],
            'dependencies' => ['input'],
            'notes' => 'Pro-only command palette component',
        ],
        'date-picker' => [
            'pro_variant' => 'date-picker',
            'upgrade_priority' => 'medium',
            'new_attributes' => ['variant', 'range', 'format'],
            'deprecated_attributes' => [],
            'dependencies' => ['input', 'modal'],
            'notes' => 'Pro-only date selection component',
        ],
        'editor' => [
            'pro_variant' => 'editor',
            'upgrade_priority' => 'low',
            'new_attributes' => ['variant', 'toolbar', 'autosave'],
            'deprecated_attributes' => [],
            'dependencies' => ['textarea'],
            'notes' => 'Pro-only rich text editor',
        ],
        'toast' => [
            'pro_variant' => 'toast',
            'upgrade_priority' => 'medium',
            'new_attributes' => ['variant', 'duration', 'position'],
            'deprecated_attributes' => [],
            'dependencies' => [],
            'notes' => 'Pro-only notification system',
        ],
    ];

    /**
     * Identify Flux components in Blade content
     */
    public function identifyFluxComponents(string $bladeContent): array
    {
        $components = [];
        
        // Pattern to match flux: components (including nested components like icon.chevron-down)
        $pattern = '/<flux:([a-z-]+(?:\.[a-z-]+)?)(?:\s+([^>]*?))?(?:\s*\/\s*>|>(?:(.*?)<\/flux:\1>))/s';
        
        if (preg_match_all($pattern, $bladeContent, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $name = $match[1];
                $attributeString = $match[2] ?? '';
                $content = $match[3] ?? '';
                $isSelfClosing = str_ends_with($match[0], '/>');
                
                // Parse attributes
                $attributes = $this->parseAttributes($attributeString);
                
                // Extract base component name (remove sub-component like .chevron-down)
                $baseComponentName = explode('.', $name)[0];
                
                $components[] = [
                    'name' => $baseComponentName,
                    'full_name' => $name,
                    'attributes' => $attributes,
                    'content' => trim($content),
                    'is_self_closing' => $isSelfClosing,
                ];
            }
        }
        
        return $components;
    }

    /**
     * Parse HTML attributes from string
     */
    private function parseAttributes(string $attributeString): array
    {
        $attributes = [];
        $attributeString = trim($attributeString);
        
        if (empty($attributeString)) {
            return $attributes;
        }
        
        // Enhanced attribute parsing (handles wire:model, x-data, etc.)
        $pattern = '/([a-zA-Z:.-]+)(?:\s*=\s*["\']([^"\']*)["\'])?/';
        
        if (preg_match_all($pattern, $attributeString, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $key = $match[1];
                $value = $match[2] ?? '';
                $attributes[$key] = $value;
            }
        }
        
        return $attributes;
    }

    /**
     * Get complete component mapping
     */
    public function getComponentMapping(): array
    {
        return $this->componentMapping;
    }

    /**
     * Get upgrade priority for a component
     */
    public function getUpgradePriority(string $componentName): string
    {
        return $this->componentMapping[$componentName]['upgrade_priority'] ?? 'medium';
    }

    /**
     * Get dependencies for a component
     */
    public function getComponentDependencies(string $componentName): array
    {
        return $this->componentMapping[$componentName]['dependencies'] ?? [];
    }

    /**
     * Scan directory for Flux components using native PHP functions
     */
    public function scanDirectoryForComponents(string $directory): array
    {
        $results = [];
        
        if (!is_dir($directory)) {
            return $results;
        }
        
        // Use native PHP recursive directory iterator instead of File facade
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->getExtension() === 'php') {
                $content = file_get_contents($file->getPathname());
                $components = $this->identifyFluxComponents($content);
                
                if (!empty($components)) {
                    $results[] = [
                        'file' => $file->getPathname(),
                        'components' => $components,
                    ];
                }
            }
        }
        
        return $results;
    }

    /**
     * Generate upgrade suggestions for a component
     */
    public function generateUpgradeSuggestions(string $componentName, array $currentAttributes = []): array
    {
        $mapping = $this->componentMapping[$componentName] ?? null;
        
        if (!$mapping) {
            return [
                'recommended_attributes' => [],
                'notes' => ['Component not found in mapping'],
            ];
        }
        
        $suggestions = [
            'recommended_attributes' => [],
            'notes' => [],
        ];
        
        // Add component-specific notes
        if (!empty($mapping['notes'])) {
            $suggestions['notes'][] = $mapping['notes'];
        }
        
        // Add pro-specific attribute suggestions
        foreach ($mapping['new_attributes'] as $attribute) {
            if (!array_key_exists($attribute, $currentAttributes)) {
                switch ($attribute) {
                    case 'variant':
                        if ($componentName === 'button') {
                            if (isset($currentAttributes['type']) && $currentAttributes['type'] === 'submit') {
                                $suggestions['recommended_attributes'][] = 'variant="primary"';
                                $suggestions['notes'][] = 'Consider using variant instead of type';
                            } else {
                                $suggestions['recommended_attributes'][] = 'variant="ghost"';
                            }
                        } else {
                            $suggestions['recommended_attributes'][] = 'variant="default"';
                        }
                        break;
                    case 'size':
                        $suggestions['recommended_attributes'][] = 'size="md"';
                        break;
                    case 'loading':
                        $suggestions['recommended_attributes'][] = 'loading="false"';
                        break;
                    case 'sortable':
                        $suggestions['recommended_attributes'][] = 'sortable="true"';
                        break;
                    case 'filterable':
                        $suggestions['recommended_attributes'][] = 'filterable="true"';
                        break;
                    default:
                        $suggestions['recommended_attributes'][] = $attribute . '="default"';
                        break;
                }
            }
        }
        
        // Add general upgrade notes
        if ($mapping['upgrade_priority'] === 'high') {
            $suggestions['notes'][] = 'High priority upgrade - enhanced functionality available';
        }
        
        return $suggestions;
    }

    /**
     * Get all applications Flux components with their locations (using File facade for production)
     */
    public function auditApplicationComponents(): array
    {
        $basePath = base_path('resources/views');
        
        // Try to use File facade if available, otherwise fall back to native PHP
        try {
            $files = File::allFiles($basePath);
            $results = [];
            
            foreach ($files as $file) {
                if ($file->getExtension() === 'php') {
                    $content = file_get_contents($file->getPathname());
                    $components = $this->identifyFluxComponents($content);
                    
                    if (!empty($components)) {
                        $results[] = [
                            'file' => $file->getPathname(),
                            'components' => $components,
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            // Fallback to native PHP scanning
            $results = $this->scanDirectoryForComponents($basePath);
        }
        
        // Group by component type
        $componentsByType = [];
        $totalUsage = 0;
        
        foreach ($results as $fileResult) {
            foreach ($fileResult['components'] as $component) {
                $name = $component['name'];
                $totalUsage++;
                
                if (!isset($componentsByType[$name])) {
                    $componentsByType[$name] = [
                        'count' => 0,
                        'locations' => [],
                        'upgrade_priority' => $this->getUpgradePriority($name),
                        'upgrade_notes' => $this->componentMapping[$name]['notes'] ?? '',
                    ];
                }
                
                $componentsByType[$name]['count']++;
                $componentsByType[$name]['locations'][] = [
                    'file' => $fileResult['file'],
                    'full_name' => $component['full_name'],
                    'attributes' => $component['attributes'],
                ];
            }
        }
        
        // Sort by upgrade priority and usage count
        uasort($componentsByType, function ($a, $b) {
            $priorityOrder = ['high' => 0, 'medium' => 1, 'low' => 2];
            $aPriority = $priorityOrder[$a['upgrade_priority']] ?? 1;
            $bPriority = $priorityOrder[$b['upgrade_priority']] ?? 1;
            
            if ($aPriority === $bPriority) {
                return $b['count'] - $a['count']; // Higher usage first
            }
            
            return $aPriority - $bPriority; // Higher priority first
        });
        
        return [
            'summary' => [
                'total_components' => $totalUsage,
                'unique_component_types' => count($componentsByType),
                'files_scanned' => count($results),
                'high_priority_count' => count(array_filter($componentsByType, fn($c) => $c['upgrade_priority'] === 'high')),
                'medium_priority_count' => count(array_filter($componentsByType, fn($c) => $c['upgrade_priority'] === 'medium')),
                'low_priority_count' => count(array_filter($componentsByType, fn($c) => $c['upgrade_priority'] === 'low')),
            ],
            'components' => $componentsByType,
        ];
    }

    /**
     * Generate comprehensive upgrade report
     */
    public function generateUpgradeReport(): array
    {
        $audit = $this->auditApplicationComponents();
        $upgradeReport = [];
        
        foreach ($audit['components'] as $componentName => $data) {
            $upgradeReport[$componentName] = [
                'current_usage' => $data['count'],
                'upgrade_priority' => $data['upgrade_priority'],
                'upgrade_notes' => $data['upgrade_notes'],
                'file_count' => count($data['locations']),
                'suggestions' => [],
            ];
            
            // Generate suggestions for common attribute patterns
            $attributePatterns = [];
            foreach ($data['locations'] as $location) {
                foreach (array_keys($location['attributes']) as $attr) {
                    $attributePatterns[$attr] = ($attributePatterns[$attr] ?? 0) + 1;
                }
            }
            
            $upgradeReport[$componentName]['common_attributes'] = $attributePatterns;
            
            // Generate sample upgrade suggestion
            $sampleAttributes = !empty($data['locations']) ? $data['locations'][0]['attributes'] : [];
            $upgradeReport[$componentName]['sample_upgrade'] = $this->generateUpgradeSuggestions($componentName, $sampleAttributes);
        }
        
        return [
            'summary' => $audit['summary'],
            'components' => $upgradeReport,
        ];
    }
}