<?php

declare(strict_types=1);

namespace Tests\Support;

use Prism\Prism\Testing\TextResponseFake;

/**
 * Centralized AI test fixtures for consistent mocking across tests.
 */
class AITestFixtures
{
    /**
     * Standard creative name generation response.
     */
    public static function creativeNamesResponse(): string
    {
        return "1. CreativeFlow\n2. InnovateLab\n3. BrightSpark\n4. FlowForge\n5. NextLevel\n6. ThinkTank\n7. LaunchPad\n8. StreamLine\n9. VisionCraft\n10. IdeaForge";
    }

    /**
     * Standard professional name generation response.
     */
    public static function professionalNamesResponse(): string
    {
        return "1. ProfessionalCorp\n2. BusinessSolutions\n3. CorporateEdge\n4. ExecutiveFlow\n5. EnterpriseHub\n6. StrategicCore\n7. ProVision\n8. BusinessCraft\n9. CorporateForge\n10. ExecutiveLab";
    }

    /**
     * Standard tech-focused name generation response.
     */
    public static function techNamesResponse(): string
    {
        return "1. TechFlow\n2. DataSync\n3. CloudCore\n4. AppForge\n5. CodeCraft\n6. ByteBridge\n7. WebWorks\n8. NetNinja\n9. PixelPro\n10. DevDesk";
    }

    /**
     * Standard brandable name generation response.
     */
    public static function brandableNamesResponse(): string
    {
        return "1. BrandFlow\n2. MarketSync\n3. TradeCraft\n4. BrandForge\n5. LogoLab\n6. BrandCore\n7. MarketFlow\n8. TradeTech\n9. BrandCraft\n10. LogoForge";
    }

    /**
     * Short response for testing with fewer names.
     */
    public static function shortNamesResponse(): string
    {
        return "1. ShortName\n2. QuickBrand\n3. FastTech";
    }

    /**
     * Multi-model responses for parallel testing.
     */
    public static function multiModelResponses(): array
    {
        return [
            TextResponseFake::make()->withText(self::techNamesResponse()),
            TextResponseFake::make()->withText(self::professionalNamesResponse()),
            TextResponseFake::make()->withText(self::creativeNamesResponse()),
            TextResponseFake::make()->withText(self::brandableNamesResponse()),
        ];
    }

    /**
     * Empty response array for simulating API failures.
     */
    public static function emptyResponse(): array
    {
        return [];
    }

    /**
     * Single response fake for basic testing.
     */
    public static function singleResponse(?string $content = null): array
    {
        return [
            TextResponseFake::make()->withText($content ?? self::creativeNamesResponse()),
        ];
    }

    /**
     * Malformed response for error testing.
     */
    public static function malformedResponse(): array
    {
        return [
            TextResponseFake::make()->withText('This is not a proper numbered list format'),
        ];
    }

    /**
     * Get specific response by type.
     */
    public static function getResponseByType(string $type): string
    {
        return match ($type) {
            'creative' => self::creativeNamesResponse(),
            'professional' => self::professionalNamesResponse(),
            'tech-focused' => self::techNamesResponse(),
            'brandable' => self::brandableNamesResponse(),
            'short' => self::shortNamesResponse(),
            default => self::creativeNamesResponse(),
        };
    }

    /**
     * Get Prism fake response array by type.
     */
    public static function getPrismFakeByType(string $type): array
    {
        return [
            TextResponseFake::make()->withText(self::getResponseByType($type)),
        ];
    }
}
