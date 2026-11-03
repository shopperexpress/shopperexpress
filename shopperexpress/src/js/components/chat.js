import Chat from '../plugins/chatPlugin';

export default function initChat() {
  document.querySelectorAll('.ai-chat').forEach((holder) => {
    new Chat(holder);
  });
}
