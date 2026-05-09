<?php

namespace App\Services;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\DB;


class SearchService
{
    // Minimum characters to trigger FTS (avoid too-broad searches)
    private const MIN_SEARCH_LENGTH = 2;

    // Maximum search results without pagination
    private const MAX_RESULTS = 1000;

    /**
     * Perform full-text search on query builder
     * 
     * @param Builder $query Eloquent query builder
     * @param string $searchTerm Term to search for
     * @param string $searchColumn Column with search_vector (tsvector)
     * @param bool $orderByRelevance Whether to order by relevance score
     * @return Builder Query builder with FTS applied
     * 
     * @example
     *   $exams = SearchService::apply(
     *       Exam::query(),
     *       'midterm exam',
     *       'search_vector'
     *   )->paginate(15);
     */
    public static function apply(
        Builder $query,
        string $searchTerm,
        string $searchColumn = 'search_vector',
        bool $orderByRelevance = true
    ): Builder {
        if (empty(trim($searchTerm))) {
            return $query;
        }

        $searchTerm = trim($searchTerm);
        if (strlen($searchTerm) < self::MIN_SEARCH_LENGTH) {
            return $query;
        }

        // Apply full-text search
        $query->whereRaw(
            "{$searchColumn} @@ plainto_tsquery('english', ?)",
            [$searchTerm]
        );

        // Order by relevance (higher rank = better match)
        if ($orderByRelevance) {
            $query->orderByRaw(
                "ts_rank({$searchColumn}, plainto_tsquery('english', ?)) DESC",
                [$searchTerm]
            );
        }

        return $query;
    }

    /**
     * Search within specific columns
     * Falls back to LIKE search if FTS returns no results
     * 
     * @param Builder $query Query builder
     * @param string $searchTerm Search term
     * @param array $columns Columns to search (used for fallback)
     * @param string $searchColumn FTS column name
     * @return Builder
     * 
     * @example
     *   $questions = SearchService::searchColumns(
     *       Question::query(),
     *       'photosynthesis',
     *       ['question', 'correct_answer']
     *   )->get();
     */
    public static function searchColumns(
        Builder $query,
        string $searchTerm,
        array $columns,
        string $searchColumn = 'search_vector'
    ): Builder {
        if (empty(trim($searchTerm)) || count($columns) === 0) {
            return $query;
        }

        $searchTerm = trim($searchTerm);

        return $query->where(function ($q) use ($searchTerm, $columns, $searchColumn) {
            $q->whereRaw(
                "{$searchColumn} @@ plainto_tsquery('english', ?)",
                [$searchTerm]
            )
            ->orWhere(function ($subQ) use ($searchTerm, $columns) {
                foreach ($columns as $column) {
                    $subQ->orWhere($column, 'ilike', "%{$searchTerm}%");
                }
            });
        })
        ->orderByRaw(
            "CASE 
                WHEN {$searchColumn} @@ plainto_tsquery('english', ?) THEN 0
                ELSE 1
            END",
            [$searchTerm]
        );
    }

    /**
     * Build a complete search query with scoring
     * Useful for advanced search UI with relevance scores
     * 
     * @param Builder $query
     * @param string $searchTerm
     * @param string $searchColumn FTS column
     * @return Builder Query with relevance scores
     * 
     * @example
     *   $results = SearchService::withScores(
     *       Exam::query(),
     *       'biology'
     *   )->get(['id', 'name', 'relevance_score']);
     */
    public static function withScores(
        Builder $query,
        string $searchTerm,
        string $searchColumn = 'search_vector'
    ): Builder {
        if (empty(trim($searchTerm))) {
            return $query->select('*', DB::raw('0 as relevance_score'));
        }

        return $query
            ->select(
                '*',
                DB::raw(
                    "ts_rank({$searchColumn}, plainto_tsquery('english', ?)) as relevance_score"
                )
            )
            ->whereRaw(
                "{$searchColumn} @@ plainto_tsquery('english', ?)",
                [$searchTerm, $searchTerm]
            )
            ->orderBy('relevance_score', 'desc');
    }

    /**
     * Highlight search matches in results
     * Returns HTML with <mark> tags around matched terms
     * 
     * @param Builder $query
     * @param string $searchTerm
     * @param string $searchColumn FTS column
     * @param string $targetColumn Column to highlight
     * @return Builder Query with highlighted column
     * 
     * @example
     *   $results = SearchService::withHighlight(
     *       Exam::query(),
     *       'final',
     *       targetColumn: 'name'
     *   )->get();
     *   
     *   // Result: "Your <mark>Final</mark> Exam"
     */
    public static function withHighlight(
        Builder $query,
        string $searchTerm,
        string $searchColumn = 'search_vector',
        string $targetColumn = 'name'
    ): Builder {
        if (empty(trim($searchTerm))) {
            return $query;
        }

        return $query
            ->select(
                '*',
                DB::raw(
                    "ts_headline('english', {$targetColumn}, 
                     plainto_tsquery('english', ?),
                     'StartSel=<mark>, StopSel=</mark>, MaxWords=50') as highlighted"
                )
            )
            ->whereRaw(
                "{$searchColumn} @@ plainto_tsquery('english', ?)",
                [$searchTerm, $searchTerm]
            )
            ->orderByRaw(
                "ts_rank({$searchColumn}, plainto_tsquery('english', ?)) DESC",
                [$searchTerm]
            );
    }

    /**
     * Fuzzy search - handles typos automatically
     * Uses trigram similarity and FTS combination
     * 
     * @param Builder $query
     * @param string $searchTerm
     * @param float $threshold Similarity threshold (0-1, default 0.3)
     * @return Builder
     * 
     * @example
     *   // Will find "exam" even if user types "exma"
     *   $results = SearchService::fuzzy(
     *       Exam::query(),
     *       'exma',
     *       threshold: 0.4
     *   )->limit(10)->get();
     */
    public static function fuzzy(
        Builder $query,
        string $searchTerm,
        float $threshold = 0.3,
        string $column = 'name'
    ): Builder {
        if (empty(trim($searchTerm))) {
            return $query;
        }

        return $query
            ->where(function ($q) use ($searchTerm, $column, $threshold) {
                // Trigram similarity (typo-tolerant)
                $q->whereRaw(
                    "similarity({$column}, ?) > ?",
                    [$searchTerm, $threshold]
                );
            })
            ->orderByRaw("similarity({$column}, ?) DESC", [$searchTerm]);
    }

    /**
     * Multi-field search - search across multiple columns simultaneously
     * 
     * @param Builder $query
     * @param string $searchTerm
     * @param array $fields Field definitions: ['field_name' => 'weight', ...]
     * @return Builder
     * 
     * @example
     *   $results = SearchService::multiField(
     *       Exam::query(),
     *       'biology final',
     *       [
     *           'name' => 2,        // Weight 2x
     *           'description' => 1
     *       ]
     *   )->get();
     */
    public static function multiField(
        Builder $query,
        string $searchTerm,
        array $fields = ['name' => 2, 'description' => 1]
    ): Builder {
        if (empty(trim($searchTerm)) || empty($fields)) {
            return $query;
        }

        // Build concatenated search vector with weights
        $vectorPart = array_map(
            fn($field, $weight) => "setweight(to_tsvector('english', COALESCE({$field}, '')), '{$weight}')",
            array_keys($fields),
            array_values($fields)
        );

        $combinedVector = implode(' || ', $vectorPart);

        return $query
            ->whereRaw(
                "({$combinedVector}) @@ plainto_tsquery('english', ?)",
                [$searchTerm]
            )
            ->orderByRaw(
                "ts_rank({$combinedVector}, plainto_tsquery('english', ?)) DESC",
                [$searchTerm]
            );
    }

    /**
     * Get search suggestions/autocomplete
     * Returns unique values that match the search prefix
     * 
     * @param string $table Table name
     * @param string $column Column to get suggestions from
     * @param string $prefix Prefix to search
     * @param int $limit Max suggestions
     * @return array List of suggestions
     * 
     * @example
     *   $suggestions = SearchService::suggestions(
     *       'exams',
     *       'name',
     *       'biol'  // Returns: "Biology Final", "Biomedical Exam", etc.
     *   );
     */
    public static function suggestions(
        string $table,
        string $column,
        string $prefix,
        int $limit = 10
    ): array {
        if (strlen($prefix) < 2) {
            return [];
        }

        return DB::table($table)
            ->select(DB::raw("DISTINCT {$column}"))
            ->where($column, 'ilike', $prefix . '%')
            ->orderBy($column)
            ->limit($limit)
            ->pluck($column)
            ->toArray();
    }

    /**
     * Debug search query - see what PostgreSQL is doing
     * 
     * @param string $searchTerm
     * @return array Debug information
     * 
     * @example
     *   $debug = SearchService::debug('exam');
     *   // Returns: tsvector representation, query plan, etc.
     */
    public static function debug(string $searchTerm): array
    {
        $term = trim($searchTerm);

        return [
            'original' => $term,
            'tsquery' => DB::selectOne(
                "SELECT plainto_tsquery('english', ?) as query",
                [$term]
            )?->query,
            'tsvector' => DB::selectOne(
                "SELECT to_tsvector('english', ?) as vector",
                [$term]
            )?->vector,
            'trigram_similarity' => DB::selectOne(
                "SELECT similarity(?, 'exam') as similarity",
                [$term]
            )?->similarity,
        ];
    }
}
