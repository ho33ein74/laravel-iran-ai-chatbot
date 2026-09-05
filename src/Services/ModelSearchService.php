<?php
namespace Unixscript\IranAiChatbot\Services;

class ModelSearchService {
    public function searchStructured(string $query): array {
        if (!config('iran-ai-chatbot.features.direct_db_suggest', true)) return [];
        $modelsConfig = config('iran-ai-chatbot.models_search.searchable_models', []);
        if (empty($modelsConfig)) return [];

        // 🌟 لیست بسیار جامع کلمات توقف (Stopwords) برای جلوگیری از سرچ بی‌مورد
        $stopwords = [
            // ضمایر و کلمات اشاره
            'من', 'تو', 'او', 'ما', 'شما', 'آنها', 'ایشان', 'این', 'آن', 'اینها', 'یک', 'یه', 'یکسری',
            'از', 'به', 'با', 'بر', 'در', 'برای', 'تا', 'اما', 'اگر', 'چون', 'فقط', 'خیلی', 'کمی',

            // افعال ربطی و پرکاربرد
            'هستم', 'هستی', 'هست', 'است', 'هستیم', 'هستید', 'هستند', 'نیستم', 'نیستی', 'نیست',
            'دارم', 'داری', 'دارد', 'داریم', 'دارید', 'دارند', 'ندارم', 'نداری', 'ندارد',
            'شد', 'شده', 'میشه', 'نمیشه', 'میکنم', 'میکنی', 'میکند', 'میکنیم', 'میکنید', 'میکنند',
            'کردم', 'کردی', 'کرد', 'کرده', 'کنم', 'کنی', 'کند', 'کنیم', 'کنید', 'کنند',
            'میخوام', 'میخوای', 'میخواهد', 'میخواهیم', 'میخواهید', 'میخواهند', 'بگو', 'باشه', 'نباشه',

            // کلمات پرسشی
            'چطور', 'چگونه', 'چطوری', 'چند', 'چنده', 'چی', 'چیه', 'چیست', 'چرا', 'کی', 'کجا', 'کدام', 'کدوم', 'آیا',

            // کلمات تعارفی و پشتیبانی که نباید محصولی براشون سرچ بشه
            'سلام', 'درود', 'وقت', 'بخیر', 'خداحافظ', 'مرسی', 'ممنون', 'تشکر', 'لطفا', 'بیزحمت',
            'کمک', 'مشکل', 'خراب', 'ایراد', 'خطا', 'ارور', 'سوال', 'سایت', 'ربات', 'هوش', 'مصنوعی',
            'مقاله', 'خرید', 'قیمت', 'محصول', 'کالا', 'پشتیبانی', 'ارتباط'
        ];

        // ۱. اول علائم نگارشی رو از جمله حذف می‌کنیم
        $cleanQuery = str_replace(['?', '!', '.', '،', ':', '؛', '؟'], ' ', $query);
        $words = explode(' ', $cleanQuery);
        $searchKeywords = [];

        // ۲. کلمات رو فیلتر می‌کنیم (هم بزرگتر از ۳ حرف باشن و هم تو لیست بالا نباشن)
        foreach ($words as $word) {
            $word = trim($word);
            if (mb_strlen($word) >= 3 && !in_array($word, $stopwords)) {
                $searchKeywords[] = $word;
            }
        }

        // ۳. اگر هیچ کلمه کلیدی مفیدی (مثل اسم برند یا محصول) باقی نموند، سرچ رو متوقف کن
        if (empty($searchKeywords)) return [];

        $results = [];
        foreach ($modelsConfig as $modelClass => $config) {
            if (!class_exists($modelClass)) continue;

            $columns = $config['columns'] ?? [];
            $label = $config['label'] ?? class_basename($modelClass);

            $queryBuilder = $modelClass::query();
            $queryBuilder->where(function($q) use ($columns, $searchKeywords) {
                foreach ($columns as $column) {
                    foreach ($searchKeywords as $keyword) {
                        $q->orWhere($column, 'LIKE', "%{$keyword}%");
                    }
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
