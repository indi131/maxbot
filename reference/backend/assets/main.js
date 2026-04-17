document.addEventListener('DOMContentLoaded', function() {
  function highlightWords(words, className) {
    const items = document.querySelectorAll(`.${className}`);
    
    items.forEach(item => {
      const text = item.textContent;
      let newText = text;
      
      words.forEach(word => {
        const regex = new RegExp(`(${word})`, 'gi');
        newText = newText.replace(regex, `<strong>$1</strong>`);
      });
      
      item.innerHTML = newText;
    });
  }

  // Пример использования
  highlightWords(['Имя', 'Телефон','Email'], 'content');
});