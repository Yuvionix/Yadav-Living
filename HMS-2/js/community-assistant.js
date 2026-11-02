(() => {
    const assistant = document.getElementById('communityAssistant');
    const form = document.getElementById('assistantForm');
    const input = document.getElementById('assistantQuestion');
    const sendButton = document.getElementById('assistantSend');
    const messages = document.getElementById('assistantMessages');

    if (!assistant || !form || !input || !sendButton || !messages) return;

    function appendMessage(text, kind, sources = []) {
        const message = document.createElement('div');
        message.className = `message message-${kind}`;
        message.textContent = text;

        if (sources.length) {
            const sourceList = document.createElement('div');
            sourceList.className = 'message-sources';
            sources.forEach((source) => {
                if (!source || typeof source.title !== 'string' || typeof source.url !== 'string') return;
                const link = document.createElement('a');
                link.href = source.url;
                link.textContent = `Source: ${source.title}`;
                sourceList.appendChild(link);
            });
            if (sourceList.childElementCount) message.appendChild(sourceList);
        }

        messages.appendChild(message);
        messages.scrollTop = messages.scrollHeight;
        return message;
    }

    async function ask(question) {
        const normalizedQuestion = question.trim();
        if (!normalizedQuestion || sendButton.disabled) return;

        appendMessage(normalizedQuestion, 'user');
        input.value = '';
        sendButton.disabled = true;
        sendButton.textContent = 'Thinking…';
        const pending = appendMessage('Searching verified property guidance…', 'assistant');

        try {
            const response = await fetch(assistant.dataset.endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': assistant.dataset.csrf,
                },
                body: JSON.stringify({
                    property: assistant.dataset.property,
                    question: normalizedQuestion,
                }),
            });
            const result = await response.json();
            pending.remove();

            if (!response.ok) {
                appendMessage(
                    typeof result.error === 'string' ? result.error : 'The assistant could not answer right now. Please try again.',
                    'assistant message-error',
                );
            } else {
                appendMessage(
                    typeof result.answer === 'string' ? result.answer : 'The assistant returned an invalid response.',
                    'assistant',
                    Array.isArray(result.sources) ? result.sources : [],
                );
            }
        } catch (_error) {
            pending.remove();
            appendMessage('The assistant could not be reached. Check the service configuration and try again.', 'assistant message-error');
        } finally {
            sendButton.disabled = false;
            sendButton.textContent = 'Ask';
            input.focus();
        }
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        ask(input.value);
    });

    document.querySelectorAll('.quick-prompt').forEach((button) => {
        button.addEventListener('click', () => ask(button.textContent || ''));
    });
})();
