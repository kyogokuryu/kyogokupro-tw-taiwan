/**
 * KYOGOKU Feed Video Widget - TikTok/Dr.Kozu Style
 * 商品詳細ページ用：カルーセル表示 + 全画面TikTok風プレイヤー
 * 
 * Features:
 * - Fullscreen modal with vertical swipe navigation
 * - Right-side action buttons (Like, Comment, Bookmark, Share)
 * - Bottom product card with Ajax "加購物車" button (no page reload)
 * - Comment panel (slide-up)
 * - In-app search panel overlay (Dr.Kozu style, no page navigation)
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
    var isSearchOpen = false;
    var isMuted = false; // タップで開くので音声ON
    var isLoadingMore = false;
    var allVideoIds = new Set(); // Track loaded video IDs to avoid duplicates
    var initialProductVideos = []; // Videos from the current product page

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
.kg-fs-cart-btn:disabled{opacity:.6;transform:none}\
.kg-fs-cart-btn svg{width:16px;height:16px;fill:#fff}\
.kg-fs-cart-btn.added{background:linear-gradient(135deg,#4CAF50,#45a049)}\
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
.kg-comment-send{padding:10px 18px;background:linear-gradient(135deg,#fe2c55,#e91e63);border:none;border-radius:20px;color:#fff;font-size:13px;font-weight:600;cursor:pointer;transition:opacity .2s}\
.kg-comment-send:disabled{opacity:.5}\
\
/* Like animation */\
@keyframes kg-like-pop{0%{transform:scale(1)}25%{transform:scale(1.3)}50%{transform:scale(.95)}100%{transform:scale(1)}}\
.kg-like-anim{animation:kg-like-pop .4s ease}\
@keyframes kg-spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}\
\
/* Toast */\
.kg-toast{position:fixed;bottom:100px;left:50%;transform:translateX(-50%);background:rgba(255,255,255,.92);color:#333;padding:10px 24px;border-radius:20px;font-size:13px;font-weight:600;z-index:1000001;opacity:0;transition:opacity .3s;pointer-events:none;box-shadow:0 4px 16px rgba(0,0,0,.2)}\
.kg-toast.show{opacity:1}\
\
/* ====== Search Panel (Dr.Kozu Style) ====== */\
.kg-search-panel{position:absolute;top:0;left:0;right:0;bottom:0;background:#fff;z-index:40;transform:translateY(100%);transition:transform .35s cubic-bezier(.25,.46,.45,.94);display:flex;flex-direction:column;overflow:hidden}\
.kg-search-panel.open{transform:translateY(0)}\
.kg-search-topbar{display:flex;align-items:center;gap:10px;padding:12px 16px;background:#fff;border-bottom:1px solid #eee;flex-shrink:0}\
.kg-search-back{width:32px;height:32px;border:none;background:none;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;padding:0}\
.kg-search-back svg{width:22px;height:22px;fill:#333}\
.kg-search-input-wrap{flex:1;display:flex;align-items:center;background:#f5f5f5;border-radius:24px;padding:0 14px;border:1.5px solid #eee;transition:border-color .2s}\
.kg-search-input-wrap:focus-within{border-color:#fe2c55}\
.kg-search-input-wrap svg{width:18px;height:18px;fill:#999;flex-shrink:0}\
.kg-search-input{flex:1;border:none;background:none;padding:10px 8px;font-size:14px;color:#333;outline:none}\
.kg-search-input::placeholder{color:#aaa}\
.kg-search-clear{width:20px;height:20px;border:none;background:rgba(0,0,0,.1);border-radius:50%;cursor:pointer;display:none;align-items:center;justify-content:center;flex-shrink:0;padding:0}\
.kg-search-clear.visible{display:flex}\
.kg-search-clear svg{width:12px;height:12px;fill:#666}\
.kg-search-body{flex:1;overflow-y:auto;-webkit-overflow-scrolling:touch;padding:0}\
\
/* Search sections */\
.kg-search-section{padding:20px 16px 8px}\
.kg-search-section-title{font-size:15px;font-weight:700;color:#333;margin-bottom:14px;display:flex;align-items:center;gap:8px}\
.kg-search-section-icon{font-size:18px}\
\
/* Hot keywords */\
.kg-search-tags{display:flex;flex-wrap:wrap;gap:8px}\
.kg-search-tag{padding:8px 16px;background:#f5f5f5;border-radius:20px;font-size:13px;color:#555;cursor:pointer;transition:all .2s;border:none;white-space:nowrap}\
.kg-search-tag:active,.kg-search-tag:hover{background:#ffe5ea;color:#fe2c55}\
\
/* Category grid */\
.kg-search-cats{display:grid;grid-template-columns:1fr 1fr;gap:10px}\
.kg-search-cat{display:flex;align-items:center;gap:10px;padding:14px 16px;background:#f9f9f9;border-radius:12px;cursor:pointer;transition:all .2s;border:1px solid #eee}\
.kg-search-cat:active,.kg-search-cat:hover{background:#fff5f7;border-color:#fe2c55}\
.kg-search-cat-icon{font-size:22px;flex-shrink:0}\
.kg-search-cat-name{font-size:13px;font-weight:600;color:#333}\
\
/* Search results */\
.kg-search-results{padding:12px 16px}\
.kg-search-results-title{font-size:14px;font-weight:700;color:#333;margin-bottom:12px;display:flex;align-items:center;justify-content:space-between}\
.kg-search-results-count{font-size:12px;color:#999;font-weight:400}\
.kg-search-result-item{display:flex;gap:12px;padding:12px 0;border-bottom:1px solid #f0f0f0;cursor:pointer;transition:background .2s}\
.kg-search-result-item:active{background:#f9f9f9}\
.kg-search-result-img{width:72px;height:72px;border-radius:10px;object-fit:cover;flex-shrink:0;background:#f0f0f0}\
.kg-search-result-info{flex:1;min-width:0;display:flex;flex-direction:column;justify-content:center}\
.kg-search-result-name{font-size:13px;font-weight:600;color:#333;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:4px}\
.kg-search-result-meta{display:flex;align-items:center;gap:6px;margin-bottom:4px}\
.kg-search-result-stars{color:#ffc800;font-size:11px}\
.kg-search-result-reviews{color:#999;font-size:11px}\
.kg-search-result-price{font-size:15px;font-weight:700;color:#fe2c55}\
.kg-search-result-sold{font-size:11px;color:#999;margin-left:6px}\
.kg-search-result-cart{flex-shrink:0;display:flex;align-items:center;justify-content:center}\
.kg-search-result-cart-btn{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#fe2c55,#e91e63);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:transform .2s}\
.kg-search-result-cart-btn:active{transform:scale(.9)}\
.kg-search-result-cart-btn:disabled{opacity:.5;transform:none}\
.kg-search-result-cart-btn svg{width:18px;height:18px;fill:#fff}\
.kg-search-result-cart-btn.added{background:linear-gradient(135deg,#4CAF50,#45a049)}\
\
/* Recommend products */\
.kg-search-recommend{display:grid;grid-template-columns:1fr 1fr;gap:10px}\
.kg-search-rec-item{border-radius:12px;overflow:hidden;cursor:pointer;background:#f9f9f9;border:1px solid #eee;transition:all .2s}\
.kg-search-rec-item:active{border-color:#fe2c55}\
.kg-search-rec-img{width:100%;aspect-ratio:1;object-fit:cover;background:#f0f0f0}\
.kg-search-rec-info{padding:8px 10px}\
.kg-search-rec-name{font-size:12px;font-weight:600;color:#333;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:4px}\
.kg-search-rec-price{font-size:14px;font-weight:700;color:#fe2c55}\
\
/* Loading spinner */\
.kg-search-loading{text-align:center;padding:40px 0;color:#999;font-size:14px}\
.kg-search-empty{text-align:center;padding:40px 0;color:#999;font-size:14px}\
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
        close: '<svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>',
        back: '<svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>',
        check: '<svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>'
    };

    // ===== Category Data =====
    var CATEGORIES = [
        { id: 91, name: '洗髮精', icon: '\uD83E\uDDF4' },
        { id: 78, name: '護髮素', icon: '\uD83D\uDCA7' },
        { id: 80, name: '免沖洗護髮', icon: '\u2728' },
        { id: 89, name: '染髮用品', icon: '\uD83C\uDFA8' },
        { id: 84, name: '美髮造型用品', icon: '\uD83D\uDC87' },
        { id: 85, name: '美容美髮儀器', icon: '\uD83D\uDD0C' },
        { id: 90, name: '醫美清潔保養', icon: '\uD83E\uDDD6' },
        { id: 86, name: '奇蹟角蛋白系列', icon: '\uD83D\uDC8E' }
    ];

    var HOT_KEYWORDS = ['角蛋白', '離子夾', '捲髮棒', '護髮素', '洗髮精', '染髮', '吹風機', '美容液'];

    // ===== Utility =====
    function formatNumber(n) {
        n = parseInt(n) || 0;
        if (n >= 10000) return (n / 10000).toFixed(1) + '\u842C';
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
        if (diff < 60) return '\u525B\u525B';
        if (diff < 3600) return Math.floor(diff / 60) + '\u5206\u9418\u524D';
        if (diff < 86400) return Math.floor(diff / 3600) + '\u5C0F\u6642\u524D';
        if (diff < 2592000) return Math.floor(diff / 86400) + '\u5929\u524D';
        return Math.floor(diff / 2592000) + '\u500B\u6708\u524D';
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

    // Fetch more random videos for infinite scroll
    function fetchMoreVideos(cb) {
        if (isLoadingMore) return;
        isLoadingMore = true;
        var excludeIds = Array.from(allVideoIds).join(',');
        apiGet(FEED_API + '?action=feed_videos&limit=10&exclude_ids=' + excludeIds, function(res) {
            isLoadingMore = false;
            if (res && res.success && res.data && res.data.length > 0) {
                var newVideos = [];
                res.data.forEach(function(v) {
                    if (!allVideoIds.has(v.id)) {
                        allVideoIds.add(v.id);
                        allVideos.push(v);
                        newVideos.push(v);
                    }
                });
                if (cb) cb(newVideos);
            } else {
                if (cb) cb([]);
            }
        });
    }

    // ===== Carousel Widget =====
    function renderCarousel(container, videos) {
        if (!videos || videos.length === 0) return;
        allVideos = videos;
        initialProductVideos = videos.slice(); // Save original product videos
        // Track all loaded video IDs
        allVideoIds = new Set();
        videos.forEach(function(v) { allVideoIds.add(v.id); });

        var html = '<div class="kg-widget">';
        html += '<div class="kg-widget-header">';
        html += '<div class="kg-widget-title"><span class="kg-widget-title-icon">&#9654;</span> \u76F8\u95DC\u5F71\u7247</div>';
        html += '<a href="' + FEED_URL + '" class="kg-widget-more">\u67E5\u770B\u66F4\u591A &#8250;</a>';
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
        html += '<div class="kg-card-more-text">\u66F4\u591A\u5F71\u7247</div></a>';
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
        isMuted = false;
        var overlay = document.createElement('div');
        overlay.className = 'kg-fs-overlay';
        overlay.id = 'kg-fs-overlay';

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
        html += '<div class="kg-comment-header"><span class="kg-comment-header-title">\u8A55\u8AD6</span><button class="kg-comment-header-close" id="kg-comment-close">&times;</button></div>';
        html += '<div class="kg-comment-list" id="kg-comment-list"></div>';
        html += '<div class="kg-comment-input-wrap">';
        html += '<input class="kg-comment-input" id="kg-comment-input" placeholder="\u7559\u4E0B\u4F60\u7684\u8A55\u8AD6..." maxlength="500">';
        html += '<button class="kg-comment-send" id="kg-comment-send">\u767C\u9001</button>';
        html += '</div></div>';

        // Search panel (Dr.Kozu style overlay)
        html += buildSearchPanelHTML();

        // Swipe hint
        html += '<div class="kg-fs-swipe-hint" id="kg-fs-swipe-hint">&#8593; \u4E0A\u6ED1\u770B\u4E0B\u4E00\u500B\u5F71\u7247</div>';

        overlay.innerHTML = html;
        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';

        loadSlide(currentIndex);
        bindFullscreenEvents(overlay);
        apiPost('view', { video_id: allVideos[currentIndex].id });

        // Pre-fetch random videos for infinite scroll
        fetchMoreVideos(function() {
            // Videos loaded in background
        });

        setTimeout(function() {
            var hint = document.getElementById('kg-fs-swipe-hint');
            if (hint) hint.style.display = 'none';
        }, 3000);
    }

    // ===== Search Panel HTML =====
    function buildSearchPanelHTML() {
        var html = '<div class="kg-search-panel" id="kg-search-panel">';
        
        // Top bar with search input
        html += '<div class="kg-search-topbar">';
        html += '<button class="kg-search-back" id="kg-search-back">' + ICONS.back + '</button>';
        html += '<div class="kg-search-input-wrap">';
        html += ICONS.search;
        html += '<input class="kg-search-input" id="kg-search-input" placeholder="\u5546\u54C1\u540D\u3001\u95DC\u9375\u5B57\u641C\u5C0B" autocomplete="off">';
        html += '<button class="kg-search-clear" id="kg-search-clear">' + ICONS.close + '</button>';
        html += '</div>';
        html += '</div>';

        // Scrollable body
        html += '<div class="kg-search-body" id="kg-search-body">';

        // Default content (shown when no search query)
        html += '<div id="kg-search-default">';

        // Hot keywords
        html += '<div class="kg-search-section">';
        html += '<div class="kg-search-section-title"><span class="kg-search-section-icon">\uD83D\uDD25</span> \u71B1\u9580\u641C\u5C0B</div>';
        html += '<div class="kg-search-tags">';
        HOT_KEYWORDS.forEach(function(kw) {
            html += '<button class="kg-search-tag" data-keyword="' + escapeHtml(kw) + '">' + escapeHtml(kw) + '</button>';
        });
        html += '</div></div>';

        // Categories
        html += '<div class="kg-search-section">';
        html += '<div class="kg-search-section-title"><span class="kg-search-section-icon">\uD83C\uDFF7\uFE0F</span> \u5206\u985E\u641C\u5C0B</div>';
        html += '<div class="kg-search-cats">';
        CATEGORIES.forEach(function(cat) {
            html += '<div class="kg-search-cat" data-category-id="' + cat.id + '">';
            html += '<span class="kg-search-cat-icon">' + cat.icon + '</span>';
            html += '<span class="kg-search-cat-name">' + escapeHtml(cat.name) + '</span>';
            html += '</div>';
        });
        html += '</div></div>';

        // Recommend section (will be populated via API)
        html += '<div class="kg-search-section">';
        html += '<div class="kg-search-section-title"><span class="kg-search-section-icon">\u2728</span> \u63A8\u85A6\u5546\u54C1</div>';
        html += '<div class="kg-search-recommend" id="kg-search-recommend">';
        html += '<div class="kg-search-loading">\u8F09\u5165\u4E2D...</div>';
        html += '</div></div>';

        html += '</div>'; // #kg-search-default

        // Results container (shown when searching)
        html += '<div id="kg-search-results" style="display:none"></div>';

        html += '</div>'; // .kg-search-body
        html += '</div>'; // .kg-search-panel

        return html;
    }

    // ===== Search Functions =====
    function openSearchPanel() {
        isSearchOpen = true;
        var panel = document.getElementById('kg-search-panel');
        if (panel) {
            panel.classList.add('open');
            setTimeout(function() {
                var input = document.getElementById('kg-search-input');
                if (input) input.focus();
            }, 400);
        }
        // Load recommended products
        loadRecommendedProducts();
    }

    function closeSearchPanel() {
        isSearchOpen = false;
        var panel = document.getElementById('kg-search-panel');
        if (panel) panel.classList.remove('open');
        // Clear search
        var input = document.getElementById('kg-search-input');
        if (input) input.value = '';
        var clearBtn = document.getElementById('kg-search-clear');
        if (clearBtn) clearBtn.classList.remove('visible');
        // Show default, hide results
        var defaultEl = document.getElementById('kg-search-default');
        var resultsEl = document.getElementById('kg-search-results');
        if (defaultEl) defaultEl.style.display = '';
        if (resultsEl) resultsEl.style.display = 'none';
    }

    function loadRecommendedProducts() {
        var container = document.getElementById('kg-search-recommend');
        if (!container) return;
        
        // Fetch popular products
        fetchSearchResults('', function(products) {
            if (!products || products.length === 0) {
                container.innerHTML = '<div class="kg-search-empty">\u66AB\u7121\u63A8\u85A6\u5546\u54C1</div>';
                return;
            }
            var html = '';
            products.slice(0, 6).forEach(function(p) {
                html += '<div class="kg-search-rec-item" data-url="' + escapeHtml(p.url) + '">';
                html += '<img class="kg-search-rec-img" src="' + escapeHtml(p.image) + '" alt="" loading="lazy" onerror="this.style.background=\'#eee\'">';
                html += '<div class="kg-search-rec-info">';
                html += '<div class="kg-search-rec-name">' + escapeHtml(p.name) + '</div>';
                html += '<div class="kg-search-rec-price">TWD$ ' + escapeHtml(p.price) + '</div>';
                html += '</div></div>';
            });
            container.innerHTML = html;

            // Click handlers
            container.querySelectorAll('.kg-search-rec-item').forEach(function(item) {
                item.addEventListener('click', function() {
                    var url = this.dataset.url;
                    if (url) window.location.href = url;
                });
            });
        });
    }

    function performSearch(query) {
        var defaultEl = document.getElementById('kg-search-default');
        var resultsEl = document.getElementById('kg-search-results');
        
        if (!query || query.trim() === '') {
            if (defaultEl) defaultEl.style.display = '';
            if (resultsEl) resultsEl.style.display = 'none';
            return;
        }

        if (defaultEl) defaultEl.style.display = 'none';
        if (resultsEl) {
            resultsEl.style.display = '';
            resultsEl.innerHTML = '<div class="kg-search-loading">\u641C\u5C0B\u4E2D...</div>';
        }

        fetchSearchResults(query, function(products) {
            if (!resultsEl) return;
            if (!products || products.length === 0) {
                resultsEl.innerHTML = '<div class="kg-search-results"><div class="kg-search-empty">\u627E\u4E0D\u5230\u300C' + escapeHtml(query) + '\u300D\u7684\u76F8\u95DC\u5546\u54C1</div></div>';
                return;
            }
            var html = '<div class="kg-search-results">';
            html += '<div class="kg-search-results-title">\u300C' + escapeHtml(query) + '\u300D\u7684\u641C\u5C0B\u7D50\u679C<span class="kg-search-results-count">' + products.length + '\u500B\u5546\u54C1</span></div>';
            
            products.forEach(function(p) {
                html += '<div class="kg-search-result-item" data-url="' + escapeHtml(p.url) + '" data-product-id="' + escapeHtml(p.id) + '">';
                html += '<img class="kg-search-result-img" src="' + escapeHtml(p.image) + '" alt="" loading="lazy" onerror="this.style.background=\'#eee\'">';
                html += '<div class="kg-search-result-info">';
                html += '<div class="kg-search-result-name">' + escapeHtml(p.name) + '</div>';
                if (p.stars) {
                    html += '<div class="kg-search-result-meta">';
                    html += '<span class="kg-search-result-stars">' + p.stars + '</span>';
                    if (p.reviews) html += '<span class="kg-search-result-reviews">(' + escapeHtml(p.reviews) + ')</span>';
                    html += '</div>';
                }
                html += '<div style="display:flex;align-items:center">';
                html += '<span class="kg-search-result-price">TWD$ ' + escapeHtml(p.price) + '</span>';
                if (p.sold) html += '<span class="kg-search-result-sold">' + escapeHtml(p.sold) + '</span>';
                html += '</div>';
                html += '</div>';
                // Cart button for each result
                if (!p.soldOut) {
                    html += '<div class="kg-search-result-cart">';
                    html += '<button class="kg-search-result-cart-btn" data-product-id="' + escapeHtml(p.id) + '" title="\u52A0\u5165\u8CFC\u7269\u8ECA">' + ICONS.cart + '</button>';
                    html += '</div>';
                }
                html += '</div>';
            });
            html += '</div>';
            resultsEl.innerHTML = html;

            // Bind click events
            resultsEl.querySelectorAll('.kg-search-result-item').forEach(function(item) {
                item.addEventListener('click', function(e) {
                    if (e.target.closest('.kg-search-result-cart-btn')) return;
                    var url = this.dataset.url;
                    if (url) window.location.href = url;
                });
            });

            // Bind cart buttons
            resultsEl.querySelectorAll('.kg-search-result-cart-btn').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    var productId = this.dataset.productId;
                    var btnEl = this;
                    addToCartAjax(productId, function(success) {
                        if (success) {
                            btnEl.classList.add('added');
                            btnEl.innerHTML = ICONS.check;
                            setTimeout(function() {
                                btnEl.classList.remove('added');
                                btnEl.innerHTML = ICONS.cart;
                            }, 2000);
                        }
                    });
                });
            });
        });
    }

    function performCategorySearch(categoryId) {
        var defaultEl = document.getElementById('kg-search-default');
        var resultsEl = document.getElementById('kg-search-results');
        
        // Find category name
        var catName = '';
        CATEGORIES.forEach(function(c) { if (c.id == categoryId) catName = c.name; });

        if (defaultEl) defaultEl.style.display = 'none';
        if (resultsEl) {
            resultsEl.style.display = '';
            resultsEl.innerHTML = '<div class="kg-search-loading">\u8F09\u5165\u4E2D...</div>';
        }

        // Update search input
        var input = document.getElementById('kg-search-input');
        if (input) input.value = catName;
        var clearBtn = document.getElementById('kg-search-clear');
        if (clearBtn) clearBtn.classList.add('visible');

        fetchCategoryResults(categoryId, function(products) {
            if (!resultsEl) return;
            if (!products || products.length === 0) {
                resultsEl.innerHTML = '<div class="kg-search-results"><div class="kg-search-empty">\u8A72\u5206\u985E\u66AB\u7121\u5546\u54C1</div></div>';
                return;
            }
            var html = '<div class="kg-search-results">';
            html += '<div class="kg-search-results-title">' + escapeHtml(catName) + '<span class="kg-search-results-count">' + products.length + '\u500B\u5546\u54C1</span></div>';
            
            products.forEach(function(p) {
                html += '<div class="kg-search-result-item" data-url="' + escapeHtml(p.url) + '" data-product-id="' + escapeHtml(p.id) + '">';
                html += '<img class="kg-search-result-img" src="' + escapeHtml(p.image) + '" alt="" loading="lazy" onerror="this.style.background=\'#eee\'">';
                html += '<div class="kg-search-result-info">';
                html += '<div class="kg-search-result-name">' + escapeHtml(p.name) + '</div>';
                if (p.stars) {
                    html += '<div class="kg-search-result-meta">';
                    html += '<span class="kg-search-result-stars">' + p.stars + '</span>';
                    if (p.reviews) html += '<span class="kg-search-result-reviews">(' + escapeHtml(p.reviews) + ')</span>';
                    html += '</div>';
                }
                html += '<div style="display:flex;align-items:center">';
                html += '<span class="kg-search-result-price">TWD$ ' + escapeHtml(p.price) + '</span>';
                if (p.sold) html += '<span class="kg-search-result-sold">' + escapeHtml(p.sold) + '</span>';
                html += '</div>';
                html += '</div>';
                if (!p.soldOut) {
                    html += '<div class="kg-search-result-cart">';
                    html += '<button class="kg-search-result-cart-btn" data-product-id="' + escapeHtml(p.id) + '" title="\u52A0\u5165\u8CFC\u7269\u8ECA">' + ICONS.cart + '</button>';
                    html += '</div>';
                }
                html += '</div>';
            });
            html += '</div>';
            resultsEl.innerHTML = html;

            // Bind events
            resultsEl.querySelectorAll('.kg-search-result-item').forEach(function(item) {
                item.addEventListener('click', function(e) {
                    if (e.target.closest('.kg-search-result-cart-btn')) return;
                    var url = this.dataset.url;
                    if (url) window.location.href = url;
                });
            });
            resultsEl.querySelectorAll('.kg-search-result-cart-btn').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    var productId = this.dataset.productId;
                    var btnEl = this;
                    addToCartAjax(productId, function(success) {
                        if (success) {
                            btnEl.classList.add('added');
                            btnEl.innerHTML = ICONS.check;
                            setTimeout(function() {
                                btnEl.classList.remove('added');
                                btnEl.innerHTML = ICONS.cart;
                            }, 2000);
                        }
                    });
                });
            });
        });
    }

    // ===== Fetch Search Results from EC-CUBE =====
    function fetchSearchResults(query, cb) {
        var url = '/products/list';
        if (query) url += '?name=' + encodeURIComponent(query);
        
        var xhr = new XMLHttpRequest();
        xhr.open('GET', url);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onload = function() {
            var products = parseProductListHTML(xhr.responseText);
            cb(products);
        };
        xhr.onerror = function() { cb([]); };
        xhr.send();
    }

    function fetchCategoryResults(categoryId, cb) {
        var url = '/products/list?category_id=' + categoryId;
        
        var xhr = new XMLHttpRequest();
        xhr.open('GET', url);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onload = function() {
            var products = parseProductListHTML(xhr.responseText);
            cb(products);
        };
        xhr.onerror = function() { cb([]); };
        xhr.send();
    }

    function parseProductListHTML(htmlStr) {
        var parser = new DOMParser();
        var doc = parser.parseFromString(htmlStr, 'text/html');
        var items = doc.querySelectorAll('.ec-shelfGrid__item');
        var products = [];

        items.forEach(function(item) {
            var link = item.querySelector('a[href*="/products/detail/"]');
            var img = item.querySelector('img');
            var nameEl = item.querySelector('.name, p.name');
            var allText = item.textContent.replace(/\s+/g, ' ').trim();

            // Extract product ID
            var productId = '';
            if (link) {
                var m = link.href.match(/\/products\/detail\/(\d+)/);
                if (m) productId = m[1];
            }

            // Extract price
            var priceMatch = allText.match(/TWD\$\s*([\d,]+(?:\.\d+)?)/);
            var price = priceMatch ? priceMatch[1] : '';

            // Extract stars
            var starsMatch = allText.match(/([★☆]{5})/);
            var stars = starsMatch ? starsMatch[1] : '';

            // Extract review count
            var reviewMatch = allText.match(/\((\d+)\)/);
            var reviews = reviewMatch ? reviewMatch[1] : '';

            // Extract sold count
            var soldMatch = allText.match(/([\d,]+\+\u500B\s*\u7D2F\u8A08\u8CA9\u58F2\u6578)/);
            var sold = soldMatch ? soldMatch[1] : '';

            // Check if sold out
            var soldOut = allText.indexOf('SOLD OUT') !== -1;

            if (productId && nameEl) {
                products.push({
                    id: productId,
                    name: nameEl.textContent.trim(),
                    image: img ? img.src : '',
                    url: link ? link.href : '',
                    price: price,
                    stars: stars,
                    reviews: reviews,
                    sold: sold,
                    soldOut: soldOut
                });
            }
        });

        return products;
    }

    // ===== Bind Search Events =====
    function bindSearchEvents() {
        // Search button in fullscreen
        var searchBtn = document.getElementById('kg-fs-search');
        if (searchBtn) {
            searchBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                openSearchPanel();
            });
        }

        // Back button
        var backBtn = document.getElementById('kg-search-back');
        if (backBtn) {
            backBtn.addEventListener('click', function() {
                closeSearchPanel();
            });
        }

        // Search input
        var searchInput = document.getElementById('kg-search-input');
        var clearBtn = document.getElementById('kg-search-clear');
        var searchTimer = null;

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                var val = this.value.trim();
                if (clearBtn) {
                    clearBtn.classList.toggle('visible', val.length > 0);
                }
                // Debounce search
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function() {
                    performSearch(val);
                }, 400);
            });

            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(searchTimer);
                    performSearch(this.value.trim());
                }
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                if (searchInput) searchInput.value = '';
                this.classList.remove('visible');
                var defaultEl = document.getElementById('kg-search-default');
                var resultsEl = document.getElementById('kg-search-results');
                if (defaultEl) defaultEl.style.display = '';
                if (resultsEl) resultsEl.style.display = 'none';
                if (searchInput) searchInput.focus();
            });
        }

        // Hot keyword tags
        var tags = document.querySelectorAll('.kg-search-tag');
        tags.forEach(function(tag) {
            tag.addEventListener('click', function() {
                var kw = this.dataset.keyword;
                if (searchInput) searchInput.value = kw;
                if (clearBtn) clearBtn.classList.add('visible');
                performSearch(kw);
            });
        });

        // Category cards
        var cats = document.querySelectorAll('.kg-search-cat');
        cats.forEach(function(cat) {
            cat.addEventListener('click', function() {
                var catId = this.dataset.categoryId;
                performCategorySearch(catId);
            });
        });
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

        container.innerHTML = '';
        container.appendChild(slide);

        updateActions(video);
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
        html += '<span class="kg-fs-action-label">\u5206\u4EAB</span>';
        html += '</div>';

        el.innerHTML = html;

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
            var priceVal = p.price02_inc_tax || p.price || 0;
            var price = priceVal ? 'NT$' + Number(priceVal).toLocaleString() : '';
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
            html += '<button class="kg-fs-cart-btn" data-product-id="' + p.id + '">' + ICONS.cart + ' \u52A0\u8CFC\u7269\u8ECA</button>';
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

        // Cart button - Ajax add to cart
        var cartBtn = el.querySelector('.kg-fs-cart-btn');
        if (cartBtn) {
            cartBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                var productId = this.dataset.productId;
                var btnEl = this;
                btnEl.disabled = true;
                btnEl.innerHTML = ICONS.cart + ' \u8F09\u5165\u4E2D...';
                addToCartAjax(productId, function(success) {
                    btnEl.disabled = false;
                    if (success) {
                        btnEl.classList.add('added');
                        btnEl.innerHTML = ICONS.check + ' \u5DF2\u52A0\u5165';
                        setTimeout(function() {
                            btnEl.classList.remove('added');
                            btnEl.innerHTML = ICONS.cart + ' \u52A0\u8CFC\u7269\u8ECA';
                        }, 2500);
                    } else {
                        btnEl.innerHTML = ICONS.cart + ' \u52A0\u8CFC\u7269\u8ECA';
                    }
                });
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
                        showToast(res.bookmarked ? '\u5DF2\u6536\u85CF' : '\u5DF2\u53D6\u6D88\u6536\u85CF');
                    }
                });
                break;

            case 'share':
                var shareUrl = window.location.origin + FEED_URL + '#video-' + video.id;
                if (navigator.share) {
                    navigator.share({ title: video.display_title || video.title, url: shareUrl }).catch(function(){});
                } else if (navigator.clipboard) {
                    navigator.clipboard.writeText(shareUrl).then(function() { showToast('\u9023\u7D50\u5DF2\u8907\u88FD'); });
                } else {
                    showToast('\u5206\u4EAB\u9023\u7D50: ' + shareUrl);
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
        list.innerHTML = '<div class="kg-comment-empty">\u8F09\u5165\u4E2D...</div>';

        apiGet(FEED_API + '?action=comments&video_id=' + video.id, function(res) {
            if (!res || !res.success || res.data.length === 0) {
                list.innerHTML = '<div class="kg-comment-empty">\u9084\u6C92\u6709\u8A55\u8AD6\uFF0C\u4F86\u6436\u6C99\u767C\u5427\uFF01</div>';
                return;
            }
            var html = '';
            res.data.forEach(function(c) {
                var initial = (c.nickname || '\u8A2A')[0].toUpperCase();
                html += '<div class="kg-comment-item">';
                html += '<div class="kg-comment-avatar">' + escapeHtml(initial) + '</div>';
                html += '<div class="kg-comment-body">';
                html += '<div class="kg-comment-nick">' + escapeHtml(c.nickname || '\u8A2A\u5BA2') + '</div>';
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

    // ===== Add to Cart (EC-CUBE Ajax) =====
    function addToCartAjax(productId, cb) {
        // First, fetch the product page to get the CSRF token and ProductClass
        var xhr1 = new XMLHttpRequest();
        xhr1.open('GET', '/products/detail/' + productId);
        xhr1.onload = function() {
            var parser = new DOMParser();
            var doc = parser.parseFromString(xhr1.responseText, 'text/html');
            
            // Get CSRF token
            var tokenInput = doc.querySelector('input[name="_token"]');
            var token = tokenInput ? tokenInput.value : '';
            
            // Get ProductClass
            var classInput = doc.querySelector('input[name="ProductClass"]');
            var productClass = classInput ? classInput.value : '';

            // Now submit the cart form
            var formData = new FormData();
            formData.append('product_id', productId);
            formData.append('ProductClass', productClass);
            formData.append('quantity', '1');
            formData.append('_token', token);
            formData.append('mode', '');

            var xhr2 = new XMLHttpRequest();
            xhr2.open('POST', '/products/add_cart/' + productId);
            xhr2.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr2.onload = function() {
                if (xhr2.status >= 200 && xhr2.status < 400) {
                    showToast('\u2705 \u5DF2\u52A0\u5165\u8CFC\u7269\u8ECA\uFF01');
                    // Update cart badge
                    try {
                        var cartBadge = document.querySelector('.ec-cartNavi__badge, .cart-count, [class*="cart"] .badge');
                        if (cartBadge) {
                            var cnt = parseInt(cartBadge.textContent) || 0;
                            cartBadge.textContent = cnt + 1;
                        }
                    } catch(e) {}
                    cb && cb(true);
                } else {
                    showToast('\u52A0\u5165\u8CFC\u7269\u8ECA\u5931\u6557\uFF0C\u8ACB\u91CD\u8A66');
                    cb && cb(false);
                }
            };
            xhr2.onerror = function() {
                showToast('\u7DB2\u8DEF\u932F\u8AA4\uFF0C\u8ACB\u91CD\u8A66');
                cb && cb(false);
            };
            xhr2.send(formData);
        };
        xhr1.onerror = function() {
            showToast('\u7DB2\u8DEF\u932F\u8AA4\uFF0C\u8ACB\u91CD\u8A66');
            cb && cb(false);
        };
        xhr1.send();
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

        // Comment panel close
        document.getElementById('kg-comment-close').addEventListener('click', function() {
            toggleCommentPanel(false);
        });

        // Comment send
        document.getElementById('kg-comment-send').addEventListener('click', submitComment);
        document.getElementById('kg-comment-input').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); submitComment(); }
        });

        // Bind search events
        bindSearchEvents();

        // Touch swipe for video navigation
        var container = document.getElementById('kg-fs-container');
        container.addEventListener('touchstart', function(e) {
            if (isCommentOpen || isSearchOpen) return;
            touchStartY = e.touches[0].clientY;
            isSwiping = true;
        }, { passive: true });

        container.addEventListener('touchmove', function(e) {
            if (!isSwiping || isCommentOpen || isSearchOpen) return;
            touchDeltaY = e.touches[0].clientY - touchStartY;
        }, { passive: true });

        container.addEventListener('touchend', function() {
            if (!isSwiping || isCommentOpen || isSearchOpen) return;
            isSwiping = false;
            if (Math.abs(touchDeltaY) > 60) {
                if (touchDeltaY < 0) {
                    navigateVideo(1); // Swipe up = next (infinite)
                } else if (touchDeltaY > 0 && currentIndex > 0) {
                    navigateVideo(-1); // Swipe down = previous
                }
            }
            touchDeltaY = 0;
        });

        // Keyboard navigation
        overlay.addEventListener('keydown', function(e) {
            if (isCommentOpen || isSearchOpen) return;
            if (e.key === 'ArrowDown') {
                navigateVideo(1); // Infinite scroll
            } else if (e.key === 'ArrowUp' && currentIndex > 0) {
                navigateVideo(-1);
            } else if (e.key === 'Escape') {
                closeFullscreen();
            }
        });
        overlay.setAttribute('tabindex', '0');
        overlay.focus();

        // Click on video to toggle play/pause
        container.addEventListener('click', function(e) {
            if (e.target.closest('.kg-fs-action, .kg-fs-product, .kg-fs-cart-btn, .kg-fs-search, .kg-search-panel')) return;
            var vid = container.querySelector('video');
            if (vid) {
                if (vid.paused) vid.play();
                else vid.pause();
            }
        });
    }

    function navigateVideo(direction) {
        var newIndex = currentIndex + direction;
        if (newIndex < 0) return;

        // If we're at the end, try to load more
        if (newIndex >= allVideos.length) {
            if (!isLoadingMore) {
                // Show loading indicator
                showSwipeLoading(true);
                fetchMoreVideos(function(newVids) {
                    showSwipeLoading(false);
                    if (newVids && newVids.length > 0) {
                        // Now navigate to the new video
                        doNavigate(newIndex);
                    } else {
                        showToast('\u5DF2\u770B\u5B8C\u6240\u6709\u5F71\u7247'); // 已看完所有影片
                    }
                });
            }
            return;
        }

        doNavigate(newIndex);

        // Pre-fetch more when approaching the end (2 videos before)
        if (newIndex >= allVideos.length - 2) {
            fetchMoreVideos(function() {});
        }
    }

    function doNavigate(newIndex) {
        if (newIndex >= allVideos.length) return;

        var oldSlide = document.getElementById('kg-fs-slide-' + currentIndex);
        if (oldSlide) {
            var vid = oldSlide.querySelector('video');
            if (vid) vid.pause();
        }

        currentIndex = newIndex;
        loadSlide(currentIndex);
        apiPost('view', { video_id: allVideos[currentIndex].id });

        if (isCommentOpen) toggleCommentPanel(false);
    }

    function showSwipeLoading(show) {
        var existing = document.getElementById('kg-fs-loading');
        if (show && !existing) {
            var el = document.createElement('div');
            el.id = 'kg-fs-loading';
            el.style.cssText = 'position:fixed;bottom:120px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,.7);color:#fff;padding:10px 24px;border-radius:20px;font-size:14px;z-index:100002;display:flex;align-items:center;gap:8px;';
            el.innerHTML = '<div style="width:18px;height:18px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:kg-spin 1s linear infinite;"></div> \u8F09\u5165\u4E2D...';
            var overlay = document.getElementById('kg-fs-overlay');
            if (overlay) overlay.appendChild(el);
        } else if (!show && existing) {
            existing.remove();
        }
    }

    function closeFullscreen() {
        var overlay = document.getElementById('kg-fs-overlay');
        if (overlay) {
            var vid = overlay.querySelector('video');
            if (vid) vid.pause();
            overlay.remove();
        }
        document.body.style.overflow = '';
        isCommentOpen = false;
        isSearchOpen = false;
    }

    // ===== Initialize =====
    function init() {
        injectStyles();

        var match = window.location.pathname.match(/\/products\/detail\/(\d+)/);
        if (!match) return;
        var productId = match[1];

        var container = document.getElementById('kg-video-widget');
        if (!container) {
            var cartForm = document.querySelector('.ec-productRole__btn form, form[action*="add_cart"]');
            if (cartForm) {
                container = document.createElement('div');
                container.id = 'kg-video-widget';
                cartForm.parentNode.insertBefore(container, cartForm.nextSibling);
            } else {
                var freeArea = document.querySelector('.ec-productRole__description');
                if (freeArea) {
                    container = document.createElement('div');
                    container.id = 'kg-video-widget';
                    freeArea.parentNode.insertBefore(container, freeArea);
                }
            }
        }
        if (!container) return;

        fetchProductVideos(productId, function(videos) {
            if (videos.length > 0) {
                renderCarousel(container, videos);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
