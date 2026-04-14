<?php
declare(strict_types=1);

$pageTitle = 'EDSI - Etudes et Développement de Solutions Informatiques';
$metaDescription = 'Site vitrine en 2 jours, boutique en ligne en 7 jours et développements web sur mesure. Devis rapide sur simple demande.';
$canonicalUrl = 'https://edsi.fr/';

require __DIR__ . '/partials/head.php';
?>
<main>
  <section class="container hero" id="accueil" style="text-align: center;">
    <h1>Concrétisons ensemble vos idées d'applications</h1>
    <h2>Conception et réalisation de sites internet,<br>de sites marchands et développement sur mesure.</h2>
    <p class="section-text">Société de service informatique performante et dynamique. Devis rapide sur simple demande.</p>
    <div class="cta-row">
      <a href="#contact" class="btn btn-primary">Prendre rendez-vous</a>
      <a href="#contact" class="btn btn-secondary">Demander un devis</a>
      <a href="https://wa.me/590690515880" target="_blank" rel="noopener noreferrer" class="btn btn-whatsapp" aria-label="WhatsApp">
        <img src="/images/whatsapp.jpg" alt="WhatsApp">
      </a>
    </div>
  </section>

  <section class="container card" id="developpement">
    <h2>Concrétisons ensemble vos idées d'applications</h2>
    <p style="margin-top:1rem;">Fort d’une expérience sans cesse renouvelée nous sommes à même d’analyser de façon formelle vos besoins, de vous conseiller des choix ambitieux mais réalistes et de mettre en place des réalisations puissantes et modernes en terme d'organisation et d'exploitation de vos données.</p>
    <div class="grid-3">
      <article class="feature">
        <img src="/images/simple.png" alt="Simple">
        <div>
          <h3>Simple</h3>
          <p>Masquer la compléxité par une ergonomie soignée et intuitive.</p>
        </div>
      </article>
      <article class="feature">
        <img src="/images/utile.png" alt="Utile">
        <div>
          <h3>Utile</h3>
          <p>Placer les utilisateurs au centre du processus de création.</p>
        </div>
      </article>
      <article class="feature">
        <img src="/images/performant.png" alt="Performant">
        <div>
          <h3>Performant</h3>
          <p>Utiliser des solutions à la pointe des évolutions technologiques.</p>
        </div>
      </article>
    </div>
  </section>

  <section class="container card" id="services">
    <h2>Nos services en 3 étapes</h2>
    <p>Dans un contexte en constante évolution, une adaptation permanente est indispensable. Conduire le changement au lieu de le subir est déjà en soi un facteur de progrès, le mettre en œuvre de façon structurée est un facteur de réussite. La conduite par projets bien identifiés permet de planifier cette adaptation tout en mobilisant de façon transversale et innovatrice les compétences de l’entreprise.</p>

    <div class="grid-3 blocks-gap">
      <article class="card feature-card">
        <img src="/images/icon-concept.png" alt="Icône conception service" width="132"">
        <h3>Conception</h3>
        <p><strong>Analyse et conception d'applications</strong></p>
        <p>La conception d'un projet informatique ou analyse préalable est un facteur essentiel à la réussite du projet.</p>
        <p>Conseils et analyse pour la mise place de systèmes d'information. Maintenance et assitance, formations.</p>
      </article>
      <article class="card feature-card">
        <img src="/images/icon-design.png" alt="Icône design service" width="132">
        <h3>Design</h3>
        <p><strong>Design &amp; graphisme de site Web orgonomique</strong></p>
        <p>Design soigné, graphisme fort et ergonomie adaptée.</p>
        <p>Création graphique originale et sur mesure en fonction de la cible et des objectifs.</p>
      </article>
      <article class="card feature-card">
        <img src="/images/icon-development.png" alt="Icône développement service" width="132">
        <h3>Développement</h3>
        <p><strong>Développement de services Web sur mesure</strong></p>
        <p>Réalisation de sites internet, intranet et extranet sur mesure s'appuyant éventuellement sur des outils "open source".</p>
        <p>Réalisation de logiciels de gestion de base de données pour Windows, Mac et Linux.</p>
      </article>
    </div>
  </section>

  <section class="container card" id="projet-type">
    <h2>Projet type</h2>
    <p><strong>Lancement express d’un site vitrine en 2 jours</strong> pour présenter une activité, capter des demandes et professionnaliser la présence en ligne.</p>
    <p><strong>Boutique en ligne en 7 jours</strong> via notre produit sous licence.</p>
    <p>Pour les autres besoins de développement sur mesure, nous contacter. <strong>Devis rapide sur simple demande.</strong></p>
  </section>

  <section class="container card" id="contact">
    <h2>Contact</h2>
    <div id="form-status" class="status" role="status" aria-live="polite"></div>
    <div class="grid-3">
      <form action="/contact.php" method="post" style="grid-column: span 2;">
        <input type="hidden" name="form_started_at" id="form-started-at" value="">
        <div class="hidden-field" aria-hidden="true">
          <label for="website">Ne pas remplir ce champ</label>
          <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
        </div>
        <p>
          <label for="name">Nom</label>
          <input id="name" name="name" type="text" required minlength="2" maxlength="120">
        </p>
        <p>
          <label for="email">Email</label>
          <input id="email" name="email" type="email" required minlength="6" maxlength="180">
        </p>
        <p>
          <label for="subject">Sujet</label>
          <input id="subject" name="subject" type="text" required minlength="3" maxlength="180">
        </p>
        <p>
          <label for="message">Message</label>
          <textarea id="message" name="message" required minlength="10" maxlength="4000"></textarea>
        </p>
        <button class="btn btn-primary" type="submit">Envoyer la demande</button>
      </form>
      <aside>
        <p><strong>Email :</strong> <a href="mailto:hello@edsi.fr">hello@edsi.fr</a></p>
        <p><strong>WhatsApp :</strong></p>
        <p><a href="https://wa.me/590690515880" target="_blank" rel="noopener noreferrer" class="btn btn-whatsapp" aria-label="WhatsApp">
          <img src="/images/whatsapp.jpg" alt="WhatsApp">
        </a></p>
        <p><strong>Devis :</strong> rapide sur simple demande.</p>
      </aside>
    </div>
  </section>
</main>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "Organization",
      "@id": "https://edsi.fr/#organization",
      "name": "EDSI - Etudes et Développement de Solutions Informatiques",
      "url": "https://edsi.fr/",
      "logo": {
        "@type": "ImageObject",
        "url": "https://edsi.fr/images/edsi-143x59.png"
      },
      "contactPoint": [
        {
          "@type": "ContactPoint",
          "contactType": "customer support",
          "email": "hello@edsi.fr",
          "telephone": "+590690515880",
          "availableLanguage": ["fr"]
        }
      ]
    },
    {
      "@type": "LocalBusiness",
      "@id": "https://edsi.fr/#localbusiness",
      "name": "EDSI - Etudes et Développement de Solutions Informatiques",
      "url": "https://edsi.fr/",
      "image": "https://edsi.fr/images/edsi-143x59.png",
      "email": "hello@edsi.fr",
      "telephone": "+590690515880",
      "description": "Création de sites vitrines en 2 jours, boutique en ligne en 7 jours et développement web sur mesure.",
      "priceRange": "$$",
      "currenciesAccepted": "EUR",
      "paymentAccepted": "Virement, Carte bancaire",
      "areaServed": [
        { "@type": "Country", "name": "France" },
        { "@type": "AdministrativeArea", "name": "Guadeloupe" }
      ],
      "sameAs": [
        "https://wa.me/590690515880"
      ],
      "parentOrganization": {
        "@id": "https://edsi.fr/#organization"
      },
      "hasOfferCatalog": {
        "@type": "OfferCatalog",
        "name": "Services EDSI",
        "itemListElement": [
          {
            "@type": "Offer",
            "itemOffered": {
              "@type": "Service",
              "name": "Site vitrine express",
              "description": "Conception et mise en ligne d'un site de présentation d'activité en 2 jours."
            }
          },
          {
            "@type": "Offer",
            "itemOffered": {
              "@type": "Service",
              "name": "Boutique en ligne sous licence",
              "description": "Déploiement d'une boutique en ligne en 7 jours sur base licence."
            }
          },
          {
            "@type": "Offer",
            "itemOffered": {
              "@type": "Service",
              "name": "Développement sur mesure",
              "description": "Réalisation de sites, services web et bases de données adaptés aux besoins métier."
            }
          }
        ]
      }
    },
    {
      "@type": "WebSite",
      "@id": "https://edsi.fr/#website",
      "url": "https://edsi.fr/",
      "name": "edsi.fr",
      "publisher": {
        "@id": "https://edsi.fr/#organization"
      },
      "inLanguage": "fr-FR"
    },
    {
      "@type": "WebPage",
      "@id": "https://edsi.fr/#webpage",
      "url": "https://edsi.fr/",
      "name": "EDSI - Sites internet et développement sur mesure",
      "isPartOf": {
        "@id": "https://edsi.fr/#website"
      },
      "about": {
        "@id": "https://edsi.fr/#localbusiness"
      },
      "inLanguage": "fr-FR"
    }
  ]
}
</script>

<script>
  (function () {
    var field = document.getElementById("form-started-at");
    if (field) {
      field.value = String(Math.floor(Date.now() / 1000));
    }
    var status = document.getElementById("form-status");
    var params = new URLSearchParams(window.location.search);
    var state = params.get("contact");
    if (!status || !state) return;
    if (state === "ok") {
      status.classList.add("success");
      status.textContent = "Votre message a bien été envoyé. Merci, nous revenons vers vous rapidement.";
    } else {
      status.classList.add("error");
      status.textContent = "L'envoi du message a echoué. Merci de nous écrire directement à hello@edsi.fr.";
    }
  })();
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
