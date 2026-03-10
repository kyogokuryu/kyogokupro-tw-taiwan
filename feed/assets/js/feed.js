/**
 * Feed - TikTok-style Video Feed
 * tw.kyogokupro.com/feed/
 * Supports: YouTube, uploaded video files (MP4/WebM/MOV)
 */

(function() {
    'use strict';

    const API_BASE = '/feed/api/';
    let currentPage = 1;
    let isLoading = false;
    let hasMore = true;
    let activeVideoId = null;
    let activePlayer = null;
    let observer = null;
    let viewedVideos = new Set();

    // ===== API Functions =====
    async function fetchVideos(page) {
        const res = await fetch(API_BASE + '?action=videos&page=' + page);
        return await res.json();
    }

    async function postAction(action, data) {
        try {
            const res = await fetch(API_BASE + '?action=' + action, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            return await res.json();
        } catch (e) {
            console.error('API error:', e);
            return null;
        }
    }

    // ===== Render Functions =====
    function createVideoCard(video) {
        const card = document.createElement('div');
        card.className = 'video-card';
        card.dataset.videoId = video.id;
        card.dataset.youtubeId = video.youtube_id || '';
        card.dataset.videoType = video.video_type || 'youtube';
        card.dataset.videoUrl = video.video_url || '';
        card.dataset.videoFile = video.video_file_url || '';

        const thumbnailUrl = video.thumbnail || '';
        const productsHtml = (video.products && video.products.length > 0) 
            ? createProductsHtml(video.products, video.id) 
            : '';

        // Use AI-generated title/description if available
        const displayTitle = video.display_title || video.title;
        const displayDesc = video.display_description || video.description;

        card.innerHTML = 
            '<div class="video-player-wrapper" data-video-id="' + video.id + '">' +
                '<img class="video-thumbnail" src="' + escapeHtml(thumbnailUrl) + '" alt="' + escapeHtml(displayTitle) + '" loading="lazy">' +
                '<div class="video-play-btn" role="button" aria-label="播放影片">' +
                    '<svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>' +
                '</div>' +
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
                '<div class="product-card-info">' +
                    '<div class="product-card-name">' + escapeHtml(p.name) + '</div>' +
                    (priceText ? '<div class="product-card-price">' + priceText + '</div>' : '') +
                '</div>' +
                '<svg class="product-card-arrow" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>' +
            '</a>';
        }).join('');
    }

    // ===== Video Player =====
    function playVideo(card) {
        var videoId = card.dataset.videoId;
        var videoType = card.dataset.videoType;
        var youtubeId = card.dataset.youtubeId;
        var videoUrl = card.dataset.videoUrl;
        var videoFile = card.dataset.videoFile;
        var wrapper = card.querySelector('.video-player-wrapper');
        var thumbnail = card.querySelector('.video-thumbnail');
        var playBtn = card.querySelector('.video-play-btn');

        // Stop any other playing video
        stopAllVideos();

        if (videoType === 'upload' && videoFile) {
            // Play uploaded video file
            var video = document.createElement('video');
            video.src = videoFile;
            video.autoplay = true;
            video.controls = true;
            video.playsInline = true;
            video.loop = true;
            video.muted = false;
            video.style.position = 'absolute';
            video.style.top = '0';
            video.style.left = '0';
            video.style.width = '100%';
            video.style.height = '100%';
            video.style.objectFit = 'contain';
            video.style.background = '#000';
            video.style.zIndex = '1';
            
            wrapper.appendChild(video);
            thumbnail.classList.add('hidden');
            playBtn.classList.add('hidden');
            activePlayer = video;
            activeVideoId = videoId;
            
            video.play().catch(function() {
                // Autoplay blocked, try muted
                video.muted = true;
                video.play();
            });
        } else if (videoType === 'direct' && videoUrl) {
            // Play direct video URL
            var video = document.createElement('video');
            video.src = videoUrl;
            video.autoplay = true;
            video.controls = true;
            video.playsInline = true;
            video.loop = true;
            video.style.position = 'absolute';
            video.style.top = '0';
            video.style.left = '0';
            video.style.width = '100%';
            video.style.height = '100%';
            video.style.objectFit = 'contain';
            video.style.background = '#000';
            video.style.zIndex = '1';
            
            wrapper.appendChild(video);
            thumbnail.classList.add('hidden');
            playBtn.classList.add('hidden');
            activePlayer = video;
            activeVideoId = videoId;
            
            video.play().catch(function() {
                video.muted = true;
                video.play();
            });
        } else if (youtubeId) {
            // Play YouTube embed
            var iframe = document.createElement('iframe');
            iframe.src = 'https://www.youtube.com/embed/' + youtubeId + '?autoplay=1&playsinline=1&rel=0&modestbranding=1&showinfo=0';
            iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
            iframe.setAttribute('allowfullscreen', '');
            iframe.style.position = 'absolute';
            iframe.style.top = '0';
            iframe.style.left = '0';
            iframe.style.width = '100%';
            iframe.style.height = '100%';
            iframe.style.zIndex = '1';

            wrapper.appendChild(iframe);
            thumbnail.classList.add('hidden');
            playBtn.classList.add('hidden');
            activePlayer = iframe;
            activeVideoId = videoId;
        } else {
            return;
        }

        // Record view
        if (!viewedVideos.has(videoId)) {
            viewedVideos.add(videoId);
            postAction('view', { video_id: parseInt(videoId) });
        }
    }

    function stopAllVideos() {
        // Remove iframes (YouTube)
        document.querySelectorAll('.video-player-wrapper iframe').forEach(function(iframe) {
            iframe.remove();
        });
        // Remove/pause video elements (uploaded files)
        document.querySelectorAll('.video-player-wrapper video').forEach(function(video) {
            video.pause();
            video.remove();
        });
        document.querySelectorAll('.video-thumbnail.hidden').forEach(function(thumb) {
            thumb.classList.remove('hidden');
        });
        document.querySelectorAll('.video-play-btn.hidden').forEach(function(btn) {
            btn.classList.remove('hidden');
        });
        activePlayer = null;
        activeVideoId = null;
    }

    // ===== Event Handlers =====
    function handleLike(btn) {
        var videoId = parseInt(btn.dataset.videoId);
        var label = btn.querySelector('.action-btn-label');
        
        btn.classList.toggle('liked');
        
        postAction('like', { video_id: videoId }).then(function(res) {
            if (res && res.success) {
                if (res.liked) {
                    btn.classList.add('liked');
                } else {
                    btn.classList.remove('liked');
                }
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
        var videoId = parseInt(link.dataset.videoId);
        var productId = parseInt(link.dataset.productId);
        postAction('analytics', { video_id: videoId, action_type: 'product_click' });
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

    function shareToLine(title, url) {
        window.open('https://social-plugins.line.me/lineit/share?url=' + encodeURIComponent(url) + '&text=' + encodeURIComponent(title), '_blank');
        hideShareModal();
    }

    function shareToFacebook(url) {
        window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url), '_blank');
        hideShareModal();
    }

    function shareToTwitter(title, url) {
        window.open('https://twitter.com/intent/tweet?text=' + encodeURIComponent(title) + '&url=' + encodeURIComponent(url), '_blank');
        hideShareModal();
    }

    function copyLink(url) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(function() {
                showToast('已複製連結');
            });
        } else {
            var input = document.createElement('input');
            input.value = url;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
            showToast('已複製連結');
        }
        hideShareModal();
    }

    function showToast(message) {
        var toast = document.getElementById('toast');
        if (!toast) return;
        toast.textContent = message;
        toast.classList.add('show');
        setTimeout(function() { toast.classList.remove('show'); }, 2000);
    }

    // ===== Intersection Observer =====
    function setupObserver() {
        observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    var card = entry.target;
                    var videoId = card.dataset.videoId;
                    
                    if (!viewedVideos.has(videoId)) {
                        viewedVideos.add(videoId);
                        postAction('view', { video_id: parseInt(videoId) });
                    }
                } else {
                    // Stop video when scrolled out
                    var iframe = entry.target.querySelector('iframe');
                    var video = entry.target.querySelector('video');
                    if (iframe) {
                        iframe.remove();
                        var thumb = entry.target.querySelector('.video-thumbnail');
                        var btn = entry.target.querySelector('.video-play-btn');
                        if (thumb) thumb.classList.remove('hidden');
                        if (btn) btn.classList.remove('hidden');
                    }
                    if (video) {
                        video.pause();
                        video.remove();
                        var thumb = entry.target.querySelector('.video-thumbnail');
                        var btn = entry.target.querySelector('.video-play-btn');
                        if (thumb) thumb.classList.remove('hidden');
                        if (btn) btn.classList.remove('hidden');
                    }
                }
            });
        }, { threshold: 0.6 });
    }

    // ===== Infinite Scroll =====
    function setupInfiniteScroll() {
        var sentinel = document.getElementById('loadMore');
        if (!sentinel) return;

        var scrollObserver = new IntersectionObserver(function(entries) {
            if (entries[0].isIntersecting && !isLoading && hasMore) {
                loadMoreVideos();
            }
        }, { rootMargin: '200px' });

        scrollObserver.observe(sentinel);
    }

    async function loadMoreVideos() {
        if (isLoading || !hasMore) return;
        isLoading = true;
        
        var loadingEl = document.getElementById('loadingIndicator');
        if (loadingEl) loadingEl.style.display = 'flex';

        currentPage++;
        var result = await fetchVideos(currentPage);

        if (result && result.success && result.data.length > 0) {
            var container = document.getElementById('feedContainer');
            var sentinel = document.getElementById('loadMore');
            
            result.data.forEach(function(video) {
                var card = createVideoCard(video);
                container.insertBefore(card, sentinel);
                observer.observe(card);
            });

            hasMore = result.pagination.has_more;
        } else {
            hasMore = false;
        }

        if (loadingEl) loadingEl.style.display = 'none';
        isLoading = false;
    }

    // ===== Scroll Indicator =====
    function setupScrollIndicator() {
        var indicator = document.querySelector('.scroll-indicator');
        if (!indicator) return;

        var scrollHandler = function() {
            if (window.scrollY > 100) {
                indicator.classList.add('hidden');
                window.removeEventListener('scroll', scrollHandler);
            }
        };
        window.addEventListener('scroll', scrollHandler, { passive: true });
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
        setupObserver();

        result.data.forEach(function(video) {
            var card = createVideoCard(video);
            container.appendChild(card);
            observer.observe(card);
        });

        hasMore = result.pagination.has_more;

        var sentinel = document.createElement('div');
        sentinel.id = 'loadMore';
        sentinel.innerHTML = '<div id="loadingIndicator" class="feed-loading" style="display:none;height:auto;padding:40px 0;"><div class="feed-spinner"></div></div>';
        container.appendChild(sentinel);

        setupInfiniteScroll();
        setupScrollIndicator();

        // Event delegation
        container.addEventListener('click', function(e) {
            var playBtn = e.target.closest('.video-play-btn');
            var thumbnail = e.target.closest('.video-thumbnail');
            var likeBtn = e.target.closest('.like-btn');
            var shareBtn = e.target.closest('.share-btn');
            var productCard = e.target.closest('.product-card');

            if (playBtn || thumbnail) {
                e.preventDefault();
                var card = e.target.closest('.video-card');
                if (card) playVideo(card);
            } else if (likeBtn) {
                e.preventDefault();
                handleLike(likeBtn);
            } else if (shareBtn) {
                e.preventDefault();
                handleShare(shareBtn);
            } else if (productCard) {
                handleProductClick(productCard);
            }
        });

        // Share modal events
        var shareModal = document.getElementById('shareModal');
        if (shareModal) {
            shareModal.addEventListener('click', function(e) {
                var option = e.target.closest('.share-option');
                var closeBtn = e.target.closest('.share-modal-close');
                
                if (option) {
                    var type = option.dataset.type;
                    var title = shareModal.dataset.shareTitle;
                    var url = shareModal.dataset.shareUrl;
                    
                    switch (type) {
                        case 'line': shareToLine(title, url); break;
                        case 'facebook': shareToFacebook(url); break;
                        case 'twitter': shareToTwitter(title, url); break;
                        case 'copy': copyLink(url); break;
                    }
                } else if (closeBtn || e.target === shareModal) {
                    hideShareModal();
                }
            });
        }

        // Handle hash navigation
        if (window.location.hash) {
            var match = window.location.hash.match(/video-(\d+)/);
            if (match) {
                var targetCard = document.querySelector('.video-card[data-video-id="' + match[1] + '"]');
                if (targetCard) {
                    setTimeout(function() {
                        targetCard.scrollIntoView({ behavior: 'smooth' });
                    }, 300);
                }
            }
        }
    }

    // ===== Utility =====
    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Start
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
