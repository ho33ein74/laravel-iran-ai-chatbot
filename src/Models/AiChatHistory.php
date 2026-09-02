<?php
namespace Unixscript\IranAiChatbot\Models;
use Illuminate\Database\Eloquent\Model;
class AiChatHistory extends Model {
    protected $fillable = ['user_id', 'session_id', 'user_message', 'bot_reply', 'requires_admin', 'admin_replied_at'];
}