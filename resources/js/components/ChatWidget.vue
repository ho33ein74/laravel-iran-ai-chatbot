<template>
  <div class="iran-ai-chatbot-ai-wrapper" :style="cssVars" dir="rtl">
    <!-- اضافه شدن شرط hideFab برای مخفی کردن دکمه پیش‌فرض -->
    <button v-if="!isOpen && !inline && !hideFab" @click="isOpen = true" :class="['iran-ai-chatbot-fab', `pos-${position}`]">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
           stroke-linejoin="round">
        <path
            d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
      </svg>
    </button>
    <div v-show="isOpen || inline"
         :class="['iran-ai-chatbot-chat-container', inline ? 'iran-ai-chatbot-inline' : displayModeClass, `pos-${position}`]">
      <div class="iran-ai-chatbot-header">
        <div class="p-header-info">
          <div class="p-avatar">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path
                  d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" />
            </svg>
          </div>
          <div class="p-title-box">
            <h3 class="p-title">{{ title }}</h3>
            <p class="p-subtitle"><span class="p-dot"></span> آنلاین و آماده پاسخگویی</p>
          </div>
        </div>
        <div class="p-header-actions">
          <button @click="clearChat" title="پاک کردن تاریخچه">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path
                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
          </button>
          <!-- اضافه شدن شرط hideCloseButton -->
          <button v-if="!inline && !hideCloseButton" @click="isOpen = false">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6L6 18M6 6l12 12"></path>
            </svg>
          </button>
        </div>
      </div>

      <div v-if="needsLoginNotice" class="iran-ai-chatbot-login-notice">
        {{ loginText }} <a :href="loginUrl">{{ loginLinkText }}</a>
      </div>

      <div class="iran-ai-chatbot-body" ref="chatContainer">
        <div v-for="(msg, index) in messages" :key="index"
             :class="['iran-ai-chatbot-msg-wrapper', msg.isUser ? 'user' : 'bot']">
          <div class="iran-ai-chatbot-bubble">
            <span v-if="!msg.isUser && msg.isTyping" class="p-typing-cursor"
                  v-html="formatText(msg.displayedText)"></span>
            <span v-else v-html="formatText(msg.text)"></span>
            <div class="p-time">{{ msg.time }}</div>
          </div>

          <!-- اسلایدر افقی نتایج جستجو -->
          <div v-if="msg.suggestions && msg.suggestions.length > 0"
               class="iran-ai-chatbot-suggestions-slider">
            <a v-for="sug in msg.suggestions"
               :key="sug.id"
               :href="sug.url || '#'"
               target="_blank"
               class="p-card-carousel">

              <div class="p-card-img-wrapper">
                <img v-if="sug.image" :src="sug.image" :alt="sug.title" class="p-card-img" />
                <div v-else class="p-card-placeholder">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                    <polyline points="21 15 16 10 5 21"></polyline>
                  </svg>
                </div>
              </div>
              <div class="p-card-info">
                <span class="p-card-badge">{{ sug.type }}</span>
                <span class="p-card-title">{{ sug.title }}</span>
              </div>
            </a>
          </div>
        </div>
        <div v-if="isLoading" class="iran-ai-chatbot-msg-wrapper bot">
          <div class="iran-ai-chatbot-bubble typing-dots"><span></span><span></span><span></span></div>
        </div>
      </div>

      <div class="iran-ai-chatbot-footer">
        <input v-model="input" @keyup.enter="sendMessage" type="text" :disabled="needsLoginNotice"
               class="p-input"
               :placeholder="placeholderText">
        <button @click="sendMessage" :disabled="!input.trim() || needsLoginNotice"
                :class="['p-send-btn', (input.trim() && !needsLoginNotice) ? 'active' : '']">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="22" y1="2" x2="11" y2="13"></line>
            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick, computed, watch } from "vue";
import axios from "axios";

axios.defaults.withCredentials = true;

const props = defineProps({
  apiEndpoint: { type: String, default: "/api/ai-chatbot" },
  inline: { type: Boolean, default: false },
  color: { type: String, default: "#1a56db" },
  title: { type: String, default: "دستیار هوشمند" },
  position: { type: String, default: "right" },
  displayMode: { type: String, default: "popup" },

  hideFab: { type: Boolean, default: false },
  defaultOpen: { type: Boolean, default: false },
  hideCloseButton: { type: Boolean, default: false },

  // اضافه شدن پراپ ذخیره تاریخچه مرورگر
  saveHistory: { type: Boolean, default: true },

  // متون نمایشی (کاملاً داینامیک)
  initialMessage: { type: String, default: "👋 سلام! به گفتگوی هوشمند خوش آمدید. سوالی دارید از من بپرسید!" },
  placeholderText: { type: String, default: "پیام خود را بنویسید..." },
  voiceAlertText: { type: String, default: "سرویس پردازش صدا به زودی فعال می‌شود." },

  // متون مربوط به لاگین
  loginText: { type: String, default: "برای استفاده از این بخش باید ابتدا" },
  loginLinkText: { type: String, default: "وارد سایت شوید" },
  loginUrl: { type: String, default: "/login" },

  // پیام‌های سیستم و خطاها
  clearConfirmText: { type: String, default: "آیا از پاک کردن تاریخچه گفتگو مطمئن هستید؟" },
  historyClearedText: { type: String, default: "تاریخچه پاک شد." },
  noResponseText: { type: String, default: "پاسخی دریافت نشد." },
  rateLimitText: { type: String, default: "سقف مجاز روزانه پر شده است." },
  errorText: { type: String, default: "خطا در ارتباط. لطفا دوباره تلاش کنید." },
  authErrorText: { type: String, default: "برای ادامه باید وارد حساب کاربری خود شوید." }
});

// استفاده از پراپ defaultOpen برای تعیین وضعیت اولیه باز یا بسته بودن
const isOpen = ref(props.defaultOpen);
const isLoading = ref(false);
const input = ref("");
const chatContainer = ref(null);
const messages = ref([]);
const needsLoginNotice = ref(false);

const cssVars = computed(() => ({ "--primary-color": props.color }));

const displayModeClass = computed(() => {
  if (props.displayMode === "sidebar") return "iran-ai-chatbot-sidebar";
  if (props.displayMode === "fullscreen") return "iran-ai-chatbot-fullscreen";
  return "iran-ai-chatbot-popup";
});

const alertFunc = () => alert(props.voiceAlertText);

// 🌟 ذخیره هوشمند در LocalStorage با هر بار تغییر آرایه پیام‌ها
watch(messages, (newVal) => {
  if (props.saveHistory) {
    const toSave = newVal.map(m => ({
      ...m,
      isTyping: false,
      displayedText: m.text
    }));
    localStorage.setItem("iran_ai_chat_history", JSON.stringify(toSave));
  }
}, { deep: true });

onMounted(() => {
  // 🌟 گوش دادن به ایونت‌های خارجی برای باز و بسته کردن ربات از هر کجای سایت
  window.addEventListener('open-ai-chat', () => { isOpen.value = true; });
  window.addEventListener('close-ai-chat', () => { isOpen.value = false; });
  window.addEventListener('toggle-ai-chat', () => { isOpen.value = !isOpen.value; });

  let historyLoaded = false;

  // تلاش برای لود کردن کش مرورگر
  if (props.saveHistory) {
    const savedData = localStorage.getItem("iran_ai_chat_history");
    if (savedData) {
      try {
        messages.value = JSON.parse(savedData);
        historyLoaded = true;
        scrollToBottom();
      } catch (e) {
      }
    }
  }

  // اگر کش خاموش بود یا قبلا پیامی نداشت، همون پیام پیش‌فرض رو نشون بده
  if (!historyLoaded) {
    messages.value.push({
      text: props.initialMessage,
      isUser: false,
      time: getCurrentTime(),
      isTyping: false,
      displayedText: props.initialMessage
    });
  }
});

const getCurrentTime = () => {
  const d = new Date();
  return d.getHours() + ":" + (d.getMinutes() < 10 ? "0" : "") + d.getMinutes();
};
const scrollToBottom = async () => {
  await nextTick();
  if (chatContainer.value) chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
};
const formatText = (text) => text ? text.replace(/\n/g, "<br>") : "";

const clearChat = async () => {
  if (!confirm(props.clearConfirmText)) return;
  try {
    await axios.post(`${props.apiEndpoint}/clear`);
    // ریست کردن پیام‌ها باعث میشه واچر خود به خود کش LocalStorage رو هم پاک/ریست کنه
    messages.value = [{
      text: props.historyClearedText,
      isUser: false,
      time: getCurrentTime(),
      isTyping: false,
      displayedText: props.historyClearedText
    }];
  } catch (e) {
  }
};

const typeText = async (msgObj) => {
  msgObj.isTyping = true;
  msgObj.displayedText = "";
  const fullText = msgObj.text;
  for (let i = 0; i <= fullText.length; i++) {
    msgObj.displayedText = fullText.substring(0, i);
    await new Promise(resolve => setTimeout(resolve, 20));
    if (i % 4 === 0) scrollToBottom();
  }
  msgObj.isTyping = false;
  scrollToBottom();
};

const sendMessage = async () => {
  if (!input.value.trim() || needsLoginNotice.value) return;
  const userMsg = input.value;
  messages.value.push({ text: userMsg, isUser: true, time: getCurrentTime() });
  input.value = "";
  isLoading.value = true;
  if (!props.inline && !isOpen.value) isOpen.value = true;
  scrollToBottom();

  try {
    const response = await axios.post(`${props.apiEndpoint}/chat`, { message: userMsg });
    const replyText = response.data.reply || props.noResponseText;

    const botMsg = {
      text: replyText,
      isUser: false,
      time: getCurrentTime(),
      isTyping: true,
      displayedText: "",
      suggestions: response.data.suggestions || []
    };

    messages.value.push(botMsg);
    isLoading.value = false;
    scrollToBottom();

    const reactiveMsg = messages.value[messages.value.length - 1];
    await typeText(reactiveMsg);

  } catch (error) {
    isLoading.value = false;
    if (error.response && error.response.status === 401) {
      messages.value.push({ text: props.authErrorText, isUser: false, time: getCurrentTime() });
      needsLoginNotice.value = true;
    } else if (error.response && error.response.status === 429) {
      messages.value.push({ text: props.rateLimitText, isUser: false, time: getCurrentTime() });
    } else {
      messages.value.push({ text: props.errorText, isUser: false, time: getCurrentTime() });
    }
  } finally {
    scrollToBottom();
  }
};
</script>

<style scoped>
.iran-ai-chatbot-ai-wrapper {
  font-family: inherit;
  box-sizing: border-box;
}

.iran-ai-chatbot-ai-wrapper * {
  box-sizing: inherit;
}

.iran-ai-chatbot-fab {
  position: fixed;
  bottom: 24px;
  z-index: 9999;
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background-color: var(--primary-color);
  color: white;
  border: none;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  display: flex;
  justify-content: center;
  align-items: center;
  transition: transform 0.2s;
}

.iran-ai-chatbot-fab.pos-right {
  right: 24px;
}

.iran-ai-chatbot-fab.pos-left {
  left: 24px;
}

.iran-ai-chatbot-fab:hover {
  transform: scale(1.05);
}

.iran-ai-chatbot-fab svg {
  width: 30px;
  height: 30px;
}

.iran-ai-chatbot-chat-container {
  background: #f9fafb;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.iran-ai-chatbot-popup {
  position: fixed;
  bottom: 95px;
  z-index: 9999;
  width: 380px;
  height: 550px;
  border-radius: 16px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
  border: 1px solid #e5e7eb;
}

.iran-ai-chatbot-popup.pos-right {
  right: 24px;
}

.iran-ai-chatbot-popup.pos-left {
  left: 24px;
}

.iran-ai-chatbot-sidebar {
  position: fixed;
  bottom: 0;
  top: 0;
  z-index: 9999;
  width: 400px;
  height: 100vh;
  border-radius: 0;
  box-shadow: -5px 0 25px rgba(0, 0, 0, 0.15);
  border: none;
}

.iran-ai-chatbot-sidebar.pos-right {
  right: 0;
}

.iran-ai-chatbot-sidebar.pos-left {
  left: 0;
}

.iran-ai-chatbot-fullscreen {
  position: fixed;
  bottom: 0;
  right: 0;
  top: 0;
  left: 0;
  z-index: 9999;
  width: 100vw;
  height: 100vh;
  border-radius: 0;
  border: none;
}

.iran-ai-chatbot-inline {
  width: 100%;
  height: 600px;
  border-radius: 16px;
  border: 1px solid #e5e7eb;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

@media (max-width: 480px) {
  .iran-ai-chatbot-popup {
    width: 90%;
    bottom: 85px;
    height: 500px;
  }

  .iran-ai-chatbot-popup.pos-right {
    right: 5%;
  }

  .iran-ai-chatbot-popup.pos-left {
    left: 5%;
  }

  .iran-ai-chatbot-sidebar {
    width: 100vw;
  }
}

.iran-ai-chatbot-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  color: white;
  background-color: var(--primary-color);
}

.p-header-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.p-avatar {
  width: 42px;
  height: 42px;
  background: white;
  border-radius: 50%;
  display: flex;
  justify-content: center;
  align-items: center;
  color: var(--primary-color);
}

.p-avatar svg {
  width: 24px;
  height: 24px;
}

.p-title-box {
  display: flex;
  flex-direction: column;
}

.p-title {
  margin: 0;
  font-size: 16px;
  font-weight: bold;
}

.p-subtitle {
  margin: 4px 0 0 0;
  font-size: 11px;
  opacity: 0.9;
  display: flex;
  align-items: center;
  gap: 4px;
}

.p-dot {
  width: 8px;
  height: 8px;
  background: #4ade80;
  border-radius: 50%;
  display: inline-block;
  animation: p-pulse 2s infinite;
}

.p-header-actions button {
  background: transparent;
  border: none;
  color: white;
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
  display: inline-flex;
}

.p-header-actions button:hover {
  background: rgba(255, 255, 255, 0.2);
}

.p-header-actions svg {
  width: 20px;
  height: 20px;
}

.iran-ai-chatbot-body {
  flex: 1;
  padding: 16px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 16px;
  background: #f3f4f6;
}

.iran-ai-chatbot-msg-wrapper {
  display: flex;
  flex-direction: column;
  width: 100%;
}

.iran-ai-chatbot-msg-wrapper.user {
  align-items: flex-end;
}

.iran-ai-chatbot-msg-wrapper.bot {
  align-items: flex-start;
}

.iran-ai-chatbot-bubble {
  max-width: 85%;
  padding: 12px 16px;
  border-radius: 16px;
  font-size: 14px;
  line-height: 1.6;
  position: relative;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.iran-ai-chatbot-msg-wrapper.user .iran-ai-chatbot-bubble {
  background: #dbeafe;
  color: #1e3a8a;
  border-bottom-right-radius: 4px;
}

.iran-ai-chatbot-msg-wrapper.bot .iran-ai-chatbot-bubble {
  background: #ffffff;
  color: #1f2937;
  border-bottom-left-radius: 4px;
  border: 1px solid #e5e7eb;
}

.p-time {
  font-size: 10px;
  margin-top: 6px;
  opacity: 0.6;
  text-align: left;
}

.p-typing-cursor {
  border-left: 2px solid #9ca3af;
  padding-left: 4px;
  animation: p-blink 1s step-end infinite;
}

.iran-ai-chatbot-suggestions-slider {
  margin-top: 12px;
  display: flex;
  flex-direction: row;
  gap: 12px;
  width: 100%;
  max-width: 100%;
  overflow-x: auto;
  padding-bottom: 10px;
  scrollbar-width: thin;
  scrollbar-color: #d1d5db transparent;
}

.iran-ai-chatbot-suggestions-slider::-webkit-scrollbar {
  height: 4px;
}

.iran-ai-chatbot-suggestions-slider::-webkit-scrollbar-thumb {
  background: #d1d5db;
  border-radius: 4px;
}

.p-card-carousel {
  flex: 0 0 160px;
  display: flex;
  flex-direction: column;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  text-decoration: none;
  color: inherit;
  transition: all 0.3s ease;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.04);
  overflow: hidden;
}

.p-card-carousel:hover {
  border-color: var(--primary-color);
  box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
  transform: translateY(-3px);
}

.p-card-img-wrapper {
  width: 100%;
  height: 120px;
  background-color: #f9fafb;
  display: flex;
  justify-content: center;
  align-items: center;
  border-bottom: 1px solid #f3f4f6;
}

.p-card-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.p-card-placeholder svg {
  width: 32px;
  height: 32px;
  color: #9ca3af;
  opacity: 0.5;
}

.p-card-info {
  padding: 10px 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.p-card-badge {
  font-size: 10px;
  background: #eff6ff;
  color: var(--primary-color);
  padding: 3px 6px;
  border-radius: 6px;
  align-self: flex-start;
  font-weight: 700;
}

.p-card-title {
  font-size: 13px;
  font-weight: 700;
  color: #374151;
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.iran-ai-chatbot-footer {
  padding: 12px;
  background: white;
  border-top: 1px solid #e5e7eb;
  display: flex;
  align-items: center;
  gap: 8px;
}

.p-input {
  flex: 1;
  border: 2px solid #e5e7eb;
  border-radius: 24px;
  padding: 10px 16px;
  font-size: 13px;
  font-family: inherit;
  outline: none;
  transition: border-color 0.2s;
  background: #f9fafb;
}

.p-input:focus {
  border-color: var(--primary-color);
  background: white;
}

.p-input:disabled {
  background: #f3f4f6;
  cursor: not-allowed;
}

.p-send-btn {
  background: #f3f4f6;
  color: #9ca3af;
  border: none;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  display: flex;
  justify-content: center;
  align-items: center;
  cursor: not-allowed;
  transition: all 0.2s;
}

.p-send-btn.active {
  background-color: var(--primary-color);
  color: white;
  cursor: pointer;
}

.p-send-btn svg {
  width: 18px;
  height: 18px;
}

.iran-ai-chatbot-login-notice {
  background: #fefce8;
  color: #854d0e;
  padding: 10px;
  text-align: center;
  font-size: 12px;
  border-bottom: 1px solid #fef08a;
}

.iran-ai-chatbot-login-notice a {
  color: var(--primary-color);
  font-weight: bold;
  text-decoration: underline;
}

@keyframes p-pulse {
  0% {
    box-shadow: 0 0 0 0 rgba(74, 222, 128, 0.7);
  }
  70% {
    box-shadow: 0 0 0 6px rgba(74, 222, 128, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(74, 222, 128, 0);
  }
}

@keyframes p-blink {
  50% {
    border-color: transparent;
  }
}

.typing-dots span {
  width: 6px;
  height: 6px;
  background: #9ca3af;
  border-radius: 50%;
  display: inline-block;
  margin: 0 2px;
  animation: p-bounce 1.4s infinite ease-in-out both;
}

.typing-dots span:nth-child(1) {
  animation-delay: -0.32s;
}

.typing-dots span:nth-child(2) {
  animation-delay: -0.16s;
}

@keyframes p-bounce {
  0%, 80%, 100% {
    transform: scale(0);
  }
  40% {
    transform: scale(1);
  }
}
</style>