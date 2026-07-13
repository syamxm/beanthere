(function () {
  var chat = document.getElementById('chatLog');
  var form = document.getElementById('chatForm');
  var input = document.getElementById('chatInput');
  var csrfToken = form.dataset.csrf;
  var pending = false;

  function scrollToBottom() {
    chat.scrollTop = chat.scrollHeight;
  }

  function addBubble(role, text, source) {
    var row = document.createElement('div');
    row.className = role === 'user' ? 'flex justify-end' : 'flex flex-col items-start';

    var bubble = document.createElement('div');
    bubble.className = role === 'user'
      ? 'max-w-[80%] bg-caramel text-espresso rounded-2xl rounded-br-sm px-4 py-2.5 text-sm'
      : 'max-w-[80%] bg-roast border border-bean rounded-2xl rounded-bl-sm px-4 py-2.5 text-sm';
    bubble.textContent = text;
    row.appendChild(bubble);

    if (source) {
      var badge = document.createElement('span');
      badge.className = source === 'model'
        ? 'mt-1 ml-1 text-[10px] uppercase tracking-wide text-caramel/80'
        : 'mt-1 ml-1 text-[10px] uppercase tracking-wide text-foam/70';
      badge.textContent = source === 'model' ? 'AI generated' : 'Fallback answer';
      row.appendChild(badge);
    }

    chat.appendChild(row);
    scrollToBottom();
    return row;
  }

  function addTypingIndicator() {
    var row = document.createElement('div');
    row.className = 'flex justify-start';
    row.innerHTML = '<div class="bg-roast border border-bean rounded-2xl rounded-bl-sm px-4 py-3 flex gap-1.5">' +
      '<span class="typing-dot"></span><span class="typing-dot"></span><span class="typing-dot"></span>' +
      '</div>';
    chat.appendChild(row);
    scrollToBottom();
    return row;
  }

  function addDrinkCard(drink) {
    var card = document.createElement('form');
    card.action = 'customize.php';
    card.method = 'post';
    card.className = 'flex justify-start';

    var id = document.createElement('input');
    id.type = 'hidden';
    id.name = 'id';
    id.value = drink.id;

    var section = document.createElement('input');
    section.type = 'hidden';
    section.name = 'from_section';
    section.value = drink.category;

    var body = document.createElement('div');
    body.className = 'w-full max-w-xs bg-roast border border-bean rounded-2xl overflow-hidden hover:border-caramel transition';

    var image = document.createElement('img');
    image.src = drink.image_path;
    image.alt = drink.name;
    image.loading = 'lazy';
    image.className = 'w-full h-36 object-cover';

    var details = document.createElement('div');
    details.className = 'p-4';

    var header = document.createElement('div');
    header.className = 'flex items-start justify-between gap-2 mb-1';

    var name = document.createElement('h3');
    name.className = 'font-semibold';
    name.textContent = drink.name;

    var price = document.createElement('span');
    price.className = 'text-caramel font-semibold whitespace-nowrap';
    price.textContent = 'RM' + drink.price;

    var description = document.createElement('p');
    description.className = 'text-foam text-sm mb-4';
    description.textContent = drink.description;

    var button = document.createElement('button');
    button.type = 'submit';
    button.className = 'w-full bg-caramel text-espresso font-semibold py-2 rounded-lg hover:bg-crema transition';
    button.textContent = 'Customise & order';

    header.appendChild(name);
    header.appendChild(price);
    details.appendChild(header);
    details.appendChild(description);
    details.appendChild(button);
    body.appendChild(image);
    body.appendChild(details);
    card.appendChild(id);
    card.appendChild(section);
    card.appendChild(body);
    chat.appendChild(card);
    scrollToBottom();
  }

  function send(message) {
    if (pending || !message) {
      return;
    }
    pending = true;
    addBubble('user', message);
    input.value = '';
    var typing = addTypingIndicator();

    fetch('recommend_api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ message: message, csrf_token: csrfToken })
    })
      .then(function (response) {
        if (!response.ok) {
          throw new Error('request failed');
        }
        return response.json();
      })
      .then(function (data) {
        typing.remove();
        addBubble('bot', data.reply, data.source);
        if (data.drink) {
          addDrinkCard(data.drink);
        }
      })
      .catch(function () {
        typing.remove();
        addBubble('bot', 'I could not reach the counter just then — try asking me again.');
      })
      .finally(function () {
        pending = false;
      });
  }

  form.addEventListener('submit', function (event) {
    event.preventDefault();
    send(input.value.trim());
  });

  document.querySelectorAll('[data-quick-reply]').forEach(function (chip) {
    chip.addEventListener('click', function () {
      send(chip.dataset.quickReply);
    });
  });
})();
