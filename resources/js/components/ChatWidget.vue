<template>
  <div class="porsyar-ai-wrapper" :style="cssVars" dir="rtl">
    <button v-if="!isOpen && !inline" @click="isOpen = true" :class="['porsyar-fab', `pos-${position}`]">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
    </button>
    <div v-show="isOpen || inline" :class="['porsyar-chat-container', inline ? 'porsyar-inline' : displayModeClass, `pos-${position}`]">
      <div class="porsyar-header">
        <div class="p-header-info">
          <div class="p-avatar">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
          </div>
          <div class="p-title-box">
            <h3 class="p-title">{{ finalBotName }}</h3>
            <p class="p-subtitle"><span class="p-dot"></span> آنلاین و آماده پاسخگویی</p>
          </div>
        </div>
        <div class="p-header-actions">
          <button @click="clearChat" title="پاک کردن تاریخچه"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg></button>
          <button v-if="!inline" @click="isOpen = false"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"></path></svg></button>
        </div>
      </div>
      <div v-if="needsLoginNotice" class="porsyar-login-notice">
        برای استفاده از این بخش باید ابتدا <a href="/login">وارد سایت</a> شوید.
      </div>
      <div class="porsyar-body" ref="chatContainer">
        <div v-if="messages.length === 0" class="porsyar-empty">
          <p>سلام! 👋 به گفتگوی هوشمند خوش آمدید. سوالی دارید از من بپرسید!</p>
        </div>
        <div v-for="(msg, index) in messages" :key="index" :class="['porsyar-msg-wrapper', msg.isUser ? 'user' : 'bot']">
          <div class="porsyar-bubble">
            <span v-if="!msg.isUser && msg.isTyping" class="p-typing-cursor">{{ msg.displayedText }}</span>
            <span v-else v-html="formatText(msg.text)"></span>
            <div class="p-time">{{ msg.time }}</div>
          </div>
          <div v-if="msg.suggestions && msg.suggestions.length > 0" class="porsyar-suggestions">
            <a v-for="sug in msg.suggestions" :key="sug.id" href="#" class="p-suggestion-item">
              <span class="p-sug-title">{{ sug.title }}</span>
              <span class="p-sug-badge">{{ sug.type }}</span>
            </a>
          </div>
        </div>
        <div v-if="isLoading" class="porsyar-msg-wrapper bot">
          <div class="porsyar-bubble typing-dots"><span></span><span></span><span></span></div>
        </div>
      </div>
      <div class="porsyar-footer">
        <button class="p-mic-btn" title="ارسال صدا" @click="alertFunc"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a3 3 0 00-3 3v7a3 3 0 006 0V5a3 3 0 00-3-3z"></path><path d="M19 10v2a7 7 0 01-14 0v-2M12 18.4v3.3M8 22h8"></path></svg></button>
        <input v-model="input" @keyup.enter="sendMessage" type="text" :disabled="needsLoginNotice" class="p-input" placeholder="پیام خود را بنویسید...">
        <button @click="sendMessage" :disabled="!input.trim() || needsLoginNotice" :class="['p-send-btn', (input.trim() && !needsLoginNotice) ? 'active' : '']"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg></button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick, computed } from 'vue';
import axios from 'axios';

// فعال‌سازی ارسال کوکی و سشن‌های وب برای حل مشکل لاگین
axios.defaults.withCredentials = true;

const props = defineProps({
  apiEndpoint: { type: String, default: '/api/ai-chatbot' },
  inline: { type: Boolean, default: false },
  color: { type: String, default: '' },
  title: { type: String, default: '' },
  position: { type: String, default: 'right' }
});

const isOpen = ref(false);
const isLoading = ref(false);
const input = ref('');
const chatContainer = ref(null);
const messages = ref([]);
const needsLoginNotice = ref(false);
const displayMode = ref('popup');

const apiSettings = ref({ bot_name: 'دستیار هوشمند', primary_color: '#1a56db' });

const cssVars = computed(() => { return { '--primary-color': props.color || apiSettings.value.primary_color }; });
const finalBotName = computed(() => props.title || apiSettings.value.bot_name);

const displayModeClass = computed(() => {
  if (displayMode.value === 'sidebar') return 'porsyar-sidebar';
  if (displayMode.value === 'fullscreen') return 'porsyar-fullscreen';
  return 'porsyar-popup';
});

const alertFunc = () => alert('سرویس پردازش صدا به زودی فعال می‌شود.');

onMounted(async () => {
  try {
    const res = await axios.get(`${props.apiEndpoint}/settings`);
    if(res.data.bot_name) apiSettings.value.bot_name = res.data.bot_name;
    if(res.data.primary_color) apiSettings.value.primary_color = res.data.primary_color;
    if(res.data.display_mode) displayMode.value = res.data.display_mode;
    if(res.data.auth_required && !res.data.is_logged_in) needsLoginNotice.value = true;
  } catch(e) {}
  messages.value.push({ text: '👋 سلام! به گفتگوی هوشمند خوش آمدید.', isUser: false, time: getCurrentTime(), isTyping: false, displayedText: '👋 سلام! به گفتگوی هوشمند خوش آمدید.' });
});

const getCurrentTime = () => { const d = new Date(); return d.getHours() + ':' + (d.getMinutes() < 10 ? '0' : '') + d.getMinutes(); };
const scrollToBottom = async () => { await nextTick(); if (chatContainer.value) chatContainer.value.scrollTop = chatContainer.value.scrollHeight; };
const formatText = (text) => text ? text.replace(/\n/g, '<br>') : '';
const clearChat = async () => {
  if(!confirm('آیا از پاک کردن تاریخچه گفتگو مطمئن هستید؟')) return;
  try {
    await axios.post(`${props.apiEndpoint}/clear`);
    messages.value = [{ text: 'تاریخچه پاک شد.', isUser: false, time: getCurrentTime(), isTyping: false, displayedText: 'تاریخچه پاک شد.' }];
  } catch(e) {}
};
const typeText = async (msgObj) => {
  msgObj.isTyping = true;
  msgObj.displayedText = '';
  const fullText = msgObj.text;
  for (let i = 0; i < fullText.length; i++) {
    msgObj.displayedText += fullText.charAt(i);
    await new Promise(resolve => setTimeout(resolve, 15));
    if(i % 5 === 0) scrollToBottom();
  }
  msgObj.isTyping = false;
  msgObj.displayedText = fullText;
};
const sendMessage = async () => {
  if (!input.value.trim() || needsLoginNotice.value) return;
  const userMsg = input.value;
  messages.value.push({ text: userMsg, isUser: true, time: getCurrentTime() });
  input.value = '';
  isLoading.value = true;
  if (!props.inline && !isOpen.value) isOpen.value = true;
  scrollToBottom();

  try {
    const response = await axios.post(`${props.apiEndpoint}/chat`, { message: userMsg });
    const botMsg = { text: response.data.reply, isUser: false, time: getCurrentTime(), suggestions: response.data.suggestions || [] };
    messages.value.push(botMsg);
    typeText(botMsg);
  } catch (error) {
    if(error.response && error.response.status === 401) {
      messages.value.push({ text: 'برای ادامه باید وارد شوید.', isUser: false, time: getCurrentTime() });
      needsLoginNotice.value = true;
    } else if (error.response && error.response.status === 429) {
      messages.value.push({ text: 'سقف مجاز روزانه پر شده است.', isUser: false, time: getCurrentTime() });
    } else {
      messages.value.push({ text: 'خطا در ارتباط.', isUser: false, time: getCurrentTime() });
    }
  } finally {
    isLoading.value = false;
    scrollToBottom();
  }
};
</script>

<style scoped>
.porsyar-ai-wrapper { font-family: Tahoma, "IranSans", "Segoe UI", sans-serif; box-sizing: border-box; }
.porsyar-ai-wrapper * { box-sizing: inherit; }
.porsyar-fab { position: fixed; bottom: 24px; z-index: 9999; width: 60px; height: 60px; border-radius: 50%; background-color: var(--primary-color); color: white; border: none; cursor: pointer; box-shadow: 0 4px 12px rgba(0,0,0,0.2); display: flex; justify-content: center; align-items: center; transition: transform 0.2s; }
.porsyar-fab.pos-right { right: 24px; } .porsyar-fab.pos-left { left: 24px; }
.porsyar-fab:hover { transform: scale(1.05); } .porsyar-fab svg { width: 30px; height: 30px; }
.porsyar-chat-container { background: #f9fafb; display: flex; flex-direction: column; overflow: hidden; }

/* حالت پاپ‌آپ پیش‌فرض */
.porsyar-popup { position: fixed; bottom: 95px; z-index: 9999; width: 380px; height: 550px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); border: 1px solid #e5e7eb; }
.porsyar-popup.pos-right { right: 24px; } .porsyar-popup.pos-left { left: 24px; }

/* حالت سایدبار جدید */
.porsyar-sidebar { position: fixed; bottom: 0; top: 0; z-index: 9999; width: 400px; height: 100vh; border-radius: 0; box-shadow: -5px 0 25px rgba(0,0,0,0.15); border: none; }
.porsyar-sidebar.pos-right { right: 0; } .porsyar-sidebar.pos-left { left: 0; }

/* حالت تمام صفحه جدید */
.porsyar-fullscreen { position: fixed; bottom: 0; right: 0; top: 0; left: 0; z-index: 9999; width: 100vw; height: 100vh; border-radius: 0; border: none; }

.porsyar-inline { width: 100%; height: 600px; border-radius: 16px; border: 1px solid #e5e7eb; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
@media (max-width: 480px) {
  .porsyar-popup { width: 90%; bottom: 85px; height: 500px; }
  .porsyar-popup.pos-right { right: 5%; }
  .porsyar-popup.pos-left { left: 5%; }
  .porsyar-sidebar { width: 100vw; }
}
.porsyar-header { display: flex; justify-content: space-between; align-items: center; padding: 16px; color: white; background-color: var(--primary-color); }
.p-header-info { display: flex; align-items: center; gap: 12px; }
.p-avatar { width: 42px; height: 42px; background: white; border-radius: 50%; display: flex; justify-content: center; align-items: center; color: var(--primary-color); }
.p-avatar svg { width: 24px; height: 24px; }
.p-title-box { display: flex; flex-direction: column; } .p-title { margin: 0; font-size: 16px; font-weight: bold; }
.p-subtitle { margin: 4px 0 0 0; font-size: 11px; opacity: 0.9; display: flex; align-items: center; gap: 4px; }
.p-dot { width: 8px; height: 8px; background: #4ade80; border-radius: 50%; display: inline-block; animation: p-pulse 2s infinite; }
.p-header-actions button { background: transparent; border: none; color: white; cursor: pointer; padding: 4px; border-radius: 4px; display: inline-flex; }
.p-header-actions button:hover { background: rgba(255,255,255,0.2); } .p-header-actions svg { width: 20px; height: 20px; }
.porsyar-body { flex: 1; padding: 16px; overflow-y: auto; display: flex; flex-direction: column; gap: 16px; background: #f3f4f6; }
.porsyar-empty { text-align: center; color: #6b7280; margin-top: auto; margin-bottom: auto; font-size: 14px; line-height: 1.6; }
.porsyar-msg-wrapper { display: flex; flex-direction: column; width: 100%; }
.porsyar-msg-wrapper.user { align-items: flex-end; } .porsyar-msg-wrapper.bot { align-items: flex-start; }
.porsyar-bubble { max-width: 85%; padding: 12px 16px; border-radius: 16px; font-size: 14px; line-height: 1.6; position: relative; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
.porsyar-msg-wrapper.user .porsyar-bubble { background: #dbeafe; color: #1e3a8a; border-bottom-right-radius: 4px; }
.porsyar-msg-wrapper.bot .porsyar-bubble { background: #ffffff; color: #1f2937; border-bottom-left-radius: 4px; border: 1px solid #e5e7eb; }
.p-time { font-size: 10px; margin-top: 6px; opacity: 0.6; text-align: left; }
.p-typing-cursor { border-left: 2px solid #9ca3af; padding-left: 4px; animation: p-blink 1s step-end infinite; }
.porsyar-suggestions { margin-top: 8px; width: 85%; display: flex; flex-direction: column; gap: 8px; }
.p-suggestion-item { display: flex; justify-content: space-between; align-items: center; background: white; border: 1px solid #bfdbfe; padding: 10px 12px; border-radius: 12px; text-decoration: none; color: inherit; transition: all 0.2s; }
.p-suggestion-item:hover { border-color: var(--primary-color); box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
.p-sug-title { font-size: 13px; font-weight: bold; color: #374151; } .p-sug-badge { font-size: 10px; background: #eff6ff; color: var(--primary-color); padding: 2px 8px; border-radius: 6px; }
.porsyar-footer { padding: 12px; background: white; border-top: 1px solid #e5e7eb; display: flex; align-items: center; gap: 8px; }
.p-input { flex: 1; border: 2px solid #e5e7eb; border-radius: 24px; padding: 10px 16px; font-size: 13px; font-family: inherit; outline: none; transition: border-color 0.2s; background: #f9fafb; }
.p-input:focus { border-color: var(--primary-color); background: white; } .p-input:disabled { background: #f3f4f6; cursor: not-allowed; }
.p-mic-btn { background: #eff6ff; color: var(--primary-color); border: none; border-radius: 50%; width: 38px; height: 38px; display: flex; justify-content: center; align-items: center; cursor: pointer; }
.p-send-btn { background: #f3f4f6; color: #9ca3af; border: none; border-radius: 50%; width: 40px; height: 40px; display: flex; justify-content: center; align-items: center; cursor: not-allowed; transition: all 0.2s; }
.p-send-btn.active { background-color: var(--primary-color); color: white; cursor: pointer; } .p-send-btn svg { width: 18px; height: 18px; }
.porsyar-login-notice { background: #fefce8; color: #854d0e; padding: 10px; text-align: center; font-size: 12px; border-bottom: 1px solid #fef08a; } .porsyar-login-notice a { color: var(--primary-color); font-weight: bold; text-decoration: underline; }
@keyframes p-pulse { 0% { box-shadow: 0 0 0 0 rgba(74, 222, 128, 0.7); } 70% { box-shadow: 0 0 0 6px rgba(74, 222, 128, 0); } 100% { box-shadow: 0 0 0 0 rgba(74, 222, 128, 0); } }
@keyframes p-blink { 50% { border-color: transparent; } }
.typing-dots span { width: 6px; height: 6px; background: #9ca3af; border-radius: 50%; display: inline-block; margin: 0 2px; animation: p-bounce 1.4s infinite ease-in-out both; }
.typing-dots span:nth-child(1) { animation-delay: -0.32s; } .typing-dots span:nth-child(2) { animation-delay: -0.16s; }
@keyframes p-bounce { 0%, 80%, 100% { transform: scale(0); } 40% { transform: scale(1); } }
</style>