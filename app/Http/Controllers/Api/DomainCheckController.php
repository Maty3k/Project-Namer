<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NameSuggestion;
use App\Services\DomainCheckService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class DomainCheckController extends Controller
{
    /**
     * Check domains for a specific suggestion.
     */
    public function check(NameSuggestion $suggestion, DomainCheckService $domainCheckService): JsonResponse
    {
        // Authorize that the user can update this suggestion's project
        $this->authorize('update', $suggestion->project);

        // Get domains from database
        $domains = $suggestion->domains;

        if (! is_array($domains) || empty($domains)) {
            return response()->json([
                'success' => false,
                'message' => 'No domains configured for this suggestion',
                'domains' => [],
            ], 400);
        }

        // Check if domains are already checked
        $allChecked = true;
        foreach ($domains as $domainData) {
            if (! is_array($domainData) || ! isset($domainData['available'])) {
                $allChecked = false;
                break;
            }
        }

        // If already checked, return existing data
        if ($allChecked) {
            return response()->json([
                'success' => true,
                'message' => 'Domains already checked',
                'domains' => $domains,
                'already_checked' => true,
            ]);
        }

        // Extract domain names for batch processing
        $domainNames = array_keys($domains);

        // Check domains using batch service
        $checkResults = $domainCheckService->checkMultipleDomains($domainNames);

        // Build checked domains array with results
        $checkedDomains = [];
        foreach ($domains as $domainName => $domainData) {
            if (! is_array($domainData)) {
                continue;
            }

            $result = $checkResults[$domainName] ?? null;

            if ($result) {
                $checkedDomains[$domainName] = [
                    'extension' => $domainData['extension'] ?? '',
                    'available' => $result['available'],
                    'status' => $result['status'] ?? ($result['available'] ? 'available' : 'taken'),
                    'has_dns_records' => $result['has_dns_records'] ?? null,
                    'check_method' => $result['check_method'] ?? null,
                    'dns_records' => $result['dns_records'] ?? null,
                ];
            } else {
                // Keep original data if check failed
                $checkedDomains[$domainName] = $domainData;
            }
        }

        // Update the database with checked results
        $suggestion->update(['domains' => $checkedDomains]);

        Log::info('Domains checked via API', [
            'suggestion_id' => $suggestion->id,
            'domain_count' => count($checkedDomains),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Domains checked successfully',
            'domains' => $checkedDomains,
            'already_checked' => false,
        ]);
    }
}
