<?php

declare(strict_types=1);

namespace App\Contracts;

interface DnsHealthAlertServiceInterface
{
    /**
     * Send an alert for a specific metric.
     *
     * @param  array<string, mixed>  $alertData
     */
    public function sendAlert(string $metric, array $alertData): void;

    /**
     * Clear all alert suppressions.
     */
    public function clearSuppressions(): void;

    /**
     * Test the webhook configuration.
     *
     * @return array<string, mixed>
     */
    public function testWebhook(): array;

    /**
     * Check DNS health and return status.
     *
     * @return array<string, mixed>
     */
    public function checkDnsHealth(): array;

    /**
     * Get alert configuration.
     *
     * @return array<string, mixed>
     */
    public function getAlertConfig(): array;

    /**
     * Check and trigger alerts based on current metrics.
     */
    public function checkAndTriggerAlerts(): void;

    /**
     * Send a test alert to verify configuration.
     *
     * @return array<string, mixed>
     */
    public function sendTestAlert(): array;

    /**
     * Get alert history.
     *
     * @return array<string, mixed>
     */
    public function getAlertHistory(): array;

    /**
     * Clear alert history.
     */
    public function clearAlertHistory(): void;
}
