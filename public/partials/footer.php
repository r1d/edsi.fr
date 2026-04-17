  <footer class="site-footer">
    <div class="container footer-row">
      <span>EDSI - Etudes et Développement de Solutions Informatiques</span>
      <a href="/mentions-legales.php">Mentions légales</a>
    </div>
  </footer>
  <script>
    (function () {
      var toggle = document.querySelector(".menu-toggle");
      var menu = document.getElementById("site-menu");
      var header = document.querySelector(".site-header");

      if (toggle && menu) {
        toggle.addEventListener("click", function () {
          var opened = menu.classList.toggle("is-open");
          toggle.setAttribute("aria-expanded", opened ? "true" : "false");
        });
      }

      var scrollLinks = document.querySelectorAll('a[href^="/#"], a[href^="#"]');
      scrollLinks.forEach(function (link) {
        link.addEventListener("click", function (event) {
          var href = link.getAttribute("href") || "";
          var hashIndex = href.indexOf("#");
          if (hashIndex === -1) return;
          var targetId = href.slice(hashIndex + 1);
          if (!targetId) return;
          var target = document.getElementById(targetId);
          if (!target) return;

          event.preventDefault();
          var headerOffset = header ? header.offsetHeight : 0;
          var y = target.getBoundingClientRect().top + window.scrollY - headerOffset - 10;
          window.scrollTo({ top: Math.max(0, y), behavior: "smooth" });
          var path = window.location.pathname || "/";
          history.replaceState(null, "", path + "#" + targetId);

          if (menu && menu.classList.contains("is-open")) {
            menu.classList.remove("is-open");
            if (toggle) {
              toggle.setAttribute("aria-expanded", "false");
            }
          }
        });
      });
    })();
  </script>
</body>
</html>
