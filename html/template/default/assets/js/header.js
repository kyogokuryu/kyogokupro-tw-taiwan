$(document).ready(function () {
  let lockedScrollTop = 0;
  const $searchInput = $("#box-search");
  const $searchBox = $("#hinr .box-search");
  const $logo = $("#hinr .logo");
  const $gnav = $("#hinr .gnav");
  const $upper = $("#hinr .upper");
  const $backButton = $("#hinr .back-button");
  const $suggestionsBox = $("#search-suggestions");
  const $formSearch = $("#product-form");
  const $searchHistory = $("#search-history");
  const isMobile = () => window.matchMedia("(max-width: 768px)").matches;

  // show and hidden pop-up
  $(document).on("click", function (event) {
    $searchInput[0] === event.target ||
      $suggestionsBox[0].contains(event.target) ||
      collapseSearch();
  });

  function expandSearch(e) {
    $upper.addClass("search-expanded");

    if (isMobile()) {
      $logo.hide();
      $gnav.hide();
      $backButton.show();
    }

    lockedScrollTop = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
    
    $("html, body").addClass("no-scroll");
    $("body").css({
      position: "fixed",
      width: "100%",
      top: `-${lockedScrollTop}px`,
      left: "0",
      right: "0",
      overflow: "hidden"
    });
    $("html").css({
      overflow: "hidden",
      height: "100%"
    });

    // Prevent touch scroll on iOS while modal is open
    document.addEventListener('touchmove', preventTouchScroll, { passive: false });

    loadSuggestions();
    $suggestionsBox.show();
  }

  function collapseSearch() {
    $upper.removeClass("search-expanded");
    $searchInput.blur();
    if (isMobile()) {
      $logo.show();
      $gnav.show();
      $backButton.hide();
    }

    $("html, body").removeClass("no-scroll");
    $("body").css({ 
      position: "", 
      width: "", 
      top: "", 
      left: "", 
      right: "",
      overflow: ""
    });
    $("html").css({
      overflow: "",
      height: ""
    });
    
    // Restore scroll position
    if (lockedScrollTop > 0) {
      window.scrollTo(0, lockedScrollTop);
      document.documentElement.scrollTop = lockedScrollTop;
      document.body.scrollTop = lockedScrollTop;
    }

    $suggestionsBox.hide();
    document.removeEventListener('touchmove', preventTouchScroll, { passive: false });
    lockedScrollTop = 0;
  }

  $searchInput.on("click", function (e) {
    expandSearch(e);
  });

  $backButton.on("click", function (e) {
    collapseSearch();
  });


  // handle search
  $formSearch.on("submit", function (e) {
    e.stopImmediatePropagation();
    handleSearch();

    loadingOverlay("hide");
  });

  function handleSearch() {
    const query = $("#box-search").val().trim();
    query && saveSearchHistory(query);
  }

  // Disable background scrolling on touch devices when modal is open
  function preventTouchScroll(e) {
    if (!$searchBox[0].contains(e.target) && !$suggestionsBox[0].contains(e.target)) {
      e.preventDefault();
    }
  }

  // save search history data
  function saveSearchHistory(query) {
    let histories = JSON.parse(localStorage.getItem("search_history")) || [];
    histories = histories.filter((item) => item !== query);
    histories.unshift(query);

    histories.length > 10 ? histories.slice(0, 10) : histories;

    localStorage.setItem("search_history", JSON.stringify(histories));
  }

  // Get product
  function fetchProducts({ url, method = "GET", payload = null, target }) {
    $.ajax({
      url: url,
      method: method,
      contentType: "application/json",
      data: payload ? JSON.stringify(payload) : null,
      success: function (data) {
        renderProducts(target, data);
      },
      error: function (xhr, status, err) {
        console.error(`Error fetching ${url}:`, err);
      },
    });
  }

  // Load suggestions
  function loadSuggestions(query = "") {
    renderSearchHistory();
    const recentlyViewedIds =
      JSON.parse(localStorage.getItem("recently_viewed")) || [];

    if (recentlyViewedIds.length === 0) {
      $("#recent-products").html(
        '<div class="suggestion-item">您尚未瀏覽任何商品</div>'
      );
      return;
    }

    // recently viewed products
    fetchProducts({
      url: "/api/recently_viewed",
      method: "POST",
      payload: { ids: recentlyViewedIds },
      target: "#recent-products",
    });

    // popular products
    fetchProducts({
      url: "/api/popular_products",
      method: "GET",
      target: "#popular-products",
    });
  }

  // render data search history
  function renderSearchHistory() {
    const container = $("#search-history");
    let histories = JSON.parse(localStorage.getItem("search_history")) || [];

    if (histories.length === 0) {
      container
        .empty()
        .append('<div class="suggestion-item">沒有搜尋紀錄</div>');
      return;
    }

    container.empty();

    // Only show the 5 most recent search histories
    const recentHistories = histories.slice(0, 5);

    recentHistories.forEach((history) => {
      container.append(`
        <a class="suggestion-item search-history-item">
          <p class="product-name">${history}</p>
        </a>
      `);
    });
  }

  // render product
  function renderProducts(containerId, products) {
    const container = $(containerId);
    container.empty();

    if (products.length === 0) {
      container.html('<div class="suggestion-item">「找不到產品」</div>');
      return;
    }

    if (containerId === "#recent-products") {
      products.forEach((product) => {
        const item = $(`
                  <a href="${
                    product.url
                  }" class="suggestion-item box" data-product-id="${
          product.id
        }">
                    <img src="${product.image}" alt="${product.name}">
                    <div class="product-info">
                        <p class="product-name">${product.name}</p>
                        <p class="product-price">¥${product.price || "---"}</p>
                    </div>
                  </a>
            `);

        container.append(item);
      });
    } else if (containerId === "#popular-products") {
      products.forEach((product, index) => {
        const item = $(`
              <a href="${product.url}" class="suggestion-item" data-product-id="${product.id}">
                    <img src="${product.image}" alt="${product.name}" style="height: auto; margin-right: 10px;">
                    <p class="product-name">${product.name}</p>
                </a>
            `);

        container.append(item);
      });
    } else {
      products.forEach((product) => {
        const item = $(`
                <a href="${product.url}" class="suggestion-item" data-product-id="${product.id}">
                    <img src="${product.image}" alt="${product.name}" style="height: auto; margin-right: 10px;">
                    <p class="product-name">${product.name}</p>
                  </a>
            `);

        container.append(item);
      });
    }
  }

  // logic display ui
  function handleResize() {
    if (!isMobile()) {
      $logo.show();
      $gnav.show();
      $backButton.hide();
      $upper.removeClass("search-expanded");
      $("html, body").removeClass("no-scroll");
      $("body").css({ position: "", width: "", top: "", left: "", right: "", overflow: "" });
      $("html").css({ overflow: "", height: "" });
    } else {
      $logo.show();
      $gnav.show();
      $backButton.hide();
      $upper.removeClass("search-expanded");
      $("html, body").removeClass("no-scroll");
      $("body").css({ position: "", width: "", top: "", left: "", right: "", overflow: "" });
      $("html").css({ overflow: "", height: "" });
    }
  }

  function updateHistoryLink() {
    let ids = JSON.parse(localStorage.getItem("recently_viewed")) || [];
    let href = "/products/history?ids=" + ids.join(",");
    document.getElementById("history-link").href = href;
  }

  (function () {
    $backButton.hide();

    $(document).on("keydown", function (event) {
      if (event.key === "Escape") {
        collapseSearch();
      }
    });

    $searchHistory.on("click", ".search-history-item", function (e) {
      e.preventDefault();

      const value = $(this).find(".product-name").text().trim();
      if (value) {
        $searchInput.val(value);
        $formSearch.trigger("submit");
      }
    });

    const resizeHandlers = [handleResize];
    $(window).on("resize", function (e) {
      resizeHandlers.forEach((fn) => fn(e));
    });

    document.getElementById("history-link").addEventListener("click", updateHistoryLink);
  })();
});
