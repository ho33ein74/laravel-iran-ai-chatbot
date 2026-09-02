<?php
namespace Unixscript\IranAiChatbot\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AiSetting extends Model {
    protected $fillable = ['key', 'value'];
    
    public static function val($key, $default = null) {
        $dbValue = Cache::rememberForever('ai_setting_' . $key, function () use ($key) {
            $setting = self::where('key', $key)->first();
            return $setting ? $setting->value : null;
        });
        
        if ($dbValue !== null) {
            if ($dbValue === 'true' || $dbValue === '1') return true;
            if ($dbValue === 'false' || $dbValue === '0') return false;
            return $dbValue;
        }
        return config("iran-ai-chatbot.$key", $default);
    }

    protected static function booted() {
        static::saved(function ($setting) { Cache::forget('ai_setting_' . $setting->key); });
        static::deleted(function ($setting) { Cache::forget('ai_setting_' . $setting->key); });
    }
}