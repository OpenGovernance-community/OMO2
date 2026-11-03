window.commonLmsShowQuizFeedback = function (answer, labels, isLastQuestion) {
    const text = String(answer || '').trim();
    if (!text) return false;
    const panel = document.createElement('section');
    panel.className = 'quiz';
    panel.dataset.lmsQuizFeedback = '1';
    panel.setAttribute('role', 'status');
    const heading = document.createElement('strong');
    heading.textContent = labels.wellDone;
    const explanation = document.createElement('p');
    explanation.className = 'generic-description';
    text.split(/\r?\n/).forEach((line, index) => {
        if (index > 0) explanation.append(document.createElement('br'));
        explanation.append(document.createTextNode(line));
    });
    panel.append(heading, explanation);
    const zone = document.getElementById('quiz-zone');
    zone.replaceChildren(panel);
    zone.scrollTop = 0;
    const button = document.getElementById('doneBtn');
    button.textContent = isLastQuestion ? labels.close : labels.next;
    button.disabled = false;
    return true;
};
