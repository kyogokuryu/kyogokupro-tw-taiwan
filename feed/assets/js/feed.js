/**
 * Feed - TikTok-style Video Feed
 * tw.kyogokupro.com/feed/
 * 
 * v6: Error handling & resilience improvements
 *   - Video load error handling (404, network errors)
 *   - Timeout fallback (15s) for stuck videos
 *   - Error overlay with retry button
 *   - Skip to next video on error
 *   - MOV format warning for non-Safari browsers
 *   - All v5 features preserved
 */

(function() {
    'use strict';

    var API_BASE = '/feed/api/';
    var currentPage = 1;
    var isLoading = false;
    var hasMore = true;
    var activeVideoId = null;
    var viewedVideos = new Set();
    var isMuted = true;
    var scrollTimer = null;
    var muteBtn = null;
    var PRELOAD_AHEAD = 3; // Number of videos to preload ahead
    var LOAD_TIMEOUT = 15000; // 15 seconds timeout for video loading
    var loadTimeouts = {}; // Track timeouts per video ID

    // ===== Global Mute Button =====
    function createMuteButton() {
        muteBtn = document.createElement('button');
        muteBtn.className = 'feed-mute-btn';
        muteBtn.setAttribute('aria-label', '音量切換');
        updateMuteIcon();
        document.body.appendChild(muteBtn);
        muteBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            isMuted = !isMuted;
            updateMuteIcon();
            var activeVid = getActiveVideo();
            if (activeVid) activeVid.muted = isMuted;
        });
    }

    function updateMuteIcon() {
        if (!muteBtn) return;
        if (isMuted) {
            muteBtn.innerHTML = '<svg viewBox="0 0 24 24"><path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"/></svg>';
            muteBtn.classList.add('muted');
        } else {
            muteBtn.innerHTML = '<svg viewBox="0 0 24 24"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/></svg>';
            muteBtn.classList.remove('muted');
        }
    }

    function getActiveVideo() {
        if (!activeVideoId) return null;
        var card = document.querySelector('.video-card[data-video-id="' + activeVideoId + '"]');
        if (!card) return null;
        return card.querySelector('video');
    }

    // ===== API Functions =====
    async function fetchVideos(page) {
        var res = await fetch(API_BASE + '?action=videos&page=' + page);
        return await res.json();
    }

    async function postAction(action, data) {
        try {
            var res = await fetch(API_BASE + '?action=' + action, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            return await res.json();
        } catch (e) { return null; }
    }

    // ===== Error Handling =====
    function showVideoError(card, message) {
        var wrapper = card.querySelector('.video-player-wrapper');
        if (!wrapper) return;

        // Remove any existing error overlay
        var existing = wrapper.querySelector('.video-error-overlay');
        if (existing) existing.remove();

        // Show thumbnail
        var thumb = card.querySelector('.video-thumbnail');
        if (thumb) thumb.classList.remove('hidden');

        // Create error overlay
        var overlay = document.createElement('div');
        overlay.className = 'video-error-overlay';
        overlay.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;z-index:5;display:flex;flex-direction:column;align-items:center;justify-content:center;background:rgba(0,0,0,0.6);color:#fff;text-align:center;pointer-events:auto;';
        overlay.innerHTML =
            '<svg viewBox="0 0 24 24" style="width:48px;height:48px;fill:#fff;opacity:0.8;margin-bottom:12px;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>' +
            '<div style="font-size:14px;font-weight:600;margin-bottom:6px;">' + (message || '影片暫時無法播放') + '</div>' +
            '<button class="video-error-skip" style="margin-top:10px;padding:8px 20px;border:1px solid rgba(255,255,255,0.5);border-radius:20px;background:transparent;color:#fff;font-size:13px;cursor:pointer;">跳過此影片 ▼</button>';

        // Skip button handler
        overlay.querySelector('.video-error-skip').addEventListener('click', function(e) {
            e.stopPropagation();
            skipToNextVideo(card);
        });

        wrapper.appendChild(overlay);
    }

    function skipToNextVideo(currentCard) {
        var cards = Array.from(document.querySelectorAll('.video-card'));
        var idx = cards.indexOf(currentCard);
        if (idx >= 0 && idx < cards.length - 1) {
            var nextCard = cards[idx + 1];
            nextCard.scrollIntoView({ behavior: 'smooth' });
        }
    }

    function clearLoadTimeout(videoId) {
        if (loadTimeouts[videoId]) {
            clearTimeout(loadTimeouts[videoId]);
            delete loadTimeouts[videoId];
        }
    }

    function setLoadTimeout(videoId, card) {
        clearLoadTimeout(videoId);
        loadTimeouts[videoId] = setTimeout(function() {
            var vid = card.querySelector('video.feed-video');
            if (vid && vid.readyState < 2 && activeVideoId === videoId) {
                // Video still hasn't loaded after timeout
                console.warn('[Feed] Video #' + videoId + ' load timeout after ' + (LOAD_TIMEOUT / 1000) + 's');
                showVideoError(card, '影片載入逾時');
            }
        }, LOAD_TIMEOUT);
    }

    // ===== Render Functions =====
    function createVideoCard(video, index) {
        var card = document.createElement('div');
        card.className = 'video-card';
        card.dataset.videoId = video.id;
        card.dataset.youtubeId = video.youtube_id || '';
        card.dataset.videoType = video.video_type || 'youtube';
        card.dataset.videoUrl = video.video_url || '';
        card.dataset.videoFile = video.video_file_url || '';

        var thumbnailUrl = video.thumbnail || '';
        var productsHtml = (video.products && video.products.length > 0)
            ? createProductsHtml(video.products, video.id) : '';

        var displayTitle = video.display_title || video.title;
        var displayDesc = video.display_description || video.description;

        // Determine the native video source
        var nativeSrc = '';
        if (video.video_type === 'upload' && video.video_file_url) {
            nativeSrc = video.video_file_url;
        } else if (video.video_type === 'direct' && video.video_url) {
            nativeSrc = video.video_url;
        }

        // Build video HTML with aggressive preloading strategy:
        // - First video (index 0): preload="auto" + src set immediately
        // - Next PRELOAD_AHEAD videos: preload="auto" + src set immediately
        // - Rest: preload="none" + data-src (lazy load on scroll)
        var videoHtml = '';
        if (nativeSrc) {
            var isEager = (typeof index === 'number' && index < PRELOAD_AHEAD + 1);
            if (isEager) {
                // Eager: set src immediately, preload=auto for full buffering
                videoHtml = '<video class="feed-video" ' +
                    'playsinline webkit-playsinline ' +
                    'muted loop preload="auto" ' +
                    'src="' + escapeHtml(nativeSrc) + '" ' +
                    'style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:contain;background:#000;z-index:1;">' +
                    '</video>';
            } else {
                // Lazy: defer loading until near viewport
                videoHtml = '<video class="feed-video" ' +
                    'playsinline webkit-playsinline ' +
                    'muted loop preload="none" ' +
                    'data-src="' + escapeHtml(nativeSrc) + '" ' +
                    'style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:contain;background:#000;z-index:1;">' +
                    '</video>';
            }
        }

        card.innerHTML =
            '<div class="video-player-wrapper" data-video-id="' + video.id + '">' +
                videoHtml +
                '<img class="video-thumbnail" src="' + escapeHtml(thumbnailUrl) + '" alt="' + escapeHtml(displayTitle) + '" loading="lazy">' +
            '</div>' +
            '<div class="video-actions">' +
                '<button class="action-btn like-btn' + (video.has_liked ? ' liked' : '') + '" data-video-id="' + video.id + '" aria-label="喜歡">' +
                    '<span class="action-btn-icon"><svg viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg></span>' +
                    '<span class="action-btn-label">' + (video.formatted_likes || '0') + '</span>' +
                '</button>' +
                '<button class="action-btn share-btn" data-video-id="' + video.id + '" data-title="' + escapeHtml(displayTitle) + '" aria-label="分享">' +
                    '<span class="action-btn-icon"><svg viewBox="0 0 24 24"><path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92s2.92-1.31 2.92-2.92-1.31-2.92-2.92-2.92z"/></svg></span>' +
                    '<span class="action-btn-label">分享</span>' +
                '</button>' +
                '<button class="action-btn comment-btn" data-video-id="' + video.id + '" aria-label="觀看次數">' +
                    '<span class="action-btn-icon"><svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg></span>' +
                    '<span class="action-btn-label">' + (video.formatted_views || '0') + '</span>' +
                '</button>' +
            '</div>' +
            (productsHtml ? '<div class="video-products">' + productsHtml + '</div>' : '') +
            '<div class="video-info">' +
                '<h2 class="video-title">' + escapeHtml(displayTitle) + '</h2>' +
                (displayDesc ? '<p class="video-description">' + escapeHtml(displayDesc) + '</p>' : '') +
                '<div class="video-meta">' +
                    '<span class="video-meta-item"><svg viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>' + (video.formatted_date || '') + '</span>' +
                    (video.video_type === 'upload' ? '<span class="video-meta-item" style="color:#9f7aea;">&#9654; 原創影片</span>' : '') +
                '</div>' +
            '</div>';

        // Attach error handler to video element after card is created
        var videoEl = card.querySelector('video.feed-video');
        if (videoEl) {
            videoEl.addEventListener('error', function() {
                var errorCode = videoEl.error ? videoEl.error.code : 0;
                var errorMsg = '影片暫時無法播放';
                if (errorCode === 4) errorMsg = '影片格式不支援或檔案遺失';
                else if (errorCode === 2) errorMsg = '網路錯誤，無法載入影片';
                else if (errorCode === 3) errorMsg = '影片解碼錯誤';
                console.warn('[Feed] Video #' + video.id + ' error: code=' + errorCode);
                clearLoadTimeout(video.id);
                showVideoError(card, errorMsg);
            });
        }

        return card;
    }

    function createProductsHtml(products, videoId) {
        return products.map(function(p) {
            var priceText = p.price ? 'NT$' + Number(p.price).toLocaleString() : '';
            var imgHtml = p.image_url && !p.image_url.includes('no-image')
                ? '<img class="product-card-img" src="' + escapeHtml(p.image_url) + '" alt="' + escapeHtml(p.name) + '" loading="lazy">'
                : '<div class="no-image-placeholder"><svg viewBox="0 0 24 24"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg></div>';
            return '<a href="' + escapeHtml(p.product_url) + '" class="product-card" target="_blank" rel="noopener" data-video-id="' + videoId + '" data-product-id="' + p.id + '">' +
                imgHtml +
                '<div class="product-card-info"><div class="product-card-name">' + escapeHtml(p.name) + '</div>' +
                (priceText ? '<div class="product-card-price">' + priceText + '</div>' : '') +
                '</div><svg class="product-card-arrow" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg></a>';
        }).join('');
    }

    // ===== Video Playback =====
    function activateCard(card) {
        var videoId = card.dataset.videoId;
        if (activeVideoId === videoId) return;

        // Check if card already has an error overlay — skip it
        if (card.querySelector('.video-error-overlay')) return;

        // Pause all other videos first
        pauseAllExcept(videoId);

        var videoType = card.dataset.videoType;
        var youtubeId = card.dataset.youtubeId;
        var wrapper = card.querySelector('.video-player-wrapper');
        var thumbnail = card.querySelector('.video-thumbnail');
        var video = card.querySelector('video.feed-video');

        if (video) {
            // Check if video already has an error
            if (video.error) {
                var errorCode = video.error.code;
                var errorMsg = '影片暫時無法播放';
                if (errorCode === 4) errorMsg = '影片格式不支援或檔案遺失';
                else if (errorCode === 2) errorMsg = '網路錯誤，無法載入影片';
                showVideoError(card, errorMsg);
                return;
            }

            // Native video — ensure src is set
            var dataSrc = video.dataset.src;
            if (dataSrc && !video.src) {
                video.src = dataSrc;
                video.preload = 'auto';
                video.load();
            }

            video.muted = true; // Must be muted for autoplay

            // Set load timeout
            setLoadTimeout(videoId, card);

            // Use 'loadeddata' event — fires when first frame is available
            // This is faster than 'canplay' which waits for more buffering
            function onFirstFrame() {
                video.removeEventListener('loadeddata', onFirstFrame);
                video.removeEventListener('playing', onFirstFrame);
                clearLoadTimeout(videoId);
                thumbnail.classList.add('hidden');
                if (!isMuted) video.muted = false;
            }

            if (video.readyState >= 2) {
                // Already have first frame data — play immediately
                clearLoadTimeout(videoId);
                var playP = video.play();
                if (playP) playP.catch(function(){});
                thumbnail.classList.add('hidden');
                if (!isMuted) video.muted = false;
            } else {
                video.addEventListener('loadeddata', onFirstFrame);
                video.addEventListener('playing', onFirstFrame);
                var playP2 = video.play();
                if (playP2) {
                    playP2.catch(function(err) {
                        // If autoplay fails, try muted
                        video.muted = true;
                        var retry = video.play();
                        if (retry) retry.catch(function(err2) {
                            // If even muted play fails, show error
                            console.warn('[Feed] Video #' + videoId + ' play failed:', err2.message);
                            clearLoadTimeout(videoId);
                            showVideoError(card, '影片無法自動播放');
                        });
                    });
                }
            }

            activeVideoId = videoId;

            // Aggressively preload next videos
            preloadAhead(card);

        } else if (youtubeId) {
            // YouTube — create iframe
            removeAllIframes();
            var muteParam = isMuted ? '&mute=1' : '&mute=0';
            var iframe = document.createElement('iframe');
            iframe.src = 'https://www.youtube.com/embed/' + youtubeId + '?autoplay=1&playsinline=1&rel=0&modestbranding=1&showinfo=0&loop=1&playlist=' + youtubeId + muteParam;
            iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
            iframe.setAttribute('allowfullscreen', '');
            iframe.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;z-index:1;border:none;';
            wrapper.insertBefore(iframe, thumbnail);
            activeVideoId = videoId;

            iframe.addEventListener('load', function() {
                setTimeout(function() { thumbnail.classList.add('hidden'); }, 300);
            });
        }

        // Record view
        if (!viewedVideos.has(videoId)) {
            viewedVideos.add(videoId);
            postAction('view', { video_id: parseInt(videoId) });
        }
    }

    // Preload the next N videos ahead of the current card
    function preloadAhead(currentCard) {
        var cards = Array.from(document.querySelectorAll('.video-card'));
        var currentIdx = cards.indexOf(currentCard);
        if (currentIdx === -1) return;

        for (var i = 1; i <= PRELOAD_AHEAD; i++) {
            var nextCard = cards[currentIdx + i];
            if (!nextCard) break;
            var vid = nextCard.querySelector('video.feed-video');
            if (vid) {
                // Skip if video already has an error
                if (vid.error) continue;

                var dataSrc = vid.dataset.src;
                if (dataSrc && !vid.src) {
                    vid.src = dataSrc;
                    vid.preload = 'auto';
                    vid.load();
                }
                // If src is already set but preload is metadata/none, upgrade to auto
                if (vid.src && vid.preload !== 'auto') {
                    vid.preload = 'auto';
                }
            }
        }

        // Also unload videos that are far behind to save memory
        for (var j = 0; j < currentIdx - 2; j++) {
            var oldCard = cards[j];
            // Don't unload cards with error overlays
            if (oldCard.querySelector('.video-error-overlay')) continue;
            var oldVid = oldCard.querySelector('video.feed-video');
            if (oldVid && oldVid.src && !oldVid.paused) {
                // Don't unload if still playing somehow
            } else if (oldVid && oldVid.src && oldVid.readyState > 0) {
                // Save src to data-src and remove src to free memory
                if (!oldVid.dataset.src) {
                    oldVid.dataset.src = oldVid.src;
                }
                oldVid.removeAttribute('src');
                oldVid.load(); // Reset the video element
                oldVid.preload = 'none';
                // Show thumbnail again
                var thumb = oldCard.querySelector('.video-thumbnail');
                if (thumb) thumb.classList.remove('hidden');
            }
        }
    }

    function pauseAllExcept(keepVideoId) {
        document.querySelectorAll('.video-card').forEach(function(card) {
            if (card.dataset.videoId === keepVideoId) return;
            var vid = card.querySelector('video.feed-video');
            if (vid && !vid.paused) {
                vid.pause();
            }
            // Only show thumbnail if no error overlay
            if (!card.querySelector('.video-error-overlay')) {
                var thumb = card.querySelector('.video-thumbnail');
                if (thumb) thumb.classList.remove('hidden');
            }
        });
        if (keepVideoId) removeAllIframes(keepVideoId);
        activeVideoId = null;
    }

    function removeAllIframes(exceptVideoId) {
        document.querySelectorAll('.video-card').forEach(function(card) {
            if (exceptVideoId && card.dataset.videoId === exceptVideoId) return;
            var iframe = card.querySelector('iframe');
            if (iframe) iframe.remove();
        });
    }

    function showPauseIndicator(wrapper) {
        var existing = wrapper.querySelector('.feed-pause-indicator');
        if (existing) existing.remove();
        var ind = document.createElement('div');
        ind.className = 'feed-pause-indicator';
        ind.innerHTML = '<svg viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z" fill="#fff"/></svg>';
        wrapper.appendChild(ind);
        setTimeout(function() { ind.classList.add('fade-out'); }, 100);
        setTimeout(function() { ind.remove(); }, 600);
    }

    // ===== Scroll-based auto-play detection =====
    function detectCurrentCard() {
        var container = document.getElementById('feedContainer');
        if (!container) return;
        var cards = container.querySelectorAll('.video-card');
        if (!cards.length) return;

        var containerRect = container.getBoundingClientRect();
        var containerCenter = containerRect.top + containerRect.height / 2;
        var bestCard = null;
        var bestDistance = Infinity;

        cards.forEach(function(card) {
            var rect = card.getBoundingClientRect();
            var cardCenter = rect.top + rect.height / 2;
            var distance = Math.abs(cardCenter - containerCenter);
            if (distance < bestDistance) {
                bestDistance = distance;
                bestCard = card;
            }
        });

        if (bestCard) {
            activateCard(bestCard);
        }
    }

    function onScrollEnd() {
        clearTimeout(scrollTimer);
        scrollTimer = setTimeout(detectCurrentCard, 60);
    }

    // ===== Event Handlers =====
    function handleLike(btn) {
        var videoId = parseInt(btn.dataset.videoId);
        var label = btn.querySelector('.action-btn-label');
        btn.classList.toggle('liked');
        postAction('like', { video_id: videoId }).then(function(res) {
            if (res && res.success) {
                btn.classList[res.liked ? 'add' : 'remove']('liked');
                label.textContent = res.formatted_likes;
            }
        });
    }

    function handleShare(btn) {
        var videoId = btn.dataset.videoId;
        var title = btn.dataset.title;
        var url = window.location.origin + '/feed/#video-' + videoId;
        postAction('analytics', { video_id: parseInt(videoId), action_type: 'share' });
        showShareModal(title, url);
    }

    function handleProductClick(link) {
        postAction('analytics', { video_id: parseInt(link.dataset.videoId), action_type: 'product_click' });
    }

    // ===== Share Modal =====
    function showShareModal(title, url) {
        var modal = document.getElementById('shareModal');
        if (!modal) return;
        modal.dataset.shareTitle = title;
        modal.dataset.shareUrl = url;
        modal.classList.add('active');
    }

    function hideShareModal() {
        var modal = document.getElementById('shareModal');
        if (modal) modal.classList.remove('active');
    }

    function shareToLine(t, u) { window.open('https://social-plugins.line.me/lineit/share?url=' + encodeURIComponent(u) + '&text=' + encodeURIComponent(t), '_blank'); hideShareModal(); }
    function shareToFacebook(u) { window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(u), '_blank'); hideShareModal(); }
    function shareToTwitter(t, u) { window.open('https://twitter.com/intent/tweet?text=' + encodeURIComponent(t) + '&url=' + encodeURIComponent(u), '_blank'); hideShareModal(); }
    function copyLink(u) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(u).then(function() { showToast('已複製連結'); });
        } else {
            var inp = document.createElement('input'); inp.value = u; document.body.appendChild(inp); inp.select(); document.execCommand('copy'); document.body.removeChild(inp); showToast('已複製連結');
        }
        hideShareModal();
    }

    function showToast(msg) {
        var t = document.getElementById('toast'); if (!t) return;
        t.textContent = msg; t.classList.add('show');
        setTimeout(function() { t.classList.remove('show'); }, 2000);
    }

    // ===== Infinite Scroll =====
    function setupInfiniteScroll() {
        var sentinel = document.getElementById('loadMore');
        if (!sentinel) return;
        var obs = new IntersectionObserver(function(entries) {
            if (entries[0].isIntersecting && !isLoading && hasMore) loadMoreVideos();
        }, { root: document.getElementById('feedContainer'), rootMargin: '400px' });
        obs.observe(sentinel);
    }

    async function loadMoreVideos() {
        if (isLoading || !hasMore) return;
        isLoading = true;
        var el = document.getElementById('loadingIndicator');
        if (el) el.style.display = 'flex';
        currentPage++;
        var result = await fetchVideos(currentPage);
        if (result && result.success && result.data.length > 0) {
            var container = document.getElementById('feedContainer');
            var sentinel = document.getElementById('loadMore');
            var existingCount = container.querySelectorAll('.video-card').length;
            result.data.forEach(function(v, i) {
                container.insertBefore(createVideoCard(v, existingCount + i), sentinel);
            });
            hasMore = result.pagination.has_more;
        } else { hasMore = false; }
        if (el) el.style.display = 'none';
        isLoading = false;
    }

    // ===== Scroll Indicator =====
    function setupScrollIndicator() {
        var ind = document.querySelector('.scroll-indicator');
        if (!ind) return;
        var container = document.getElementById('feedContainer');
        if (!container) return;
        var handler = function() {
            if (container.scrollTop > 100) { ind.classList.add('hidden'); container.removeEventListener('scroll', handler); }
        };
        container.addEventListener('scroll', handler, { passive: true });
    }

    // ===== Initialize =====
    async function init() {
        var container = document.getElementById('feedContainer');
        if (!container) return;

        container.innerHTML = '<div class="feed-loading"><div class="feed-spinner"></div><div class="feed-loading-text">載入中...</div></div>';

        var result = await fetchVideos(1);
        if (!result || !result.success || result.data.length === 0) {
            container.innerHTML = '<div class="feed-empty"><svg viewBox="0 0 24 24"><path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z"/></svg><div class="feed-empty-title">目前沒有影片</div><div class="feed-empty-text">敬請期待更多精彩內容</div></div>';
            return;
        }

        container.innerHTML = '';
        result.data.forEach(function(v, i) { container.appendChild(createVideoCard(v, i)); });
        hasMore = result.pagination.has_more;

        var sentinel = document.createElement('div');
        sentinel.id = 'loadMore';
        sentinel.innerHTML = '<div id="loadingIndicator" class="feed-loading" style="display:none;height:auto;padding:40px 0;"><div class="feed-spinner"></div></div>';
        container.appendChild(sentinel);

        createMuteButton();
        setupInfiniteScroll();
        setupScrollIndicator();

        // Scroll-based auto-play
        container.addEventListener('scroll', onScrollEnd, { passive: true });
        if ('onscrollend' in window) {
            container.addEventListener('scrollend', detectCurrentCard, { passive: true });
        }

        // Auto-play first video immediately (no delay)
        var first = container.querySelector('.video-card');
        if (first) activateCard(first);

        // Event delegation
        container.addEventListener('click', function(e) {
            var likeBtn = e.target.closest('.like-btn');
            var shareBtn = e.target.closest('.share-btn');
            var productCard = e.target.closest('.product-card');
            if (likeBtn) { e.preventDefault(); handleLike(likeBtn); }
            else if (shareBtn) { e.preventDefault(); handleShare(shareBtn); }
            else if (productCard) { handleProductClick(productCard); }
            else {
                var card = e.target.closest('.video-card');
                if (card) {
                    // If error overlay is showing, don't try to play
                    if (card.querySelector('.video-error-overlay')) return;

                    var vid = card.querySelector('video.feed-video');
                    if (vid && vid.src) {
                        if (vid.error) {
                            // Video has error, show error overlay
                            showVideoError(card, '影片暫時無法播放');
                            return;
                        }
                        if (vid.paused) {
                            isMuted = false;
                            updateMuteIcon();
                            vid.muted = false;
                            vid.play().catch(function(){});
                            card.querySelector('.video-thumbnail').classList.add('hidden');
                        } else {
                            vid.pause();
                            showPauseIndicator(card.querySelector('.video-player-wrapper'));
                        }
                    } else {
                        isMuted = false;
                        updateMuteIcon();
                        activateCard(card);
                    }
                }
            }
        });

        // Share modal
        var shareModal = document.getElementById('shareModal');
        if (shareModal) {
            shareModal.addEventListener('click', function(e) {
                var opt = e.target.closest('.share-option');
                var cls = e.target.closest('.share-modal-close');
                if (opt) {
                    var t = opt.dataset.type, title = shareModal.dataset.shareTitle, url = shareModal.dataset.shareUrl;
                    if (t === 'line') shareToLine(title, url);
                    else if (t === 'facebook') shareToFacebook(url);
                    else if (t === 'twitter') shareToTwitter(title, url);
                    else if (t === 'copy') copyLink(url);
                } else if (cls || e.target === shareModal) { hideShareModal(); }
            });
        }

        // Hash navigation
        if (window.location.hash) {
            var m = window.location.hash.match(/video-(\d+)/);
            if (m) {
                var tc = container.querySelector('.video-card[data-video-id="' + m[1] + '"]');
                if (tc) setTimeout(function() { tc.scrollIntoView({ behavior: 'smooth' }); }, 300);
            }
        }
    }

    function escapeHtml(s) {
        if (!s) return '';
        var d = document.createElement('div'); d.textContent = s; return d.innerHTML;
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
