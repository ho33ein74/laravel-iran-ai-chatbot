فایل `README.md` جامع و نهایی که ساختار اصلی شما (شامل مقادیر `sidebar`، `fullscreen` و چیدمان‌ها) را با امکانات جدید نسخه آخر (پراپ‌های Vue، همگام‌سازی فایل Env و رفع ارور گارد) یکپارچه کرده است. این متن را کپی و جایگزین کنید:

```markdown
# پکیج چت‌بات هوشمند لاراول (Laravel Iran AI Chatbot) 🤖

یک پکیج جامع و حرفه‌ای برای راه‌اندازی دستیار هوشمند و چت‌بات در پروژه‌های لاراول. این پکیج از سرویس‌دهنده‌های مختلف (AvalAI، GapGPT، OpenAI، Gemini و مدل‌های Local)، جستجوی آفلاین در دیتابیس (RAG بومی)، گاردریل‌های امنیتی و اتصال به پیام‌رسان‌ها پشتیبانی می‌کند.

## ویژگی‌های کلیدی
*   **پشتیبانی از چندین درایور هوش مصنوعی:** AvalAI, GapGPT, OpenAI, Gemini و مدل‌های لوکال (با قابلیت انتخاب مدل داینامیک).
*   **همگام‌سازی کامل Env و دیتابیس:** تمام تنظیمات هم از فایل `.env` و هم از جدول `ai_settings` (با اولویت دیتابیس برای تغییرات در لحظه) خوانده می‌شوند.
*   **ظاهر کاربری (UI) پیشرفته و کاملاً مستقل (Pure CSS):** کامپوننت Vue.js اختصاصی با قابلیت شخصی‌سازی نحوه نمایش (پاپ‌آپ، سایدبار، تمام‌صفحه) و چیدمان (حبابی، زیرهم) توسط ادمین سایت یا برنامه‌نویس.
*   **جستجوی مستقیم دیتابیس (Native RAG):** پیدا کردن محصولات و مقالات سایت و پیشنهاد آن‌ها به کاربر بدون نیاز به مصرف توکن API.
*   **تشخیص هوشمند کاربر (Smart Auth):** شناسایی خودکار گاردهای فعال (بدون ارور `Guard not defined`)، مدیریت سشن‌ها برای مهمانان و اتصال تاریخچه چت به `user_id` برای کاربران لاگین شده.

---

## ۱. نصب و راه‌اندازی

ابتدا پکیج را در پروژه خود نصب کنید:

```bash
composer require unixscript/laravel-iran-ai-chatbot

```

سپس تنظیمات، کامپوننت‌های Vue و فایل‌های مورد نیاز را منتشر کرده و مایگریشن‌ها را اجرا کنید:

```bash
php artisan vendor:publish --tag=iran-ai-chatbot-assets
php artisan migrate

```

---

## ۲. تنظیمات فایل `.env`

پس از پابلیش، فایلی به نام `.env.chatbot.example` ساخته می‌شود. برای پیکربندی سریع، متغیرهای اصلی را به فایل `.env` پروژه خود اضافه کنید:

```env
IRAN_AI_DRIVER=gapgpt

# تنظیمات GapGPT
GAPGPT_API_KEY="توکن_شما"
GAPGPT_MODEL="gpt-4o-mini"
GAPGPT_ENDPOINT="[https://api.gapgpt.app/v1/chat/completions](https://api.gapgpt.app/v1/chat/completions)"

# تنظیمات رفتاری
IRAN_AI_AUTH_REQUIRED=false
IRAN_AI_RAG_ENABLED=true
IRAN_AI_MODELS_SEARCH=true

# تنظیمات ظاهری پیش‌فرض
IRAN_AI_UI_COLOR="#1a56db"
IRAN_AI_BOT_NAME="دستیار هوشمند"
IRAN_AI_DISPLAY_MODE="popup" 
IRAN_AI_LAYOUT="bubble"

```

### اتصال به دیتابیس (پیشنهاد محصولات)

برای اینکه ربات بتواند در محصولات شما جستجو کند، فایل `config/iran-ai-chatbot.php` را باز کنید و بخش `searchable_models` را به شکل زیر مقداردهی کنید:

```php
'models_search' => [
    'max_results' => env('IRAN_AI_SEARCH_LIMIT', 4),
    'searchable_models' => [
        \App\Models\Product::class => [
            'columns' => ['title', 'description'], // ستون‌هایی که باید در آن‌ها سرچ شود
            'label' => 'محصول' // نامی که به کاربر نمایش داده می‌شود
        ],
    ]
],

```

---

## ۳. نحوه استفاده در ظاهر سایت (فرانت‌اند)

این پکیج یک کامپوننت `Vue.js` قدرتمند ارائه می‌دهد. فایل `resources/js/vendor/iran-ai-chatbot/ChatWidget.vue` را در `app.js` رجیستر کنید:

```javascript
import ChatWidget from './vendor/iran-ai-chatbot/ChatWidget.vue';
app.component('chat-widget', ChatWidget);

```

### حالت اول: استفاده از تنظیمات سراسری (ادمین)

با قرار دادن تگ زیر در قالب سایت، چت‌بات تمام تنظیمات رنگ، نام و حالت نمایش را مستقیماً از دیتابیس یا فایل کانفیگ دریافت می‌کند:

```html
<chat-widget></chat-widget>

```

### حالت دوم: شخصی‌سازی مستقیم توسط برنامه‌نویس (Custom Props)

اگر می‌خواهید چت‌بات را در یک برگه (مثلاً "پشتیبانی ویژه") با ظاهری متفاوت، به صورت ثابت (Inline) و مستقل از تنظیمات ادمین نمایش دهید:

```html
<div class="col-md-8 mx-auto">
    <chat-widget 
        color="#ff0000" 
        title="پشتیبانی ویژه" 
        position="left"
        :inline="true">
    </chat-widget>
</div>

```

---

## ۴. مدیریت استایل و رفتار توسط ادمین (Dynamic Settings)

ادمین سایت می‌تواند با درج رکوردهای زیر در جدول `ai_settings` (مثلاً از طریق پنل مدیریت)، رفتار و ظاهر بات را برای تمام کاربران تغییر دهد. مقادیر دیتابیس همواره اولویت بالاتری نسبت به فایل `.env` دارند:

* **اجباری کردن لاگین:**
* `key`: `features.auth_required` | `value`: `true` یا `false`


* **تغییر حالت نمایش ویجت:**
* `key`: `ui.default_display_mode` | `value`: `popup` یا `sidebar` یا `fullscreen`


* **تغییر چیدمان پیام‌ها:**
* `key`: `ui.default_layout` | `value`: `bubble` (حبابی) یا `stacked` (زیرهم)


* **تغییر نام ربات:**
* `key`: `ui.bot_name` | `value`: `پشتیبان آنلاین من`


* **تغییر رنگ سازمانی:**
* `key`: `ui.primary_color` | `value`: `#10b981`


* **تغییر درایور هوش مصنوعی:**
* `key`: `default_driver` | `value`: `avalai` یا `gapgpt` یا `openai` یا `gemini` یا `local_offline`



---

## ۵. مدیریت کاربران (Auth)

سیستم به صورت خودکار با سیستم Auth لاراول یکپارچه است:

* **مدیریت مهمانان:** اگر لاگین اجباری نباشد و کاربر مهمان باشد، چت‌ها با کوکی `session_id` مدیریت و بازیابی می‌شوند.
* **مدیریت کاربران تایید شده:** اگر کاربر لاگین باشد (از طریق گاردهای `web`, `api` یا `sanctum`)، پیام‌ها با `user_id` اختصاصی او در دیتابیس ثبت می‌شوند تا تاریخچه مکالمات همواره در دسترس باشد.
