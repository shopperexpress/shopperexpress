(function ($) {
	"use strict";

	jQuery(document).ready(function ($) {

		$(document).on("submit", "#automotive_login_form", function (e) {
			e.preventDefault();

			var nonce = $(this).find("#remember_me").data("nonce");
			var url = $(this).find(".url");
			var username = $(this).find(".username_input");
			var password = $(this).find(".password_input");
			var empty_fields = false;

			if (!username.val()) {
				empty_fields = true;
				username.css("border", "1px solid #F00");
			} else {
				username.removeAttr("style");
			}

			if (!password.val()) {
				empty_fields = true;
				password.css("border", "1px solid #F00");
			} else {
				password.removeAttr("style");
			}

			if (!empty_fields) {

				jQuery.ajax({
					url: ajax.admin,
					type: 'POST',
					data: {action: 'ajax_login', username: username.val(), password: password.val(), nonce: nonce },
					success: function (response) {
						if ("success" == response) {
							window.location.replace(url.val());
						}else{
							jQuery('.login-message').html(response).show();
						}
					}
				});
			}

		});

	});


})(jQuery);

document.addEventListener("DOMContentLoaded", () => {
    const button = document.querySelector('[data-ai-button]');
    const input = document.querySelector('[data-ai-input]');
    const messageContainer = document.querySelector('[data-ai-message]');

    if (!button || !input || !messageContainer) return;

    let isWaitingResponse = false;

    // --- UI helpers ---

    function getInputText(el) {
        const text = el.innerText || '';
        return text.replace(/\s+/g, ' ').trim();
    }

    function typeText(element, text, speed = 30) {
        let i = 0;
        element.textContent = '';
        return new Promise(resolve => {
            const interval = setInterval(() => {
                if (!text || !text[i]) {
                    clearInterval(interval);
                    resolve();
                    return;
                }
                element.textContent += text[i];
                i++;
                if (i >= text.length) {
                    clearInterval(interval);
                    resolve();
                }
            }, speed);
        });
    }

    async function typeTextSafe(element, html, speed = 30) {
        const temp = document.createElement('div');
        temp.innerHTML = html;
        const plainText = temp.textContent || temp.innerText || '';
        await typeText(element, plainText, speed);
        element.innerHTML = html;
    }

    function createLoadingDots() {
        const waitDiv = document.createElement('div');
        waitDiv.classList.add('ai-chat__message', 'answer', 'loading');
        for (let i = 0; i < 3; i++) {
            const dot = document.createElement('span');
            dot.classList.add('ai-chat__dot-pulse');
            waitDiv.appendChild(dot);
        }
        messageContainer.appendChild(waitDiv);
        return waitDiv;
    }

    function scrollToBottom() {
        messageContainer.scrollTop = messageContainer.scrollHeight;
    }

    // --- Submit handler ---

    button.addEventListener('click', () => {
        if (isWaitingResponse) return;

        const userText = getInputText(input);
        if (!userText) return;

        const userMessage = document.createElement('div');
        userMessage.classList.add('ai-chat__message');
        userMessage.textContent = userText;
        messageContainer.appendChild(userMessage);
        scrollToBottom();

        input.innerHTML = '<div><br></div>';

        isWaitingResponse = true;

        const loading = createLoadingDots();
        scrollToBottom();

        fetch('/wp-admin/admin-ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'ai',
                question: userText,
                nonce: ajax.nonce,
                type: 'faq'
            })
        })
        .then(res => res.json())
        .then(async data => {
            const loadingBlock = messageContainer.querySelector('.ai-chat__message.loading');
            if (loadingBlock && loadingBlock.parentNode) {
                loadingBlock.parentNode.removeChild(loadingBlock);
            }

            const payload = data.data || {};
            const message = payload.message || '';

            const answer = document.createElement('div');
            answer.classList.add('ai-chat__message', 'answer');
            messageContainer.appendChild(answer);
            scrollToBottom();
            await typeTextSafe(answer, message);
            scrollToBottom();

            isWaitingResponse = false;
        })
        .catch(() => {
            loading.remove();
            const errorDiv = document.createElement('div');
            errorDiv.classList.add('ai-chat__message', 'answer');
            errorDiv.textContent = 'Error receiving response';
            messageContainer.appendChild(errorDiv);
            scrollToBottom();
            isWaitingResponse = false;
        });
    });
});