// Claude slop
(function () {
  function getToken(id) {
    return ((Number(id) / 1e15) * Math.PI)
      .toString(6 ** 2)
      .replace(/(0+|\.)/g, '');
  }

  function extractId(url) {
    const match = url.match(/status(?:es)?\/(\d+)/);
    return match ? match[1] : null;
  }

  function escapeHtml(str) {
    return str
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function formatDate(iso) {
    const d = new Date(iso);
    return d.toLocaleDateString(undefined, {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    });
  }

  // Convert entities (links, mentions, hashtags) into HTML.
  // Syndication JSON gives us `entities` with indices; simplest reliable
  // approach is to walk the text and replace known entity strings.
  function renderText(tweet) {
    let html = escapeHtml(tweet.text || '');

    const urls = (tweet.entities && tweet.entities.urls) || [];
    urls.forEach((u) => {
      html = html.replace(
        escapeHtml(u.url),
        `<a href="${escapeHtml(u.expanded_url)}" target="_blank" rel="noopener">${escapeHtml(u.display_url)}</a>`
      );
    });

    const mentions = (tweet.entities && tweet.entities.user_mentions) || [];
    mentions.forEach((m) => {
      const handle = '@' + m.screen_name;
      html = html.replace(
        new RegExp(escapeHtml(handle), 'gi'),
        `<a href="https://twitter.com/${escapeHtml(m.screen_name)}" target="_blank" rel="noopener">${escapeHtml(handle)}</a>`
      );
    });

    const hashtags = (tweet.entities && tweet.entities.hashtags) || [];
    hashtags.forEach((h) => {
      const tag = '#' + h.text;
      html = html.replace(
        new RegExp(escapeHtml(tag), 'gi'),
        `<a href="https://twitter.com/hashtag/${escapeHtml(h.text)}" target="_blank" rel="noopener">${escapeHtml(tag)}</a>`
      );
    });

    // Strip the trailing t.co media URL that points to the tweet itself,
    // since we render media separately.
    html = html.replace(/<a [^>]*pic\.twitter\.com[^<]*<\/a>\s*$/, '').trim();
    html = html.replace(/https?:\/\/t\.co\/\w+\s*$/, '').trim();

    return html;
  }

  function renderMedia(tweet) {
    const media = tweet.mediaDetails || [];
    if (!media.length) return '';

    return (
      '<div class="tweet-embed__media">' +
      media
        .map((m) => {
          if (m.type === 'photo') {
            return `<img src="${escapeHtml(m.media_url_https)}" alt="" loading="lazy">`;
          }
          if (m.type === 'video' || m.type === 'animated_gif') {
            // Pick highest-bitrate mp4 variant.
            const variants = ((m.video_info && m.video_info.variants) || [])
              .filter((v) => v.content_type === 'video/mp4')
              .sort((a, b) => (b.bitrate || 0) - (a.bitrate || 0));
            const src = variants[0] ? variants[0].url : '';
            const poster = m.media_url_https;
            return `<video src="${escapeHtml(src)}" poster="${escapeHtml(poster)}" controls ${m.type === 'animated_gif' ? 'autoplay loop muted playsinline' : ''}></video>`;
          }
          return '';
        })
        .join('') +
      '</div>'
    );
  }

  function renderTweet(tweet, sourceUrl) {
    const user = tweet.user || {};
    const name = escapeHtml(user.name || '');
    const handle = escapeHtml(user.screen_name || '');
    const avatar = escapeHtml(user.profile_image_url_https || '');
    const date = formatDate(tweet.created_at);
    const permalink = escapeHtml(sourceUrl);

    return `
      <div class="tweet-embed__card">
        <div class="tweet-embed__header">
          <img class="tweet-embed__avatar" src="${avatar}" alt="" loading="lazy">
          <div class="tweet-embed__author">
            <span class="tweet-embed__name">${name}</span>
            <span class="tweet-embed__handle">@${handle}</span>
          </div>
        </div>
        <div class="tweet-embed__body">${renderText(tweet)}</div>
        ${renderMedia(tweet)}
        <div class="tweet-embed__footer">
          <a class="tweet-embed__date" href="${permalink}" target="_blank" rel="noopener">${date}</a>
        </div>
      </div>
    `;
  }

  async function hydrate(el) {
    const src = el.getAttribute('data-src');
    if (!src) return;

    const id = extractId(src);
    if (!id) {
      el.innerHTML = '<p class="tweet-embed__error">Invalid tweet URL.</p>';
      return;
    }

    const token = getToken(id);
    const endpoint = `/tweet.php?id=${id}`;

    el.classList.add('tweet-embed--loading');

    try {
      const res = await fetch(endpoint);
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const tweet = await res.json();
      el.innerHTML = renderTweet(tweet, src);
      el.classList.remove('tweet-embed--loading');
      el.classList.add('tweet-embed--loaded');
    } catch (err) {
      el.classList.remove('tweet-embed--loading');
      el.classList.add('tweet-embed--error');
      el.innerHTML = `<p class="tweet-embed__error">Could not load tweet. <a href="${escapeHtml(src)}" target="_blank" rel="noopener">View on Twitter</a></p>`;
      console.error('tweet-embed:', err);
    }
  }

  function init() {
    document.querySelectorAll('.tweet-embed[data-src]').forEach(hydrate);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();