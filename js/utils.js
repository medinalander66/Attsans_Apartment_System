export function showError(message, elementId = 'error-message') {
    const errorDiv = document.getElementById(elementId);
    if (!errorDiv) return;
    errorDiv.style.display = 'block';
    errorDiv.style.textAlign = 'center';
    errorDiv.style.fontSize = '18px';
    errorDiv.style.marginTop = '10px';
    errorDiv.textContent = message;
}
