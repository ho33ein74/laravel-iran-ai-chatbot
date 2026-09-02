<?php
namespace Unixscript\IranAiChatbot\Services;
use Unixscript\IranAiChatbot\Models\AiSetting;

class ModelSearchService {
    public function searchStructured(string $query): array {
        if (!AiSetting::val('features.direct_db_suggest', true)) return [];
        $modelsConfig = config('iran-ai-chatbot.models_search.searchable_models', []);
        if (empty($modelsConfig)) return [];

        $stopwords = ['سلام', 'چنده', 'چیست', 'چطور', 'لطفا', 'میشه', 'بگو', 'قیمت', 'دارید', 'هست', 'میخوام', 'دارین', 'خرید', 'مقاله'];
        $cleanQuery = str_replace($stopwords, '', $query);
        $words = array_filter(explode(' ', $cleanQuery), fn($w) => mb_strlen(trim($w)) >= 3);

        if (empty($words)) return [];

        $results = [];
        foreach ($modelsConfig as $modelClass => $config) {
            if (!class_exists($modelClass)) continue;
            
            $columns = $config['columns'] ?? [];
            $label = $config['label'] ?? class_basename($modelClass);

            $queryBuilder = $modelClass::query();
            $queryBuilder->where(function($q) use ($columns, $words) {
                foreach ($columns as $column) {
                    foreach ($words as $word) {
                        $q->orWhere($column, 'LIKE', "%{$word}%");
                    }
                }
            });
            
            $records = $queryBuilder->limit(config('iran-ai-chatbot.models_search.max_results', 4))->get();

            foreach ($records as $record) {
                $title = $record->title ?? $record->name ?? 'مورد یافت شده';
                $results[] = ['title' => $title, 'type' => $label, 'id' => $record->id];
            }
        }
        return $results;
    }
}