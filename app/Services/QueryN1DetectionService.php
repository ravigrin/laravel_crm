<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service to detect N+1 query problems in development
 * 
 * Usage:
 * $detector = app(QueryN1DetectionService::class);
 * $detector->start();
 * // ... your code that loads data ...
 * $detector->report();
 */
class QueryN1DetectionService
{
    private array $initialQueries = [];
    private array $queryGroups = [];
    private bool $enabled = false;

    public function __construct()
    {
        $this->enabled = config('app.debug') === true;
    }

    /**
     * Start tracking queries for N+1 detection
     */
    public function start(): void
    {
        if (!$this->enabled) {
            return;
        }

        DB::enableQueryLog();
        $this->initialQueries = [];
        $this->queryGroups = [];
    }

    /**
     * Report potential N+1 problems
     */
    public function report(): void
    {
        if (!$this->enabled) {
            return;
        }

        $queries = DB::getQueryLog();
        
        if (empty($queries)) {
            Log::info('N+1 Detection: No queries executed');
            return;
        }

        $this->analyzeQueries($queries);
        $this->logSuspiciousPatterns();
    }

    /**
     * Analyze queries for N+1 patterns
     */
    private function analyzeQueries(array $queries): void
    {
        foreach ($queries as $query) {
            // Normalize query for grouping (remove WHERE values)
            $normalized = preg_replace(
                ['/\d+/', "/'[^']*'/"],
                ['?', "'?'"],
                $query['query']
            );

            if (!isset($this->queryGroups[$normalized])) {
                $this->queryGroups[$normalized] = [];
            }

            $this->queryGroups[$normalized][] = $query;
        }
    }

    /**
     * Log suspicious patterns (potential N+1)
     */
    private function logSuspiciousPatterns(): void
    {
        foreach ($this->queryGroups as $normalized => $queries) {
            $count = count($queries);

            // Flag if same query runs 5+ times (likely N+1)
            if ($count >= 5) {
                Log::warning('Potential N+1 Query Detected', [
                    'query' => substr($normalized, 0, 100),
                    'count' => $count,
                    'examples' => array_slice($queries, 0, 3),
                ]);
            }
        }

        // Log total query count
        $total = array_sum(array_map('count', $this->queryGroups));
        Log::info("N+1 Detection Report: $total total queries in " . count($this->queryGroups) . " groups");
    }

    /**
     * Get query groups for manual inspection
     */
    public function getQueryGroups(): array
    {
        return $this->queryGroups;
    }

    /**
     * Get suspicious queries (potential N+1)
     */
    public function getSuspiciousQueries(): array
    {
        return array_filter(
            $this->queryGroups,
            fn($queries) => count($queries) >= 5
        );
    }
}
