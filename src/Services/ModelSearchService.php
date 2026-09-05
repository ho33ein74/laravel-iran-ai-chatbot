<?php
namespace Unixscript\IranAiChatbot\Services;

class ModelSearchService {
    public function searchStructured(string $query): array {
        if (!config('iran-ai-chatbot.features.direct_db_suggest', true)) return [];
        $modelsConfig = config('iran-ai-chatbot.models_search.searchable_models', []);
        if (empty($modelsConfig)) return [];

        // 🌟 کلمات توقف عمومی زبان فارسی (که در هر حوزه‌ای ثابت هستند)
        $baseStopwords = [
            'من', 'تو', 'او', 'ما', 'شما', 'آنها', 'ایشان', 'این', 'آن', 'اینها', 'یک', 'یه', 'یکسری',
            'از', 'به', 'با', 'بر', 'در', 'برای', 'تا', 'اما', 'اگر', 'چون', 'فقط', 'خیلی', 'کمی', 'روی',
            'هستم', 'هستی', 'هست', 'است', 'هستیم', 'هستید', 'هستند', 'نیستم', 'نیستی', 'نیست',
            'دارم', 'داری', 'دارد', 'داریم', 'دارید', 'دارند', 'ندارم', 'نداری', 'ندارد',
            'شد', 'شده', 'میشه', 'نمیشه', 'میکنم', 'میکنی', 'میکند', 'میکنیم', 'میکنید', 'میکنند',
            'کردم', 'کردی', 'کرد', 'کرده', 'کنم', 'کنی', 'کند', 'کنیم', 'کنید', 'کنند',
            'میخوام', 'میخوای', 'میخواهد', 'میخواهیم', 'میخواهید', 'میخواهند', 'بگو', 'باشه', 'نباشه',
            'چطور', 'چگونه', 'چطوری', 'چند', 'چنده', 'چی', 'چیه', 'چیست', 'چرا', 'کی', 'کجا', 'کدام', 'کدوم', 'آیا',
            'سلام', 'درود', 'وقت', 'بخیر', 'خداحافظ', 'مرسی', 'ممنون', 'تشکر', 'لطفا', 'بیزحمت',
            'کمک', 'مشکل', 'خراب', 'ایراد', 'خطا', 'ارور', 'سوال', 'سایت', 'ربات', 'هوش', 'مصنوعی',
            'مقاله', 'خرید', 'قیمت', 'محصول', 'کالا', 'پشتیبانی', 'ارتباط'
        ];

        // 🌟 خواندن کلمات توقف اختصاصی از فایل کانفیگ و ادغام با لیست پایه
        $customStopwords = config('iran-ai-chatbot.models_search.custom_stopwords', []);
        $stopwords = array_merge($baseStopwords, $customStopwords);

        $cleanQuery = str_replace(['?', '!', '.', '،', ':', '؛', '؟'], ' ', $query);
        $words = explode(' ', $cleanQuery);
        $searchKeywords = [];

        foreach ($words as $word) {
            $word = trim($word);
            // بررسی کلمات در لیست ادغام‌شده
            if (mb_strlen($word) >= 3 && !in_array($word, $stopwords)) {
                $searchKeywords[] = $word;
            }
        }

        if (empty($searchKeywords)) return [];

        $results = [];
        foreach ($modelsConfig as $modelClass => $config) {
            if (!class_exists($modelClass)) continue;

            $columns = $config['columns'] ?? [];
            $label = $config['label'] ?? class_basename($modelClass);

            $queryBuilder = $modelClass::query();

            // جستجوی بسیار دقیق (منطق AND)
            $queryBuilder->where(function($q) use ($columns, $searchKeywords) {
                foreach ($searchKeywords as $keyword) {
                    $q->where(function($subQ) use ($columns, $keyword) {
                        foreach ($columns as $column) {
                            $subQ->orWhere($column, 'LIKE', "%{$keyword}%");
                        }
                    });
                }
            });

            $records = $queryBuilder->limit(config('iran-ai-chatbot.models_search.max_results', 4))->get();

            foreach ($records as $record) {
                $title = $record->title ?? $record->name ?? 'مورد یافت شده';
                $urlTemplate = $config['url_template'] ?? '/';

                $slug = $record->slug ?? '';
                $parsedUrl = str_replace(['{id}', '{slug}', '{slug?}'], [$record->id, $slug, $slug], $urlTemplate);
                $finalUrl = url($parsedUrl);

                $imageUrl = null;
                if ($record->image && !empty($record->get_image['url'])) {
                    $imageUrl = $record->get_image['url'];
                }

                $results[] = [
                    'title' => $title,
                    'type'  => $label,
                    'id'    => $record->id,
                    'url'   => $finalUrl,
                    'image' => $imageUrl
                ];
            }
        }
        return $results;
    }
}