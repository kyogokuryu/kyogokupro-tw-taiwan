/**
 * KYOGOKU Feed Video Widget - TikTok/Dr.Kozu Style
 * 商品詳細ページ用：カルーセル表示 + 全画面TikTok風プレイヤー
 * 
 * Features:
 * - Fullscreen modal with vertical swipe navigation
 * - Right-side action buttons (Like, Comment, Share)
 * - Bottom product card with "加購物車" button
 * - Comment panel (slide-up)
 * - Search button
 * - Mute toggle
 * - Touch/swipe support for mobile
 */
(function() {
    'use strict';

    var FEED_API = '/feed/api/';
    var FEED_URL = '/feed/';
    var allVideos = [];
    var currentIndex = 0;
    var touchStartY = 0;
    var touchDeltaY = 0;
    var isSwiping = false;
    var isCommentOpen = false;
    var isMuted = false; // タップで開くので音声ON

    // ===== Inject Styles =====
    function injectStyles() {
        if (document.getElementById('kg-widget-styles')) return;
        var css = document.createElement('style');
        css.id = 'kg-widget-styles';
        css.textContent = '\
/* === Widget Carousel === */\
.kg-widget{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;margin:20px 0;padding:0}\
.kg-widget *{box-sizing:border-box}\
.kg-widget-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;padding:0 4px}\
.kg-widget-title{font-size:17px;font-weight:700;color:#1a1a1a;display:flex;align-items:center;gap:8px}\
.kg-widget-title-icon{font-size:20px}\
.kg-widget-more{font-size:13px;color:#666;text-decoration:none;display:flex;align-items:center;gap:4px;transition:color .2s}\
.kg-widget-more:hover{color:#e74c3c}\
.kg-carousel-wrap{position:relative;overflow:hidden}\
.kg-carousel{display:flex;gap:10px;overflow-x:auto;scroll-snap-type:x mandatory;-webkit-overflow-scrolling:touch;scrollbar-width:none;padding:4px 0}\
.kg-carousel::-webkit-scrollbar{display:none}\
.kg-card{flex:0 0 140px;scroll-snap-align:start;border-radius:12px;overflow:hidden;position:relative;cursor:pointer;background:#000;aspect-ratio:9/16;transition:transform .3s,box-shadow .3s}\
.kg-card:hover{transform:translateY(-3px);box-shadow:0 6px 20px rgba(0,0,0,.15)}\
.kg-card-thumb{width:100%;height:100%;object-fit:cover}\
.kg-card-overlay{position:absolute;bottom:0;left:0;right:0;padding:30px 8px 8px;background:linear-gradient(transparent,rgba(0,0,0,.8));pointer-events:none}\
.kg-card-title{color:#fff;font-size:11px;font-weight:600;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:4px;text-shadow:0 1px 2px rgba(0,0,0,.5)}\
.kg-card-stats{display:flex;align-items:center;gap:8px;color:rgba(255,255,255,.8);font-size:10px}\
.kg-play-btn{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:40px;height:40px;background:rgba(255,255,255,.9);border-radius:50%;display:flex;align-items:center;justify-content:center;opacity:.85}\
.kg-play-icon{width:0;height:0;border-style:solid;border-width:8px 0 8px 14px;border-color:transparent transparent transparent #1a1a1a;margin-left:2px}\
.kg-card-more{flex:0 0 140px;scroll-snap-align:start;border-radius:12px;overflow:hidden;cursor:pointer;background:linear-gradient(135deg,#f8f8f8,#e8e8e8);aspect-ratio:9/16;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;transition:transform .3s;text-decoration:none}\
.kg-card-more:hover{transform:translateY(-3px)}\
.kg-card-more-icon{width:40px;height:40px;border-radius:50%;background:#e74c3c;display:flex;align-items:center;justify-content:center}\
.kg-card-more-arrow{color:#fff;font-size:22px;font-weight:bold}\
.kg-card-more-text{color:#333;font-size:12px;font-weight:600}\
\
/* === Fullscreen TikTok Player === */\
.kg-fs-overlay{position:fixed;top:0;left:0;right:0;bottom:0;background:#000;z-index:999999;display:flex;flex-direction:column}\
.kg-fs-overlay *{box-sizing:border-box}\
.kg-fs-container{position:relative;width:100%;height:100%;overflow:hidden}\
.kg-fs-slide{position:absolute;top:0;left:0;width:100%;height:100%;display:flex;align-items:center;justify-content:center;transition:transform .35s cubic-bezier(.25,.46,.45,.94)}\
.kg-fs-video{width:100%;height:100%;object-fit:contain;background:#000}\
.kg-fs-iframe{width:100%;height:100%;border:none;background:#000}\
\
/* Top bar */\
.kg-fs-topbar{position:absolute;top:0;left:0;right:0;padding:12px 16px;display:flex;align-items:center;justify-content:space-between;z-index:20;background:linear-gradient(rgba(0,0,0,.4),transparent);pointer-events:none}\
.kg-fs-topbar>*{pointer-events:auto}\
.kg-fs-brand{color:#fff;font-size:14px;font-weight:700;letter-spacing:2px;text-shadow:0 1px 4px rgba(0,0,0,.5)}\
\
/* Close button */\
.kg-fs-close{width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.15);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);border:none;cursor:pointer;color:#fff;font-size:20px;display:flex;align-items:center;justify-content:center;transition:background .2s}\
.kg-fs-close:hover{background:rgba(255,255,255,.3)}\
\
/* Right side action buttons */\
.kg-fs-actions{position:absolute;right:10px;bottom:220px;display:flex;flex-direction:column;align-items:center;gap:20px;z-index:20}\
.kg-fs-action{display:flex;flex-direction:column;align-items:center;gap:3px;cursor:pointer;-webkit-tap-highlight-color:transparent}\
.kg-fs-action-icon{width:46px;height:46px;border-radius:50%;background:rgba(255,255,255,.12);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);display:flex;align-items:center;justify-content:center;transition:background .2s,transform .15s}\
.kg-fs-action-icon:active{transform:scale(.88)}\
.kg-fs-action-icon.liked{background:rgba(254,44,85,.25)}\
.kg-fs-action-icon.bookmarked{background:rgba(255,200,0,.25)}\
.kg-fs-action-icon svg{width:24px;height:24px;fill:#fff;transition:fill .2s}\
.kg-fs-action-icon.liked svg{fill:#fe2c55}\
.kg-fs-action-icon.bookmarked svg{fill:#ffc800}\
.kg-fs-action-label{color:#fff;font-size:11px;font-weight:500;text-shadow:0 1px 3px rgba(0,0,0,.5)}\
\
/* Bottom info area */\
.kg-fs-bottom{position:absolute;bottom:0;left:0;right:0;z-index:15;background:linear-gradient(transparent,rgba(0,0,0,.6));padding-top:40px;pointer-events:none}\
.kg-fs-bottom>*{pointer-events:auto}\
.kg-fs-info{padding:0 68px 8px 16px}\
.kg-fs-info-title{color:#fff;font-size:15px;font-weight:700;margin-bottom:4px;text-shadow:0 1px 4px rgba(0,0,0,.6);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}\
.kg-fs-info-desc{color:rgba(255,255,255,.75);font-size:12px;line-height:1.4;display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;overflow:hidden;text-shadow:0 1px 3px rgba(0,0,0,.5)}\
\
/* Product card (Dr.Kozu / TikTok Shop style) */\
.kg-fs-product{margin:8px 16px 16px;padding:10px 12px;background:rgba(255,255,255,.12);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border-radius:12px;display:flex;align-items:center;gap:10px;cursor:pointer;transition:background .2s;border:1px solid rgba(255,255,255,.1)}\
.kg-fs-product:active{background:rgba(255,255,255,.2)}\
.kg-fs-product-img{width:52px;height:52px;border-radius:8px;object-fit:cover;flex-shrink:0;background:#333}\
.kg-fs-product-info{flex:1;min-width:0}\
.kg-fs-product-name{color:#fff;font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}\
.kg-fs-product-meta{display:flex;align-items:center;gap:6px;margin-top:2px}\
.kg-fs-product-stars{color:#ffc800;font-size:11px}\
.kg-fs-product-reviews{color:rgba(255,255,255,.5);font-size:11px}\
.kg-fs-product-price-row{display:flex;align-items:center;gap:6px;margin-top:2px}\
.kg-fs-product-price{color:#ff6b6b;font-size:15px;font-weight:700}\
.kg-fs-product-sold{color:rgba(255,255,255,.45);font-size:10px}\
.kg-fs-cart-btn{flex-shrink:0;padding:10px 16px;background:linear-gradient(135deg,#fe2c55,#e91e63);color:#fff;border:none;border-radius:20px;font-size:12px;font-weight:700;cursor:pointer;transition:transform .2s,box-shadow .2s;white-space:nowrap;display:flex;align-items:center;gap:4px}\
.kg-fs-cart-btn:active{transform:scale(.95)}\
.kg-fs-cart-btn svg{width:16px;height:16px;fill:#fff}\
\
/* Mute toggle */\
.kg-fs-mute{position:absolute;right:10px;top:60px;width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.12);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;z-index:20;transition:background .2s}\
.kg-fs-mute svg{width:20px;height:20px;fill:#fff}\
.kg-fs-mute:active{background:rgba(255,255,255,.25)}\
\
/* Search button */\
.kg-fs-search{position:absolute;right:10px;bottom:160px;width:46px;height:46px;border-radius:50%;background:rgba(254,44,85,.8);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;z-index:20;box-shadow:0 4px 16px rgba(254,44,85,.4);transition:transform .2s}\
.kg-fs-search svg{width:24px;height:24px;fill:#fff}\
.kg-fs-search:active{transform:scale(.9)}\
\
/* Swipe hint */\
.kg-fs-swipe-hint{position:absolute;bottom:100px;left:50%;transform:translateX(-50%);color:rgba(255,255,255,.4);font-size:11px;text-align:center;z-index:20;animation:kg-bounce 2s infinite;pointer-events:none}\
@keyframes kg-bounce{0%,100%{transform:translateX(-50%) translateY(0)}50%{transform:translateX(-50%) translateY(-6px)}}\
\
/* Comment Panel */\
.kg-comment-panel{position:absolute;bottom:0;left:0;right:0;height:55vh;background:rgba(18,18,18,.97);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border-radius:16px 16px 0 0;z-index:30;transform:translateY(100%);transition:transform .35s cubic-bezier(.25,.46,.45,.94);display:flex;flex-direction:column}\
.kg-comment-panel.open{transform:translateY(0)}\
.kg-comment-header{display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid rgba(255,255,255,.08);flex-shrink:0}\
.kg-comment-header-title{color:#fff;font-size:15px;font-weight:700}\
.kg-comment-header-close{background:none;border:none;color:rgba(255,255,255,.5);font-size:22px;cursor:pointer;padding:4px}\
.kg-comment-list{flex:1;overflow-y:auto;padding:12px 16px;-webkit-overflow-scrolling:touch}\
.kg-comment-item{display:flex;gap:10px;margin-bottom:16px}\
.kg-comment-avatar{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#667eea,#764ba2);display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px;font-weight:600;flex-shrink:0}\
.kg-comment-body{flex:1;min-width:0}\
.kg-comment-nick{color:rgba(255,255,255,.5);font-size:12px;margin-bottom:2px}\
.kg-comment-text{color:#fff;font-size:13px;line-height:1.4;word-break:break-word}\
.kg-comment-time{color:rgba(255,255,255,.3);font-size:10px;margin-top:3px}\
.kg-comment-empty{color:rgba(255,255,255,.35);text-align:center;padding:40px 0;font-size:14px}\
.kg-comment-input-wrap{display:flex;gap:8px;padding:12px 16px;border-top:1px solid rgba(255,255,255,.08);flex-shrink:0;background:rgba(18,18,18,.97)}\
.kg-comment-input{flex:1;padding:10px 14px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);border-radius:20px;color:#fff;font-size:13px;outline:none;transition:border-color .2s}\
.kg-comment-input:focus{border-color:rgba(255,255,255,.25)}\
.kg-comment-input::placeholder{color:rgba(255,255,255,.3)}\
.kg-comment-send{padding:10px 18px;background:linear-gradient(135deg,#fe2c55,#e91e63);color:#fff;border:none;border-radius:20px;font-size:13px;font-weight:600;cursor:pointer;transition:opacity .2s;white-space:nowrap}\
.kg-comment-send:disabled{opacity:.35;cursor:default}\
\
/* Like animation */\
@keyframes kg-like-pop{0%{transform:scale(1)}25%{transform:scale(1.3)}50%{transform:scale(.95)}100%{transform:scale(1)}}\
.kg-like-anim{animation:kg-like-pop .4s ease}\
\
/* Toast */\
.kg-toast{position:fixed;bottom:100px;left:50%;transform:translateX(-50%);background:rgba(255,255,255,.92);color:#333;padding:10px 24px;border-radius:20px;font-size:13px;font-weight:600;z-index:1000001;opacity:0;transition:opacity .3s;pointer-events:none;box-shadow:0 4px 16px rgba(0,0,0,.2)}\
.kg-toast.show{opacity:1}\
\
/* Responsive */\
@media(max-width:768px){\
  .kg-card{flex:0 0 120px}\
  .kg-card-more{flex:0 0 120px}\
  .kg-card-title{font-size:10px}\
  .kg-widget-title{font-size:15px}\
  .kg-fs-actions{right:8px;bottom:200px;gap:16px}\
  .kg-fs-action-icon{width:42px;height:42px}\
  .kg-fs-action-icon svg{width:22px;height:22px}\
  .kg-fs-info{padding:0 60px 6px 12px}\
  .kg-fs-product{margin:6px 12px 12px}\
  .kg-fs-search{right:8px;bottom:148px;width:42px;height:42px}\
  .kg-fs-search svg{width:22px;height:22px}\
}\
';
        document.head.appendChild(css);
    }

    // ===== SVG Icons =====
    var ICONS = {
        heart: '<svg viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>',
        heartFilled: '<svg viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>',
        comment: '<svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>',
        share: '<svg viewBox="0 0 24 24"><path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92s2.92-1.31 2.92-2.92-1.31-2.92-2.92-2.92z"/></svg>',
        bookmark: '<svg viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>',
        search: '<svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>',
        volumeOn: '<svg viewBox="0 0 24 24"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/></svg>',
        volumeOff: '<svg viewBox="0 0 24 24"><path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"/></svg>',
        cart: '<svg viewBox="0 0 24 24"><path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49A1.003 1.003 0 0020 4H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/></svg>',
        close: '<svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>'
    };

    // ===== Utility =====
    function formatNumber(n) {
        n = parseInt(n) || 0;
        if (n >= 10000) return (n / 10000).toFixed(1) + '萬';
        if (n >= 1000) return (n / 1000).toFixed(1) + 'K';
        return n.toString();
    }

    function extractYoutubeId(url) {
        if (!url) return null;
        var m = url.match(/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([a-zA-Z0-9_-]{11})/);
        return m ? m[1] : null;
    }

    function timeAgo(dateStr) {
        var d = new Date(dateStr);
        var now = new Date();
        var diff = Math.floor((now - d) / 1000);
        if (diff < 60) return '剛剛';
        if (diff < 3600) return Math.floor(diff / 60) + '分鐘前';
        if (diff < 86400) return Math.floor(diff / 3600) + '小時前';
        if (diff < 2592000) return Math.floor(diff / 86400) + '天前';
        return Math.floor(diff / 2592000) + '個月前';
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function showToast(msg) {
        var t = document.createElement('div');
        t.className = 'kg-toast';
        t.textContent = msg;
        document.body.appendChild(t);
        setTimeout(function() { t.classList.add('show'); }, 10);
        setTimeout(function() { t.classList.remove('show'); setTimeout(function() { t.remove(); }, 300); }, 2000);
    }

    // ===== API =====
    function apiGet(url, cb) {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', url);
        xhr.onload = function() { try { cb(JSON.parse(xhr.responseText)); } catch(e) { cb(null); } };
        xhr.onerror = function() { cb(null); };
        xhr.send();
    }

    function apiPost(action, data, cb) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', FEED_API + '?action=' + action);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onload = function() { try { var r = JSON.parse(xhr.responseText); cb && cb(r); } catch(e) { cb && cb(null); } };
        xhr.onerror = function() { cb && cb(null); };
        xhr.send(JSON.stringify(data));
    }

    function fetchProductVideos(productId, cb) {
        apiGet(FEED_API + '?action=product_videos&product_id=' + productId, function(res) {
            cb(res && res.success ? res.data : []);
        });
    }

    // ===== Carousel Widget =====
    function renderCarousel(container, videos) {
        if (!videos || videos.length === 0) return;
        allVideos = videos;

        var html = '<div class="kg-widget">';
        html += '<div class="kg-widget-header">';
        html += '<div class="kg-widget-title"><span class="kg-widget-title-icon">&#9654;</span> 相關影片</div>';
        html += '<a href="' + FEED_URL + '" class="kg-widget-more">查看更多 &#8250;</a>';
        html += '</div>';
        html += '<div class="kg-carousel-wrap"><div class="kg-carousel">';

        videos.forEach(function(v, i) {
            var thumb = v.thumbnail || '';
            html += '<div class="kg-card" data-index="' + i + '">';
            html += '<img class="kg-card-thumb" src="' + escapeHtml(thumb) + '" alt="" loading="lazy">';
            html += '<div class="kg-play-btn"><div class="kg-play-icon"></div></div>';
            html += '<div class="kg-card-overlay">';
            html += '<div class="kg-card-title">' + escapeHtml(v.display_title || v.title) + '</div>';
            html += '<div class="kg-card-stats">';
            html += '<span>&#128065; ' + (v.formatted_views || '0') + '</span>';
            html += '<span>&#10084; ' + (v.formatted_likes || '0') + '</span>';
            html += '</div></div></div>';
        });

        html += '<a href="' + FEED_URL + '" class="kg-card-more">';
        html += '<div class="kg-card-more-icon"><span class="kg-card-more-arrow">&#8250;</span></div>';
        html += '<div class="kg-card-more-text">更多影片</div></a>';
        html += '</div></div></div>';

        container.innerHTML = html;

        // Click handlers
        container.querySelectorAll('.kg-card').forEach(function(card) {
            card.addEventListener('click', function() {
                var idx = parseInt(this.dataset.index);
                openFullscreen(idx);
            });
        });
    }

    // ===== Fullscreen Player =====
    function openFullscreen(index) {
        currentIndex = index;
        isMuted = false; // ユーザータップ後なので音声ON
        var overlay = document.createElement('div');
        overlay.className = 'kg-fs-overlay';
        overlay.id = 'kg-fs-overlay';

        // Build HTML
        var html = '';
        // Top bar
        html += '<div class="kg-fs-topbar">';
        html += '<div class="kg-fs-brand">KYOGOKU</div>';
        html += '<button class="kg-fs-close" id="kg-fs-close">' + ICONS.close + '</button>';
        html += '</div>';

        // Mute button
        html += '<button class="kg-fs-mute" id="kg-fs-mute">' + (isMuted ? ICONS.volumeOff : ICONS.volumeOn) + '</button>';

        // Video container
        html += '<div class="kg-fs-container" id="kg-fs-container"></div>';

        // Right side actions
        html += '<div class="kg-fs-actions" id="kg-fs-actions"></div>';

        // Search button
        html += '<button class="kg-fs-search" id="kg-fs-search">' + ICONS.search + '</button>';

        // Bottom info + product
        html += '<div class="kg-fs-bottom" id="kg-fs-bottom"></div>';

        // Comment panel
        html += '<div class="kg-comment-panel" id="kg-comment-panel">';
        html += '<div class="kg-comment-header"><span class="kg-comment-header-title">評論</span><button class="kg-comment-header-close" id="kg-comment-close">&times;</button></div>';
        html += '<div class="kg-comment-list" id="kg-comment-list"></div>';
        html += '<div class="kg-comment-input-wrap">';
        html += '<input class="kg-comment-input" id="kg-comment-input" placeholder="留下你的評論..." maxlength="500">';
        html += '<button class="kg-comment-send" id="kg-comment-send">發送</button>';
        html += '</div></div>';

        // Swipe hint
        html += '<div class="kg-fs-swipe-hint" id="kg-fs-swipe-hint">&#8593; 上滑看下一個影片</div>';

        overlay.innerHTML = html;
        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';

        // Load current video
        loadSlide(currentIndex);

        // Bind events
        bindFullscreenEvents(overlay);

        // Record view
        apiPost('view', { video_id: allVideos[currentIndex].id });

        // Hide swipe hint after 3s
        setTimeout(function() {
            var hint = document.getElementById('kg-fs-swipe-hint');
            if (hint) hint.style.display = 'none';
        }, 3000);
    }

    function loadSlide(index) {
        var video = allVideos[index];
        if (!video) return;

        var container = document.getElementById('kg-fs-container');
        var slide = document.createElement('div');
        slide.className = 'kg-fs-slide';
        slide.id = 'kg-fs-slide-' + index;

        if (video.is_uploaded && video.video_file_url) {
            slide.innerHTML = '<video class="kg-fs-video" src="' + escapeHtml(video.video_file_url) + '" playsinline loop ' + (isMuted ? 'muted' : '') + ' autoplay></video>';
        } else if (video.youtube_id) {
            slide.innerHTML = '<iframe class="kg-fs-iframe" src="https://www.youtube.com/embed/' + video.youtube_id + '?autoplay=1&mute=' + (isMuted ? 1 : 0) + '&loop=1&playlist=' + video.youtube_id + '&playsinline=1&controls=0&rel=0&modestbranding=1" allow="autoplay; encrypted-media" allowfullscreen></iframe>';
        }

        // Remove old slides
        container.innerHTML = '';
        container.appendChild(slide);

        // Update actions
        updateActions(video);
        // Update bottom info
        updateBottom(video);
    }

    function updateActions(video) {
        var el = document.getElementById('kg-fs-actions');
        if (!el) return;

        var html = '';
        // Like
        html += '<div class="kg-fs-action" data-action="like">';
        html += '<div class="kg-fs-action-icon' + (video.has_liked ? ' liked' : '') + '" id="kg-like-icon">' + (video.has_liked ? ICONS.heartFilled : ICONS.heart) + '</div>';
        html += '<span class="kg-fs-action-label" id="kg-like-count">' + formatNumber(video.like_count) + '</span>';
        html += '</div>';

        // Comment
        html += '<div class="kg-fs-action" data-action="comment">';
        html += '<div class="kg-fs-action-icon">' + ICONS.comment + '</div>';
        html += '<span class="kg-fs-action-label" id="kg-comment-count">' + formatNumber(video.comment_count || 0) + '</span>';
        html += '</div>';

        // Bookmark
        html += '<div class="kg-fs-action" data-action="bookmark">';
        html += '<div class="kg-fs-action-icon' + (video.has_bookmarked ? ' bookmarked' : '') + '" id="kg-bookmark-icon">' + ICONS.bookmark + '</div>';
        html += '<span class="kg-fs-action-label" id="kg-bookmark-count">' + formatNumber(video.bookmark_count || 0) + '</span>';
        html += '</div>';

        // Share
        html += '<div class="kg-fs-action" data-action="share">';
        html += '<div class="kg-fs-action-icon">' + ICONS.share + '</div>';
        html += '<span class="kg-fs-action-label">分享</span>';
        html += '</div>';

        el.innerHTML = html;

        // Bind action clicks
        el.querySelectorAll('.kg-fs-action').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                var action = this.dataset.action;
                handleAction(action);
            });
        });
    }

    function updateBottom(video) {
        var el = document.getElementById('kg-fs-bottom');
        if (!el) return;

        var html = '<div class="kg-fs-info">';
        html += '<div class="kg-fs-info-title">' + escapeHtml(video.display_title || video.title) + '</div>';
        if (video.display_description) {
            html += '<div class="kg-fs-info-desc">' + escapeHtml(video.display_description) + '</div>';
        }
        html += '</div>';

        // Product card
        if (video.products && video.products.length > 0) {
            var p = video.products[0];
            var price = p.price02_inc_tax ? 'NT$' + Number(p.price02_inc_tax).toLocaleString() : '';
            var stars = '';
            var rating = parseFloat(p.avg_rating) || 4.5;
            for (var s = 0; s < 5; s++) {
                stars += s < Math.round(rating) ? '&#9733;' : '&#9734;';
            }

            html += '<div class="kg-fs-product" data-url="' + escapeHtml(p.product_url || '') + '">';
            html += '<img class="kg-fs-product-img" src="' + escapeHtml(p.image_url || '') + '" alt="" onerror="this.style.display=\'none\'">';
            html += '<div class="kg-fs-product-info">';
            html += '<div class="kg-fs-product-name">' + escapeHtml(p.name || '') + '</div>';
            html += '<div class="kg-fs-product-meta">';
            html += '<span class="kg-fs-product-stars">' + stars + '</span>';
            if (p.review_count) html += '<span class="kg-fs-product-reviews">(' + p.review_count + ')</span>';
            html += '</div>';
            html += '<div class="kg-fs-product-price-row">';
            html += '<span class="kg-fs-product-price">' + price + '</span>';
            html += '</div>';
            html += '</div>';
            html += '<button class="kg-fs-cart-btn" data-product-id="' + p.id + '">' + ICONS.cart + ' 加購物車</button>';
            html += '</div>';
        }

        el.innerHTML = html;

        // Product card click -> go to product page
        var productCard = el.querySelector('.kg-fs-product');
        if (productCard) {
            productCard.addEventListener('click', function(e) {
                if (e.target.closest('.kg-fs-cart-btn')) return;
                var url = this.dataset.url;
                if (url) window.location.href = url;
            });
        }

        // Cart button
        var cartBtn = el.querySelector('.kg-fs-cart-btn');
        if (cartBtn) {
            cartBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                var productId = this.dataset.productId;
                addToCart(productId);
            });
        }
    }

    // ===== Actions =====
    function handleAction(action) {
        var video = allVideos[currentIndex];
        if (!video) return;

        switch (action) {
            case 'like':
                apiPost('like', { video_id: video.id }, function(res) {
                    if (res && res.success) {
                        video.has_liked = res.liked;
                        video.like_count = res.like_count;
                        var icon = document.getElementById('kg-like-icon');
                        var count = document.getElementById('kg-like-count');
                        if (icon) {
                            icon.className = 'kg-fs-action-icon' + (res.liked ? ' liked' : '');
                            icon.innerHTML = res.liked ? ICONS.heartFilled : ICONS.heart;
                            icon.classList.add('kg-like-anim');
                            setTimeout(function() { icon.classList.remove('kg-like-anim'); }, 400);
                        }
                        if (count) count.textContent = formatNumber(res.like_count);
                    }
                });
                break;

            case 'comment':
                toggleCommentPanel(true);
                break;

            case 'bookmark':
                apiPost('bookmark', { video_id: video.id }, function(res) {
                    if (res && res.success) {
                        video.has_bookmarked = res.bookmarked;
                        video.bookmark_count = res.bookmark_count;
                        var icon = document.getElementById('kg-bookmark-icon');
                        var count = document.getElementById('kg-bookmark-count');
                        if (icon) {
                            icon.className = 'kg-fs-action-icon' + (res.bookmarked ? ' bookmarked' : '');
                            icon.classList.add('kg-like-anim');
                            setTimeout(function() { icon.classList.remove('kg-like-anim'); }, 400);
                        }
                        if (count) count.textContent = formatNumber(res.bookmark_count);
                        showToast(res.bookmarked ? '已收藏' : '已取消收藏');
                    }
                });
                break;

            case 'share':
                var shareUrl = window.location.origin + FEED_URL + '#video-' + video.id;
                if (navigator.share) {
                    navigator.share({ title: video.display_title || video.title, url: shareUrl }).catch(function(){});
                } else if (navigator.clipboard) {
                    navigator.clipboard.writeText(shareUrl).then(function() { showToast('連結已複製'); });
                } else {
                    showToast('分享連結: ' + shareUrl);
                }
                apiPost('analytics', { video_id: video.id, action_type: 'share' });
                break;
        }
    }

    // ===== Comment Panel =====
    function toggleCommentPanel(open) {
        var panel = document.getElementById('kg-comment-panel');
        if (!panel) return;
        isCommentOpen = open;
        if (open) {
            panel.classList.add('open');
            loadComments();
        } else {
            panel.classList.remove('open');
        }
    }

    function loadComments() {
        var video = allVideos[currentIndex];
        if (!video) return;
        var list = document.getElementById('kg-comment-list');
        if (!list) return;
        list.innerHTML = '<div class="kg-comment-empty">載入中...</div>';

        apiGet(FEED_API + '?action=comments&video_id=' + video.id, function(res) {
            if (!res || !res.success || res.data.length === 0) {
                list.innerHTML = '<div class="kg-comment-empty">還沒有評論，來搶沙發吧！</div>';
                return;
            }
            var html = '';
            res.data.forEach(function(c) {
                var initial = (c.nickname || '訪')[0].toUpperCase();
                html += '<div class="kg-comment-item">';
                html += '<div class="kg-comment-avatar">' + escapeHtml(initial) + '</div>';
                html += '<div class="kg-comment-body">';
                html += '<div class="kg-comment-nick">' + escapeHtml(c.nickname || '訪客') + '</div>';
                html += '<div class="kg-comment-text">' + escapeHtml(c.comment_text) + '</div>';
                html += '<div class="kg-comment-time">' + timeAgo(c.created_at) + '</div>';
                html += '</div></div>';
            });
            list.innerHTML = html;
        });
    }

    function submitComment() {
        var input = document.getElementById('kg-comment-input');
        var text = input ? input.value.trim() : '';
        if (!text) return;

        var video = allVideos[currentIndex];
        if (!video) return;

        var sendBtn = document.getElementById('kg-comment-send');
        if (sendBtn) sendBtn.disabled = true;

        apiPost('comment', { video_id: video.id, nickname: '', comment_text: text }, function(res) {
            if (sendBtn) sendBtn.disabled = false;
            if (res && res.success) {
                input.value = '';
                loadComments();
                video.comment_count = res.comment_count;
                var countEl = document.getElementById('kg-comment-count');
                if (countEl) countEl.textContent = formatNumber(res.comment_count);
            }
        });
    }

    // ===== Add to Cart (EC-CUBE) =====
    function addToCart(productId) {
        var formData = new FormData();
        formData.append('product_id', productId);
        formData.append('quantity', '1');
        formData.append('mode', 'add_cart');

        // Get CSRF token from page
        var token = document.querySelector('meta[name="csrf-token"]');
        if (token) formData.append('_token', token.getAttribute('content'));

        // Try EC-CUBE cart API
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/products/add_cart/' + productId);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 400) {
                showToast('已加入購物車！');
                // Update cart count in header if exists
                try {
                    var cartBadge = document.querySelector('.ec-cartNavi__badge');
                    if (cartBadge) {
                        var cnt = parseInt(cartBadge.textContent) || 0;
                        cartBadge.textContent = cnt + 1;
                    }
                } catch(e) {}
            } else {
                showToast('加入購物車失敗，請重試');
            }
        };
        xhr.onerror = function() { showToast('網路錯誤，請重試'); };
        xhr.send(formData);
    }

    // ===== Fullscreen Events =====
    function bindFullscreenEvents(overlay) {
        // Close button
        document.getElementById('kg-fs-close').addEventListener('click', closeFullscreen);

        // Mute toggle
        document.getElementById('kg-fs-mute').addEventListener('click', function() {
            isMuted = !isMuted;
            this.innerHTML = isMuted ? ICONS.volumeOff : ICONS.volumeOn;
            var vid = overlay.querySelector('video');
            if (vid) vid.muted = isMuted;
            var iframe = overlay.querySelector('iframe');
            if (iframe) {
                var src = iframe.src;
                iframe.src = src.replace(/mute=\d/, 'mute=' + (isMuted ? 1 : 0));
            }
        });

        // Search button
        document.getElementById('kg-fs-search').addEventListener('click', function() {
            window.location.href = '/products/list';
        });

        // Comment panel close
        document.getElementById('kg-comment-close').addEventListener('click', function() {
            toggleCommentPanel(false);
        });

        // Comment send
        document.getElementById('kg-comment-send').addEventListener('click', submitComment);
        document.getElementById('kg-comment-input').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); submitComment(); }
        });

        // Touch swipe for video navigation
        var container = document.getElementById('kg-fs-container');
        container.addEventListener('touchstart', function(e) {
            if (isCommentOpen) return;
            touchStartY = e.touches[0].clientY;
            isSwiping = true;
        }, { passive: true });

        container.addEventListener('touchmove', function(e) {
            if (!isSwiping || isCommentOpen) return;
            touchDeltaY = e.touches[0].clientY - touchStartY;
        }, { passive: true });

        container.addEventListener('touchend', function() {
            if (!isSwiping || isCommentOpen) return;
            isSwiping = false;
            if (Math.abs(touchDeltaY) > 60) {
                if (touchDeltaY < 0 && currentIndex < allVideos.length - 1) {
                    // Swipe up -> next
                    navigateVideo(1);
                } else if (touchDeltaY > 0 && currentIndex > 0) {
                    // Swipe down -> prev
                    navigateVideo(-1);
                }
            }
            touchDeltaY = 0;
        });

        // Keyboard navigation
        overlay.addEventListener('keydown', function(e) {
            if (isCommentOpen) return;
            if (e.key === 'ArrowDown' && currentIndex < allVideos.length - 1) {
                navigateVideo(1);
            } else if (e.key === 'ArrowUp' && currentIndex > 0) {
                navigateVideo(-1);
            } else if (e.key === 'Escape') {
                closeFullscreen();
            }
        });
        overlay.setAttribute('tabindex', '0');
        overlay.focus();

        // Click on video to toggle play/pause (for uploaded videos)
        container.addEventListener('click', function(e) {
            if (e.target.closest('.kg-fs-action, .kg-fs-product, .kg-fs-cart-btn, .kg-fs-search')) return;
            var vid = container.querySelector('video');
            if (vid) {
                if (vid.paused) vid.play();
                else vid.pause();
            }
        });
    }

    function navigateVideo(direction) {
        var newIndex = currentIndex + direction;
        if (newIndex < 0 || newIndex >= allVideos.length) return;

        // Stop current video
        var oldSlide = document.getElementById('kg-fs-slide-' + currentIndex);
        if (oldSlide) {
            var vid = oldSlide.querySelector('video');
            if (vid) vid.pause();
        }

        currentIndex = newIndex;
        loadSlide(currentIndex);

        // Record view
        apiPost('view', { video_id: allVideos[currentIndex].id });

        // Close comment panel if open
        if (isCommentOpen) toggleCommentPanel(false);
    }

    function closeFullscreen() {
        var overlay = document.getElementById('kg-fs-overlay');
        if (overlay) {
            // Stop video
            var vid = overlay.querySelector('video');
            if (vid) vid.pause();
            overlay.remove();
        }
        document.body.style.overflow = '';
        isCommentOpen = false;
    }

    // ===== Initialize =====
    function init() {
        injectStyles();

        // Find product ID from URL
        var match = window.location.pathname.match(/\/products\/detail\/(\d+)/);
        if (!match) return;
        var productId = match[1];

        // Find or create widget container
        var container = document.getElementById('kg-video-widget');
        if (!container) {
            // Try to insert after cart form
            var cartForm = document.querySelector('.ec-productRole__btn form, form[action*="add_cart"]');
            if (cartForm) {
                container = document.createElement('div');
                container.id = 'kg-video-widget';
                cartForm.parentNode.insertBefore(container, cartForm.nextSibling);
            } else {
                // Fallback: insert before free area
                var freeArea = document.querySelector('.ec-productRole__description');
                if (freeArea) {
                    container = document.createElement('div');
                    container.id = 'kg-video-widget';
                    freeArea.parentNode.insertBefore(container, freeArea);
                }
            }
        }
        if (!container) return;

        // Fetch videos for this product
        fetchProductVideos(productId, function(videos) {
            if (videos.length > 0) {
                renderCarousel(container, videos);
            }
        });
    }

    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
