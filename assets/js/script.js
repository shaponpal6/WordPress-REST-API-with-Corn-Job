(function () {
  var initSlider = function (sel) {
    var s = { e: {}, f: {}, v: {} };

    s.f.$ = function (sel) {
      return document.querySelectorAll(sel);
    };

    s.f.$1 = function (sel) {
      return document.querySelector(sel);
    };

    s.v.resize_timer = false;

    s.e.nav_h = s.f.$1(sel + " .nav");
    s.e.thumbs_track = s.e.nav_h.querySelector(".glide__slides");
    s.e.thumbs = s.e.nav_h.querySelectorAll(".glide__slide img");

    s.f.getNumThumbs = function () {
      var nav_width = s.e.nav_h.offsetWidth;
      // try to read width attribute if available because it contains the thumbs
      // real width
      var thumb_width =
        s.e.thumbs[0].getAttribute("width") || s.e.thumbs[0].offsetWidth;
      return (
        (nav_width && thumb_width && Math.ceil(nav_width / thumb_width)) || 1
      );
    };

    s.f.initNav = function () {
      // destroy previous instance of the nav
      var startAt = (s.f.nav && s.f.nav.index) || 0;
      var last_slide =
        (s.e.thumbs_track.children.length &&
          s.e.thumbs_track.children.length - 1) ||
        0;
      if (last_slide < startAt) {
        // there are les slides than previous last active slide
        // change to currently available last slide
        startAt = last_slide;
      }
      s.f.nav && s.f.nav.destroy();

      s.f.nav = new Glide(s.e.nav_h, {
        perView: 1,
        focusAt: "center",
        perTouch: 1,
        gap: 5,
        rewind: false,
        startAt: startAt,
      }).mount();
    };

    s.f.updateThumbPages = function () {
      var thumbs_per_page = s.f.getNumThumbs();
      if (s.e.thumbs && thumbs_per_page) {
        var space = 0;
        var nav_width = s.e.nav_h.offsetWidth;
        var num_thumbs = s.e.thumbs.length;
        var page_cnt = 0;
        var pages = [];

        var page = document.createElement("li");
        page.className = "glide__slide";

        var max_width =
          nav_width / (thumbs_per_page - space / nav_width) - space;

        for (var n = 0; n < num_thumbs; n++) {
          if (page_cnt < thumbs_per_page) {
            s.e.thumbs[n].style.maxWidth = max_width + "px";
            (function (n) {
              s.e.thumbs[n].addEventListener("click", function (e) {
                s.f.screen.go("=" + n);
              });
            })(n);
            page.appendChild(s.e.thumbs[n]);
            page_cnt++;

            if (page_cnt < thumbs_per_page) {
              s.e.thumbs[n].style.marginRight = space + "px";
            }
          }
          if (page_cnt >= thumbs_per_page) {
            pages.push(page);
            var page = document.createElement("li");
            page.className = "glide__slide";
            page_cnt = 0;
          }
        }
        if (page_cnt > 0) {
          // add last page
          pages.push(page);
        }

        // empty thumbnail track
        while (s.e.thumbs_track.firstChild) {
          s.e.thumbs_track.removeChild(s.e.thumbs_track.firstChild);
        }
        for (var n = 0; n < pages.length; n++) {
          s.e.thumbs_track.appendChild(pages[n]);
        }
      }

      s.f.initNav();
    };

    s.f.updateActiveThumb = function () {
      // index of active image
      var index = s.f.screen.index;

      // set active css class to correct thumg
      var active = s.e.thumbs_track.querySelector(".active");
      active && active.classList.remove("active");
      s.e.thumbs[index] && s.e.thumbs[index].classList.add("active");

      if (s.f.nav) {
        // make sure that correct thumb page is visible
        var thumbs_per_page = s.f.getNumThumbs();
        var thumb_page = Math.floor(index / thumbs_per_page);

        if (s.f.nav.index != thumb_page) {
          s.f.nav.go("=" + thumb_page);
        }
      }
    };

    s.f.screen = new Glide(sel + " .screen", {
      rewind: false,
    }).mount();
    s.f.screen.on("move", s.f.updateActiveThumb);
    s.f.updateActiveThumb();

    s.f.onResize = function () {
      // throttle thumb size recalculation when resizing
      s.v.resize_timer && clearTimeout(s.v.resize_timer);
      s.v.resize_timer = setTimeout(s.f.updateThumbPages, 500);
    };
    window.addEventListener("resize", s.f.onResize);
    s.f.updateThumbPages();
  };

  document.addEventListener(
    "DOMContentLoaded",
    function () {
      initSlider(".picture-slider");
    },
    false
  );
})();
