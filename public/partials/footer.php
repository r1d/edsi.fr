  <footer class="site-footer">
    <div class="container footer-row">
      <span>EDSI - Etudes et Développement de Solutions Informatiques</span>
      <a href="/legal">Mentions légales</a>
    </div>
  </footer>
  <script>
    (function () {
      var toggle = document.querySelector(".menu-toggle");
      var menu = document.getElementById("site-menu");
      var header = document.querySelector(".site-header");

      var sectionByPath = {
        "/services": "services",
        "/produits": "projet-type",
        "/contact": "contact"
      };
      var pathBySection = {
        "services": "/services",
        "projet-type": "/produits",
        "contact": "/contact",
        "accueil": "/",
        "developpement": "/"
      };

      function scrollToId(targetId, behavior) {
        var target = document.getElementById(targetId);
        if (!target) return false;
        var headerOffset = header ? header.offsetHeight : 0;
        var y = target.getBoundingClientRect().top + window.scrollY - headerOffset - 10;
        window.scrollTo({ top: Math.max(0, y), behavior: behavior || "smooth" });
        return true;
      }

      function closeMenu() {
        if (menu && menu.classList.contains("is-open")) {
          menu.classList.remove("is-open");
          if (toggle) {
            toggle.setAttribute("aria-expanded", "false");
          }
        }
      }

      function normalizePath(pathname) {
        if (!pathname || pathname === "/") return "/";
        return pathname.replace(/\/+$/, "") || "/";
      }

      if (toggle && menu) {
        toggle.addEventListener("click", function () {
          var opened = menu.classList.toggle("is-open");
          toggle.setAttribute("aria-expanded", opened ? "true" : "false");
        });
      }

      var scrollLinks = document.querySelectorAll(
        'a[href^="/#"], a[href^="#"], a[href="/services"], a[href="/produits"], a[href="/contact"]'
      );
      scrollLinks.forEach(function (link) {
        link.addEventListener("click", function (event) {
          var href = link.getAttribute("href") || "";
          var targetId = null;
          var nextPath = null;

          if (href.charAt(0) === "#" || href.indexOf("/#") === 0) {
            var hashIndex = href.indexOf("#");
            targetId = href.slice(hashIndex + 1);
            nextPath = pathBySection[targetId] || ("/" + (targetId ? "#" + targetId : ""));
          } else {
            nextPath = normalizePath(href);
            targetId = sectionByPath[nextPath] || null;
          }

          if (!targetId || !document.getElementById(targetId)) return;

          event.preventDefault();
          scrollToId(targetId, "smooth");
          if (nextPath) {
            history.pushState(null, "", nextPath);
          }
          closeMenu();
        });
      });

      var initialSection =
        <?= json_encode($scrollTo ?? null, JSON_UNESCAPED_UNICODE) ?> ||
        sectionByPath[normalizePath(window.location.pathname)] ||
        (window.location.hash ? window.location.hash.slice(1) : null);

      if (initialSection) {
        window.setTimeout(function () {
          scrollToId(initialSection, "auto");
        }, 0);
      }
    })();
  </script>
</body>
</html>
