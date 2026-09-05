# پکیج چت‌بات هوشمند لاراول (Laravel Iran AI Chatbot) 🤖

یک پکیج جامع، سبک و حرفه‌ای برای راه‌اندازی دستیار هوشمند و چت‌بات در پروژه‌های لاراولی. این پکیج از سرویس‌دهنده‌های مختلف (AvalAI، GapGPT، OpenAI، Gemini و مدل‌های Local)، جستجوی آفلاین در دیتابیس (RAG بومی)، گاردریل‌های امنیتی و سیستم احراز هویت هوشمند پشتیبانی می‌کند.

## ویژگی‌های کلیدی
*   🚀 **معماری بدون دیتابیس برای تنظیمات (Zero DB Settings):** تمام تنظیمات پایه‌ای، محدودیت‌ها و گاردریل‌ها از طریق فایل `.env` و `config` مدیریت می‌شوند.
*   **پشتیبانی از چندین درایور هوش مصنوعی:** AvalAI, GapGPT, OpenAI, Gemini و مدل‌های لوکال (با قابلیت انتخاب مدل داینامیک).
*   **ظاهر کاربری (UI) پیشرفته و مستقل:** کامپوننت Vue.js اختصاصی با قابلیت شخصی‌سازی کامل رنگ‌ها، متون، نحوه نمایش (پاپ‌آپ، سایدبار، تمام‌صفحه) توسط `Props` در فرانت‌اند.
*   **جستجوی مستقیم دیتابیس (Native RAG):** پیدا کردن محصولات و مقالات سایت و پیشنهاد آن‌ها به صورت **اسلایدرهای افقی و عکس‌دار (Carousel)** بدون نیاز به مصرف توکن API.
*   **امنیت و گاردریل‌ها:** فیلتر اطلاعات حساس (PII Masking)، جلوگیری از حملات Prompt Injection و سیستم Moderation.
*   **تشخیص هوشمند کاربر (Smart Auth):** مدیریت سشن‌ها برای مهمانان و اتصال تاریخچه چت به `user_id` برای کاربران لاگین شده به صورت کاملاً خودکار.

---

## ۱. نصب و راه‌اندازی

ابتدا پکیج را در پروژه خود نصب کنید:

```bash
composer require unixscript/laravel-iran-ai-chatbot

```

سپس فایل‌های پیکربندی و دارایی‌ها (Vue Components) را منتشر کرده و مایگریشن‌ها (صرفاً برای ذخیره تاریخچه چت‌ها) را اجرا کنید:

```bash
php artisan vendor:publish --provider="Unixscript\IranAiChatbot\IranAiServiceProvider"
php artisan migrate

```

---

## ۲. تنظیمات فایل `.env` (پیکربندی API و امکانات)

تمام تنظیمات حیاتی سیستم، کلیدهای API و فعال/غیرفعال کردن امکانات هوشمند مستقیماً از طریق فایل `.env` پروژه شما انجام می‌شود:

```env
# درایور پیش‌فرض (gapgpt, avalai, openai, gemini, local_offline)
IRAN_AI_DRIVER=gapgpt

# تنظیمات مربوط به مدل GapGPT
GAPGPT_API_KEY="توکن_شما"
GAPGPT_ENDPOINT="[https://api.gapgpt.app/v1/chat/completions](https://api.gapgpt.app/v1/chat/completions)"
GAPGPT_MODEL="gpt-4o-mini"

# تنظیمات امنیتی، هوش مصنوعی و گاردریل‌ها
IRAN_AI_RAG_ENABLED=true
IRAN_AI_MODELS_SEARCH=true
IRAN_AI_PII_MASKING=true
IRAN_AI_PROMPT_INJECTION=true
IRAN_AI_MODERATION=true

# تنظیمات محدودیت کاربری و لاگین
IRAN_AI_AUTH_REQUIRED=false
IRAN_AI_QUOTA_ENABLED=true
IRAN_AI_MAX_QUESTIONS=20

```

---

## ۳. اتصال به دیتابیس (پیشنهاد محصولات به صورت کارت‌های لینک‌دار)

برای اینکه ربات بتواند در محصولات شما جستجو کند و نتایج را به صورت کارت‌های قابل کلیک نمایش دهد، فایل `config/iran-ai-chatbot.php` را باز کنید و بخش `searchable_models` را مقداردهی کنید:

```php
'models_search' => [
    'max_results' => env('IRAN_AI_SEARCH_LIMIT', 4),
    'searchable_models' => [
        \App\Models\Product::class => [
            'columns' => ['title', 'description'], // ستون‌هایی که باید سرچ شوند
            'label' => 'محصول', // نامی که به عنوان بج (Badge) روی کارت نمایش داده می‌شود
            'url_template' => '/products/{id}/{slug}' // الگوی لینک محصول برای باز شدن در تب جدید
        ],
    ]
],

```

---

## ۴. نحوه استفاده در فرانت‌اند (Vue 3) و شخصی‌سازی ظاهر

ویجت چت‌بات به عنوان یک کامپوننت Vue طراحی شده است. تمام ظاهر، متون و پیام‌ها **مستقیماً از طریق پراپ‌ها (Props)** قابل شخصی‌سازی است. تنظیماتی که در فایل کانفیگ به عنوان `ui` قرار دارند، صرفاً مقادیر پیش‌فرض (Fallback) هستند.

کامپوننت را در فایل `app.js` رجیستر کنید:

```javascript
import ChatWidget from './vendor/iran-ai-chatbot/ChatWidget.vue';
app.component('chat-widget', ChatWidget);

```

### نمونه استفاده کامل و شخصی‌سازی شده در فایل Blade:

شما می‌توانید بی‌نهایت چت‌بات با رنگ‌ها و پیام‌های مختلف در صفحات مختلف سایت خود داشته باشید:

```html
<chat-widget
    color="#e11d48"
    title="پشتیبانی ویژه"
    position="left"
    display-mode="popup"
    :inline="false"
    
    initial-message="سلام دوست من! 👋 من دستیار هوشمند فروشگاه هستم. دنبال چه محصولی می‌گردی؟"
    placeholder-text="نام محصول یا سوالت رو اینجا بنویس..."
    
    login-text="کاربر عزیز، لطفا برای پرسیدن سوال ابتدا"
    login-link-text="وارد حساب کاربری خود شوید."
    login-url="/panel/login"
    
    error-text="ارتباط با سرور قطع شد، لطفا چند دقیقه دیگر تلاش کنید."
    rate-limit-text="تعداد پیام‌های شما امروز به اتمام رسیده است."
></chat-widget>

```

### لیست کامل پراپ‌های (Props) ظاهری:

| نام پراپ | نوع | پیش‌فرض | توضیحات |
| --- | --- | --- | --- |
| `color` | String | (خوانده شده از کانفیگ) | رنگ اصلی چت‌بات (هدر، دکمه‌ها و...) |
| `title` | String | (خوانده شده از کانفیگ) | عنوان بالای چت‌بات |
| `position` | String | `right` | موقعیت ویجت شناور (`left` یا `right`) |
| `display-mode` | String | `popup` | نحوه نمایش چت‌بات (`popup`, `sidebar`, `fullscreen`) |
| `inline` | Boolean | `false` | نمایش درون‌خطی و ثابت به جای ویجت شناور |
| `initial-message` | String | (پیام سلام) | پیامی که ربات در ابتدای باز شدن نمایش می‌دهد |
| `placeholder-text` | String | `پیام خود را بنویسید...` | متن پس‌زمینه فیلد ورودی |
| `login-url` | String | `/login` | آدرسی که کاربر در صورت نیاز به لاگین هدایت می‌شود |

---

## ۵. مدیریت کاربران (Smart Auth)

سیستم به صورت کاملاً هوشمند با `Auth` لاراول درگیر می‌شود:

* **مدیریت مهمانان:** اگر `auth_required` برابر با `false` باشد، مکالمات مهمانان با استفاده از شناسه‌های امن کوکی مدیریت و ذخیره می‌شوند.
* **مدیریت کاربران لاگین شده:** در صورت تشخیص لاگین بودن کاربر، پیام‌ها مستقیماً با `user_id` آن‌ها ذخیره می‌شود تا در دستگاه‌های دیگر نیز به تاریخچه خود دسترسی داشته باشند.
