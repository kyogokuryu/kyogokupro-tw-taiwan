/**
 * KYOGOKU Feed Video Widget
 * 商品詳細ページに紐付き動画をインライン再生カルーセルで表示
 * 
 * Usage: <div id="kg-video-widget" data-product-id="XX"></div>
 *        <script src="/feed/assets/js/widget.js"></script>
 */
(function() {
    'use strict';

    var FEED_API = '/feed/api/';
    var FEED_URL = '/feed/';

    // ===== Styles =====
    function injectStyles() {
        if (document.getElementById('kg-widget-styles')) return;
        var css = document.createElement('style');
        css.id = 'kg-widget-styles';
        css.textContent = '\n' +
        '/* Widget Container */\n' +
        '.kg-widget { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 30px 0; padding: 0; }\n' +
        '.kg-widget * { box-sizing: border-box; }\n' +
        '.kg-widget-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; padding: 0 4px; }\n' +
        '.kg-widget-title { font-size: 18px; font-weight: 700; color: #1a1a1a; display: flex; align-items: center; gap: 8px; }\n' +
        '.kg-widget-title-icon { font-size: 22px; }\n' +
        '.kg-widget-more { font-size: 14px; color: #666; text-decoration: none; display: flex; align-items: center; gap: 4px; transition: color 0.2s; }\n' +
        '.kg-widget-more:hover { color: #e74c3c; }\n' +
        '\n' +
        '/* Carousel */\n' +
        '.kg-carousel-wrap { position: relative; overflow: hidden; }\n' +
        '.kg-carousel { display: flex; gap: 12px; overflow-x: auto; scroll-snap-type: x mandatory; -webkit-overflow-scrolling: touch; scrollbar-width: none; padding: 4px 0; }\n' +
        '.kg-carousel::-webkit-scrollbar { display: none; }\n' +
        '\n' +
        '/* Video Card */\n' +
        '.kg-card { flex: 0 0 200px; scroll-snap-align: start; border-radius: 12px; overflow: hidden; position: relative; cursor: pointer; background: #000; aspect-ratio: 9/16; transition: transform 0.3s, box-shadow 0.3s; }\n' +
        '.kg-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,0.15); }\n' +
        '.kg-card-thumb { width: 100%; height: 100%; object-fit: cover; transition: opacity 0.3s; }\n' +
        '.kg-card-preview { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0; transition: opacity 0.5s; }\n' +
        '.kg-card:hover .kg-card-preview { opacity: 1; }\n' +
        '.kg-card:hover .kg-card-thumb { opacity: 0; }\n' +
        '\n' +
        '/* Card Overlay */\n' +
        '.kg-card-overlay { position: absolute; bottom: 0; left: 0; right: 0; padding: 40px 12px 12px; background: linear-gradient(transparent, rgba(0,0,0,0.8)); pointer-events: none; }\n' +
        '.kg-card-title { color: #fff; font-size: 13px; font-weight: 600; line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 8px; text-shadow: 0 1px 3px rgba(0,0,0,0.5); }\n' +
        '.kg-card-stats { display: flex; align-items: center; gap: 12px; color: rgba(255,255,255,0.85); font-size: 12px; }\n' +
        '.kg-card-stat { display: flex; align-items: center; gap: 3px; }\n' +
        '\n' +
        '/* Play Button */\n' +
        '.kg-play-btn { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 48px; height: 48px; background: rgba(255,255,255,0.9); border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: transform 0.3s, opacity 0.3s; opacity: 0.9; }\n' +
        '.kg-card:hover .kg-play-btn { transform: translate(-50%, -50%) scale(1.1); opacity: 1; }\n' +
        '.kg-play-icon { width: 0; height: 0; border-style: solid; border-width: 10px 0 10px 18px; border-color: transparent transparent transparent #1a1a1a; margin-left: 3px; }\n' +
        '\n' +
        '/* More Card */\n' +
        '.kg-card-more { flex: 0 0 200px; scroll-snap-align: start; border-radius: 12px; overflow: hidden; cursor: pointer; background: linear-gradient(135deg, #f8f8f8, #e8e8e8); aspect-ratio: 9/16; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 12px; transition: transform 0.3s, box-shadow 0.3s; text-decoration: none; }\n' +
        '.kg-card-more:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,0.1); background: linear-gradient(135deg, #fff, #f0f0f0); }\n' +
        '.kg-card-more-icon { width: 48px; height: 48px; border-radius: 50%; background: #e74c3c; display: flex; align-items: center; justify-content: center; }\n' +
        '.kg-card-more-arrow { color: #fff; font-size: 24px; font-weight: bold; }\n' +
        '.kg-card-more-text { color: #333; font-size: 14px; font-weight: 600; }\n' +
        '\n' +
        '/* Carousel Navigation */\n' +
        '.kg-nav { position: absolute; top: 50%; transform: translateY(-50%); width: 36px; height: 36px; border-radius: 50%; background: rgba(255,255,255,0.95); border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.15); z-index: 10; transition: transform 0.2s, box-shadow 0.2s; }\n' +
        '.kg-nav:hover { transform: translateY(-50%) scale(1.1); box-shadow: 0 4px 12px rgba(0,0,0,0.2); }\n' +
        '.kg-nav-left { left: 8px; }\n' +
        '.kg-nav-right { right: 8px; }\n' +
        '.kg-nav-arrow { font-size: 18px; color: #333; font-weight: bold; line-height: 1; }\n' +
        '\n' +
        '/* Inline Player Modal */\n' +
        '.kg-player-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.85); z-index: 99999; display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s; }\n' +
        '.kg-player-overlay.active { opacity: 1; }\n' +
        '.kg-player-container { position: relative; width: 100%; max-width: 400px; aspect-ratio: 9/16; border-radius: 16px; overflow: hidden; background: #000; box-shadow: 0 20px 60px rgba(0,0,0,0.5); }\n' +
        '.kg-player-video { width: 100%; height: 100%; object-fit: cover; }\n' +
        '.kg-player-iframe { width: 100%; height: 100%; border: none; }\n' +
        '.kg-player-close { position: absolute; top: 12px; right: 12px; width: 36px; height: 36px; border-radius: 50%; background: rgba(0,0,0,0.6); border: none; cursor: pointer; color: #fff; font-size: 20px; display: flex; align-items: center; justify-content: center; z-index: 10; transition: background 0.2s; }\n' +
        '.kg-player-close:hover { background: rgba(0,0,0,0.8); }\n' +
        '\n' +
        '/* Sound Toggle */\n' +
        '.kg-sound-toggle { position: absolute; bottom: 80px; right: 12px; width: 40px; height: 40px; border-radius: 50%; background: rgba(0,0,0,0.6); border: none; cursor: pointer; color: #fff; font-size: 18px; display: flex; align-items: center; justify-content: center; z-index: 10; transition: background 0.2s; }\n' +
        '.kg-sound-toggle:hover { background: rgba(0,0,0,0.8); }\n' +
        '.kg-sound-hint { position: absolute; bottom: 126px; right: 8px; background: rgba(0,0,0,0.7); color: #fff; font-size: 11px; padding: 4px 10px; border-radius: 12px; white-space: nowrap; opacity: 1; transition: opacity 1s; pointer-events: none; }\n' +
        '\n' +
        '/* Player Stats */\n' +
        '.kg-player-stats { position: absolute; bottom: 0; left: 0; right: 0; padding: 60px 16px 16px; background: linear-gradient(transparent, rgba(0,0,0,0.7)); }\n' +
        '.kg-player-title { color: #fff; font-size: 16px; font-weight: 700; margin-bottom: 8px; text-shadow: 0 1px 3px rgba(0,0,0,0.5); }\n' +
        '.kg-player-meta { display: flex; align-items: center; gap: 16px; color: rgba(255,255,255,0.85); font-size: 13px; }\n' +
        '.kg-player-meta-item { display: flex; align-items: center; gap: 4px; }\n' +
        '\n' +
        '/* CTA Button */\n' +
        '.kg-player-cta { position: absolute; bottom: 16px; left: 16px; right: 16px; z-index: 10; }\n' +
        '.kg-player-stats { bottom: 60px; }\n' +
        '.kg-cta-btn { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 12px 16px; background: linear-gradient(135deg, #e74c3c, #c0392b); color: #fff; border: none; border-radius: 25px; font-size: 15px; font-weight: 700; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; text-decoration: none; }\n' +
        '.kg-cta-btn:hover { transform: scale(1.03); box-shadow: 0 4px 16px rgba(231,76,60,0.4); }\n' +
        '.kg-cta-price { font-size: 14px; opacity: 0.9; }\n' +
        '\n' +
        '/* Dots Indicator */\n' +
        '.kg-dots { display: flex; justify-content: center; gap: 6px; margin-top: 12px; }\n' +
        '.kg-dot { width: 6px; height: 6px; border-radius: 50%; background: #ccc; transition: background 0.3s, width 0.3s; }\n' +
        '.kg-dot.active { background: #e74c3c; width: 18px; border-radius: 3px; }\n' +
        '\n' +
        '/* Responsive */\n' +
        '@media (max-width: 768px) {\n' +
        '  .kg-card { flex: 0 0 160px; }\n' +
        '  .kg-card-more { flex: 0 0 160px; }\n' +
        '  .kg-card-title { font-size: 12px; }\n' +
        '  .kg-widget-title { font-size: 16px; }\n' +
        '  .kg-player-container { max-width: 90vw; }\n' +
        '  .kg-nav { display: none; }\n' +
        '}\n' +
        '';
        document.head.appendChild(css);
    }

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

    // ===== API =====
    function fetchProductVideos(productId, callback) {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', FEED_API + '?action=product_videos&product_id=' + productId);
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    var res = JSON.parse(xhr.responseText);
                    callback(res.success ? res.data : []);
                } catch(e) { callback([]); }
            } else { callback([]); }
        };
        xhr.onerror = function() { callback([]); };
        xhr.send();
    }

    // ===== Render Widget =====
    function renderWidget(container, videos, productId) {
        if (!videos || videos.length === 0) {
            container.style.display = 'none';
            return;
        }

        injectStyles();

        var html = '<div class="kg-widget">';
        
        // Header
        html += '<div class="kg-widget-header">';
        html += '<div class="kg-widget-title"><span class="kg-widget-title-icon">📹</span> 這款商品的使用影片</div>';
        html += '<a href="' + FEED_URL + '" class="kg-widget-more">查看更多 ›</a>';
        html += '</div>';

        // Carousel
        html += '<div class="kg-carousel-wrap">';
        html += '<div class="kg-carousel" id="kg-carousel-' + productId + '">';

        for (var i = 0; i < videos.length; i++) {
            var v = videos[i];
            var thumb = v.thumbnail || '';
            var title = v.display_title || v.title || '';
            var views = formatNumber(v.view_count);
            var likes = formatNumber(v.like_count);
            var isUpload = v.video_type === 'upload' && v.video_file_url;

            html += '<div class="kg-card" data-index="' + i + '">';
            
            // Thumbnail
            if (thumb) {
                html += '<img class="kg-card-thumb" src="' + thumb + '" alt="' + title.replace(/"/g, '&quot;') + '" loading="lazy">';
            } else {
                html += '<div class="kg-card-thumb" style="background:#1a1a1a;"></div>';
            }

            // Auto-play preview video (for uploaded videos)
            if (isUpload) {
                html += '<video class="kg-card-preview" src="' + v.video_file_url + '" muted loop playsinline preload="none"></video>';
            }

            // Play button
            html += '<div class="kg-play-btn"><div class="kg-play-icon"></div></div>';

            // Overlay with title & stats
            html += '<div class="kg-card-overlay">';
            html += '<div class="kg-card-title">' + title + '</div>';
            html += '<div class="kg-card-stats">';
            html += '<span class="kg-card-stat">👁 ' + views + '</span>';
            html += '<span class="kg-card-stat">❤️ ' + likes + '</span>';
            html += '</div>';
            html += '</div>';

            html += '</div>';
        }

        // "More" card
        html += '<a href="' + FEED_URL + '" class="kg-card-more">';
        html += '<div class="kg-card-more-icon"><span class="kg-card-more-arrow">›</span></div>';
        html += '<div class="kg-card-more-text">查看更多影片</div>';
        html += '</a>';

        html += '</div>'; // carousel

        // Navigation arrows (PC only)
        html += '<button class="kg-nav kg-nav-left" data-dir="left"><span class="kg-nav-arrow">‹</span></button>';
        html += '<button class="kg-nav kg-nav-right" data-dir="right"><span class="kg-nav-arrow">›</span></button>';

        html += '</div>'; // carousel-wrap

        // Dots indicator
        html += '<div class="kg-dots" id="kg-dots-' + productId + '">';
        for (var d = 0; d <= videos.length; d++) {
            html += '<div class="kg-dot' + (d === 0 ? ' active' : '') + '"></div>';
        }
        html += '</div>';

        html += '</div>'; // widget

        container.innerHTML = html;

        // Bind events
        bindCarouselEvents(container, videos, productId);
        bindCardEvents(container, videos);
        bindHoverPreview(container);
    }

    // ===== Hover Preview (auto-play on hover) =====
    function bindHoverPreview(container) {
        var cards = container.querySelectorAll('.kg-card');
        for (var i = 0; i < cards.length; i++) {
            (function(card) {
                var preview = card.querySelector('.kg-card-preview');
                if (!preview) return;

                card.addEventListener('mouseenter', function() {
                    preview.play().catch(function(){});
                });
                card.addEventListener('mouseleave', function() {
                    preview.pause();
                    preview.currentTime = 0;
                });
            })(cards[i]);
        }

        // Mobile: IntersectionObserver for auto-play
        if ('IntersectionObserver' in window && /Mobi|Android/i.test(navigator.userAgent)) {
            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    var preview = entry.target.querySelector('.kg-card-preview');
                    if (!preview) return;
                    if (entry.isIntersecting) {
                        preview.play().catch(function(){});
                    } else {
                        preview.pause();
                    }
                });
            }, { threshold: 0.5 });

            cards.forEach(function(card) { observer.observe(card); });
        }
    }

    // ===== Carousel Scroll =====
    function bindCarouselEvents(container, videos, productId) {
        var carousel = container.querySelector('.kg-carousel');
        var dots = container.querySelectorAll('.kg-dot');
        var navLeft = container.querySelector('.kg-nav-left');
        var navRight = container.querySelector('.kg-nav-right');

        function updateDots() {
            var scrollLeft = carousel.scrollLeft;
            var cardWidth = carousel.querySelector('.kg-card').offsetWidth + 12;
            var activeIndex = Math.round(scrollLeft / cardWidth);
            for (var i = 0; i < dots.length; i++) {
                dots[i].classList.toggle('active', i === activeIndex);
            }
        }

        carousel.addEventListener('scroll', debounce(updateDots, 100));

        if (navLeft) {
            navLeft.addEventListener('click', function() {
                carousel.scrollBy({ left: -220, behavior: 'smooth' });
            });
        }
        if (navRight) {
            navRight.addEventListener('click', function() {
                carousel.scrollBy({ left: 220, behavior: 'smooth' });
            });
        }
    }

    // ===== Card Click → Inline Player =====
    function bindCardEvents(container, videos) {
        var cards = container.querySelectorAll('.kg-card');
        for (var i = 0; i < cards.length; i++) {
            (function(index) {
                cards[index].addEventListener('click', function() {
                    openPlayer(videos[index]);
                });
            })(i);
        }
    }

    // ===== Inline Player =====
    function openPlayer(video) {
        // Remove existing player
        var existing = document.querySelector('.kg-player-overlay');
        if (existing) existing.remove();

        var isUpload = video.video_type === 'upload' && video.video_file_url;
        var ytId = extractYoutubeId(video.video_url || video.video_src);
        var title = video.display_title || video.title || '';
        var views = formatNumber(video.view_count);
        var likes = formatNumber(video.like_count);

        var overlay = document.createElement('div');
        overlay.className = 'kg-player-overlay';

        var html = '<div class="kg-player-container">';

        // Video content
        if (isUpload) {
            html += '<video class="kg-player-video" src="' + video.video_file_url + '" autoplay muted loop playsinline></video>';
            html += '<button class="kg-sound-toggle" id="kg-sound-btn" title="點擊開啟聲音">🔇</button>';
            html += '<div class="kg-sound-hint" id="kg-sound-hint">點擊開啟聲音</div>';
        } else if (ytId) {
            html += '<iframe class="kg-player-iframe" src="https://www.youtube.com/embed/' + ytId + '?autoplay=1&mute=1&loop=1&playlist=' + ytId + '&playsinline=1&rel=0" allow="autoplay; encrypted-media" allowfullscreen></iframe>';
        }

        // Close button
        html += '<button class="kg-player-close" title="關閉">✕</button>';

        // Stats overlay
        html += '<div class="kg-player-stats">';
        html += '<div class="kg-player-title">' + title + '</div>';
        html += '<div class="kg-player-meta">';
        html += '<span class="kg-player-meta-item">👁 ' + views + ' 次觀看</span>';
        html += '<span class="kg-player-meta-item">❤️ ' + likes + '</span>';
        html += '</div>';
        html += '</div>';

        // CTA button (if product linked)
        if (video.products && video.products.length > 0) {
            var p = video.products[0];
            var price = p.price ? 'NT$' + parseInt(p.price).toLocaleString() : '';
            html += '<div class="kg-player-cta">';
            html += '<a href="/products/detail/' + p.id + '" class="kg-cta-btn">';
            html += '🛒 立即購買';
            if (price) html += ' <span class="kg-cta-price">' + price + '</span>';
            html += '</a>';
            html += '</div>';
        }

        html += '</div>'; // player-container
        overlay.innerHTML = html;
        document.body.appendChild(overlay);

        // Prevent body scroll
        document.body.style.overflow = 'hidden';

        // Animate in
        requestAnimationFrame(function() {
            overlay.classList.add('active');
        });

        // Close events
        var closeBtn = overlay.querySelector('.kg-player-close');
        closeBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            closePlayer(overlay);
        });

        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                closePlayer(overlay);
            }
        });

        document.addEventListener('keydown', function escHandler(e) {
            if (e.key === 'Escape') {
                closePlayer(overlay);
                document.removeEventListener('keydown', escHandler);
            }
        });

        // Sound toggle
        if (isUpload) {
            var soundBtn = overlay.querySelector('#kg-sound-btn');
            var soundHint = overlay.querySelector('#kg-sound-hint');
            var playerVideo = overlay.querySelector('.kg-player-video');

            // Hide hint after 3s
            setTimeout(function() {
                if (soundHint) soundHint.style.opacity = '0';
            }, 3000);

            soundBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                playerVideo.muted = !playerVideo.muted;
                soundBtn.textContent = playerVideo.muted ? '🔇' : '🔊';
                if (soundHint) soundHint.style.display = 'none';
            });
        }

        // Record view
        recordView(video.id);
    }

    function closePlayer(overlay) {
        overlay.classList.remove('active');
        document.body.style.overflow = '';
        setTimeout(function() {
            if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
        }, 300);
    }

    function recordView(videoId) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', FEED_API + '?action=view');
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.send(JSON.stringify({ video_id: videoId }));
    }

    // ===== Debounce =====
    function debounce(fn, delay) {
        var timer;
        return function() {
            clearTimeout(timer);
            timer = setTimeout(fn, delay);
        };
    }

    // ===== Init =====
    function init() {
        var containers = document.querySelectorAll('[id="kg-video-widget"], [data-kg-widget]');
        if (containers.length === 0) {
            // Auto-detect product page
            var productIdMeta = document.querySelector('input[name="product_id"]');
            if (productIdMeta) {
                var pid = productIdMeta.value;
                // Find insertion point: after product description, before category list
                var descEl = document.querySelector('.ec-productRole__description') || 
                             document.querySelector('.ec-productRole__detail') ||
                             document.querySelector('.item_visual');
                if (descEl) {
                    var widgetDiv = document.createElement('div');
                    widgetDiv.id = 'kg-video-widget';
                    widgetDiv.setAttribute('data-product-id', pid);
                    descEl.parentNode.insertBefore(widgetDiv, descEl.nextSibling);
                    containers = [widgetDiv];
                }
            }
        }

        for (var i = 0; i < containers.length; i++) {
            (function(container) {
                var productId = container.getAttribute('data-product-id');
                if (!productId) return;

                fetchProductVideos(productId, function(videos) {
                    renderWidget(container, videos, productId);
                });
            })(containers[i]);
        }
    }

    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
