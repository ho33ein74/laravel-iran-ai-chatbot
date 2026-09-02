<template>
  <div>
    <!-- دکمه شناور اصلی (مخفی در حالت اینلاین) -->
    <button v-if="displayMode === 'popup' && !isOpen && !inline" @click="isOpen = true" class="fixed bottom-5 right-5 z-50 bg-blue-600 hover:bg-blue-700 text-white rounded-full p-4 shadow-lg flex items-center justify-center transition-all">
      <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
    </button>

    <!-- کانتینر اصلی چت -->
    <div v-show="isOpen || inline" :class="containerClass" style="font-family: Tahoma, sans-serif;" dir="rtl">
      
      <!-- هدر -->
      <div class="bg-blue-600 p-4 text-white flex justify-between items-center shadow-md z-10 shrink-0">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-sm text-blue-600">
             <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
          </div>
          <div>
            <h3 class="font-bold text-lg leading-tight">{{ uiConfig.bot_name }}</h3>
            <p class="text-[11px] text-blue-100 flex items-center gap-1 mt-1">
              <span class="w-2 h-2 bg-green-400 rounded-full inline-block animate-pulse"></span>
              آنلاین و آماده پاسخگویی
            </p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <button @click="clearChat" class="text-white hover:text-gray-200 transition p-1 rounded hover:bg-blue-700" title="پاک کردن تاریخچه">
             <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
          </button>
          <button v-if="!inline" @click="isOpen = false" class="text-white hover:text-gray-200 transition p-1 rounded hover:bg-blue-700">
             <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
          </button>
        </div>
      </div>

      <!-- اخطار لاگین -->
      <div v-if="needsLoginNotice" class="bg-yellow-50 border-b border-yellow-100 p-3 text-yellow-800 text-xs text-center">
         برای استفاده از این بخش باید ابتدا <a href="/login" class="font-bold underline text-blue-600">وارد سایت</a> شوید.
      </div>

      <!-- ناحیه پیام‌ها -->
      <div class="flex-1 p-4 overflow-y-auto bg-slate-50 flex flex-col gap-4" ref="chatContainer">
        
        <div v-if="messages.length === 0" class="flex flex-col items-center justify-center h-full text-center opacity-70">
           <svg class="w-16 h-16 text-gray-300 mb-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
           <p class="text-gray-500">سلام! سوالی دارید از من بپرسید.</p>
        </div>

        <div v-for="(msg, index) in messages" :key="index" class="flex flex-col w-full">
            <div :class="[
                'p-3 text-sm leading-relaxed relative',
                layoutMode === 'bubble' ? 'max-w-[85%] shadow-sm' : 'w-full border-b border-gray-100',
                layoutMode === 'bubble' && msg.isUser ? 'bg-blue-100 text-blue-900 self-end rounded-2xl rounded-br-sm' : '',
                layoutMode === 'bubble' && !msg.isUser ? 'bg-white border border-gray-200 text-gray-800 self-start rounded-2xl rounded-bl-sm' : '',
                layoutMode === 'stacked' && msg.isUser ? 'bg-blue-50 text-blue-900' : '',
                layoutMode === 'stacked' && !msg.isUser ? 'bg-white text-gray-800' : ''
            ]">
                <strong v-if="layoutMode === 'stacked'" class="block mb-1 text-xs opacity-70">{{ msg.isUser ? 'شما' : uiConfig.bot_name }}</strong>
                <span v-if="!msg.isUser && msg.isTyping" class="inline-block border-l-2 border-gray-400 pl-1 animate-pulse">{{ msg.displayedText }}</span>
                <span v-else v-html="formatText(msg.text)"></span>
                <div v-if="layoutMode === 'bubble'" :class="['text-[10px] mt-1 opacity-60 text-left', msg.isUser ? 'text-blue-700' : 'text-gray-400']">{{ msg.time }}</div>
            </div>

            <div v-if="msg.suggestions && msg.suggestions.length > 0" class="self-start mt-2 w-full max-w-[85%]">
                <div class="grid grid-cols-1 gap-2">
                    <a v-for="sug in msg.suggestions" :key="sug.id" href="#" class="block p-3 bg-white border border-blue-100 rounded-xl hover:border-blue-300 hover:shadow-md transition">
                        <div class="flex items-center justify-between">
                           <span class="font-bold text-sm text-gray-800">{{ sug.title }}</span>
                           <span class="text-xs bg-blue-50 text-blue-600 px-2 py-1 rounded-md">{{ sug.type }}</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <div v-if="isLoading" class="bg-white border border-gray-200 rounded-2xl p-4 w-20 shadow-sm self-start rounded-bl-sm flex gap-1 justify-center items-center h-10">
          <span class="w-1.5 h-1.5 bg-blue-500 rounded-full animate-bounce"></span>
          <span class="w-1.5 h-1.5 bg-blue-500 rounded-full animate-bounce" style="animation-delay: 0.2s"></span>
          <span class="w-1.5 h-1.5 bg-blue-500 rounded-full animate-bounce" style="animation-delay: 0.4s"></span>
        </div>
      </div>

      <!-- ورودی متن و ابزارها -->
      <div class="p-3 bg-white border-t border-gray-100 flex items-center gap-2 shrink-0 relative">
        <button class="text-blue-600 bg-blue-50 hover:bg-blue-100 border border-blue-100 rounded-full p-2 transition-colors shadow-sm" title="ارسال صدا">
             <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
        </button>
        
        <input 
          v-model="input" 
          @keyup.enter="sendMessage"
          type="text" 
          :disabled="needsLoginNotice"
          class="flex-1 bg-white border-2 border-blue-100 rounded-full px-4 py-2 text-sm focus:outline-none focus:border-blue-500 transition-colors shadow-inner disabled:bg-gray-100 disabled:cursor-not-allowed"
          placeholder="پیام خود را بنویسید..."
        >
        
        <button @click="sendMessage" :disabled="!input.trim() || needsLoginNotice" :class="['p-2 rounded-full transition shadow-sm', (input.trim() && !needsLoginNotice) ? 'bg-blue-600 hover:bg-blue-700 text-white' : 'bg-gray-100 text-gray-400 cursor-not-allowed']">
          <svg class="w-5 h-5 transform rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19V6m0 0l-7 7m7-7l7 7"></path></svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import axios from 'axios';

const props = defineProps({
  apiEndpoint: { type: String, default: '/api/ai-chatbot' },
  inline: { type: Boolean, default: false }
});

const isOpen = ref(false);
const isLoading = ref(false);
const input = ref('');
const chatContainer = ref(null);
const messages = ref([]);
const needsLoginNotice = ref(false);

const uiConfig = ref({
    bot_name: 'دستیار هوشمند',
    primary_color: '#3b82f6',
});

const displayMode = ref('popup'); 
const layoutMode = ref('bubble'); 

const containerClass = computed(() => {
    if (props.inline) return 'w-full h-[600px] border border-gray-200 rounded-2xl relative shadow-sm flex flex-col bg-white overflow-hidden';
    if (displayMode.value === 'fullscreen') return 'fixed inset-0 w-full h-full bg-white z-50 flex flex-col';
    if (displayMode.value === 'sidebar') return 'fixed top-0 right-0 w-80 sm:w-[350px] h-full bg-white shadow-2xl z-50 flex flex-col border-l border-gray-200';
    return 'fixed bottom-20 right-5 w-80 sm:w-[380px] bg-white rounded-2xl shadow-2xl border border-gray-100 flex flex-col h-[550px] z-50 overflow-hidden';
});

onMounted(async () => {
    try {
        const res = await axios.get(`${props.apiEndpoint}/settings`);
        uiConfig.value = res.data;
        if(res.data.display_mode) displayMode.value = res.data.display_mode;
        if(res.data.layout_mode) layoutMode.value = res.data.layout_mode;
        if(res.data.auth_required && !res.data.is_logged_in) needsLoginNotice.value = true;
    } catch(e) {}
    
    messages.value.push({
        text: '👋 سلام! به گفتگوی هوشمند خوش آمدید. سوالی دارید؟',
        isUser: false, time: getCurrentTime(), isTyping: false, displayedText: '👋 سلام! به گفتگوی هوشمند خوش آمدید. سوالی دارید؟'
    });
});

const getCurrentTime = () => {
    const d = new Date();
    return d.getHours() + ':' + (d.getMinutes() < 10 ? '0' : '') + d.getMinutes();
};

const scrollToBottom = async () => {
  await nextTick();
  if (chatContainer.value) chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
};

const formatText = (text) => text ? text.replace(/\n/g, '<br>') : '';

const clearChat = async () => {
    if(!confirm('آیا از پاک کردن تاریخچه گفتگو مطمئن هستید؟')) return;
    try {
        await axios.post(`${props.apiEndpoint}/clear`);
        messages.value = [{ text: 'تاریخچه پاک شد. چطور می‌توانم کمک کنم؟', isUser: false, time: getCurrentTime(), isTyping: false }];
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
        messages.value.push({ text: 'برای ادامه گفتگو باید وارد حساب کاربری شوید.', isUser: false, time: getCurrentTime() });
        needsLoginNotice.value = true;
    } else if (error.response && error.response.status === 429) {
        messages.value.push({ text: 'سقف مجاز روزانه پرسش‌های شما پر شده است.', isUser: false, time: getCurrentTime() });
    } else {
        messages.value.push({ text: 'خطا در ارتباط با سرور.', isUser: false, time: getCurrentTime() });
    }
  } finally {
    isLoading.value = false;
    scrollToBottom();
  }
};
</script>