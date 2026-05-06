#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════════════════════════
# Tire Tracker — Script d'installation
# Usage : bash install.sh
# ═══════════════════════════════════════════════════════════════════════════
set -euo pipefail

# ── Couleurs ────────────────────────────────────────────────────────────────
GREEN='\033[0;32m'; YELLOW='\033[1;33m'; RED='\033[0;31m'
CYAN='\033[0;36m';  BOLD='\033[1m';      NC='\033[0m'

ok()   { echo -e "${GREEN}  ✔ $*${NC}"; }
warn() { echo -e "${YELLOW}  ⚠ $*${NC}"; }
fail() { echo -e "${RED}  ✘ $*${NC}"; exit 1; }
info() { echo -e "${CYAN}  → $*${NC}"; }

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo ""
echo -e "${BOLD}🏁 Tire Tracker — Installation${NC}"
echo    "   Répertoire : ${SCRIPT_DIR}"
echo    "══════════════════════════════════════════════"

# ── 1. Vérification PHP ─────────────────────────────────────────────────────
echo ""
echo -e "${BOLD}[1/5] Vérification de PHP${NC}"

command -v php &>/dev/null || fail "PHP n'est pas installé. Installez-le avec : sudo apt install php8.3-cli"

PHP_BIN=$(command -v php)
PHP_VER=$(php -r 'echo PHP_VERSION;')
PHP_MAJ=$(php -r 'echo PHP_MAJOR_VERSION;')
PHP_MIN=$(php -r 'echo PHP_MINOR_VERSION;')

[[ "$PHP_MAJ" -ge 8 ]] || fail "PHP 8.0+ requis (version actuelle : ${PHP_VER})"
ok "PHP ${PHP_VER} détecté (${PHP_BIN})"

# Extensions requises
MISSING_EXT=()
for ext in pdo pdo_sqlite curl dom libxml; do
  if php -r "exit(extension_loaded('${ext}') ? 0 : 1);" 2>/dev/null; then
    ok "Extension ${ext}"
  else
    MISSING_EXT+=("php${PHP_MAJ}.${PHP_MIN}-${ext}")
    warn "Extension manquante : ${ext}"
  fi
done

if [[ ${#MISSING_EXT[@]} -gt 0 ]]; then
  echo ""
  warn "Extensions manquantes. Installez-les avec :"
  echo -e "   ${YELLOW}sudo apt install ${MISSING_EXT[*]}${NC}"
  fail "Installez les extensions manquantes puis relancez install.sh"
fi

# ── 2. Vérification de la config ────────────────────────────────────────────
echo ""
echo -e "${BOLD}[2/5] Vérification de config.php${NC}"

CONFIG="${SCRIPT_DIR}/config.php"
[[ -f "$CONFIG" ]] || fail "config.php introuvable — copiez config.example.php vers config.php dans ${SCRIPT_DIR}"

# Vérifier que le mot de passe a été changé
if grep -q "changeme_ici" "$CONFIG"; then
  warn "APP_PASSWORD est encore 'changeme_ici' — pensez à le modifier !"
else
  ok "APP_PASSWORD personnalisé"
fi

# Vérifier la clé Brevo
if php -r "require_once '${CONFIG}'; exit(trim(BREVO_API_KEY) !== '' && BREVO_API_KEY !== 'xkeysib-REMPLACER' ? 0 : 1);" 2>/dev/null; then
  ok "BREVO_API_KEY configurée"
else
  warn "BREVO_API_KEY absente ou encore xkeysib-REMPLACER — éditez config.php (voir config.example.php)"
fi

# ── 3. Création de la base SQLite ────────────────────────────────────────────
echo ""
echo -e "${BOLD}[3/5] Initialisation de la base SQLite${NC}"

DB_RESULT=$(php -r "
  require_once '${SCRIPT_DIR}/config.php';
  require_once '${SCRIPT_DIR}/src/Database.php';
  try {
    Database::init();
    echo 'OK:' . DB_PATH;
  } catch (Exception \$e) {
    echo 'ERR:' . \$e->getMessage();
  }
" 2>&1)

if [[ "$DB_RESULT" == OK:* ]]; then
  DB_FILE="${DB_RESULT#OK:}"
  ok "Base SQLite créée : ${DB_FILE}"
  chmod 664 "$DB_FILE" 2>/dev/null || true
else
  fail "Impossible de créer la base SQLite : ${DB_RESULT#ERR:}"
fi

# ── 4. Permissions ───────────────────────────────────────────────────────────
echo ""
echo -e "${BOLD}[4/5] Permissions${NC}"

chmod 644 "${SCRIPT_DIR}/index.php" \
           "${SCRIPT_DIR}/ajax.php"  \
           "${SCRIPT_DIR}/config.php" 2>/dev/null || true
chmod 755 "${SCRIPT_DIR}/run.php"   2>/dev/null || true
ok "Permissions appliquées"

# Créer le fichier de log cron
touch "${SCRIPT_DIR}/cron.log" 2>/dev/null || true
chmod 664 "${SCRIPT_DIR}/cron.log" 2>/dev/null || true
ok "Fichier cron.log créé"

# ── 5. Configuration du cron ──────────────────────────────────────────────────
echo ""
echo -e "${BOLD}[5/5] Configuration du cron${NC}"

# 5h00 heure Guadeloupe = 9h00 UTC
CRON_CMD="php ${SCRIPT_DIR}/run.php >> ${SCRIPT_DIR}/cron.log 2>&1"
CRON_LINE="0 9 * * * ${CRON_CMD}"

# Vérifier si le cron existe déjà
if crontab -l 2>/dev/null | grep -qF "${SCRIPT_DIR}/run.php"; then
  ok "Cron déjà configuré"
else
  echo ""
  echo -e "   Ligne cron à ajouter (5h00 heure Guadeloupe = 9h00 UTC) :"
  echo -e "   ${CYAN}${CRON_LINE}${NC}"
  echo ""
  read -r -p "   Ajouter automatiquement au crontab ? [o/N] " REPLY
  echo ""
  if [[ "$REPLY" =~ ^[Oo]$ ]]; then
    (crontab -l 2>/dev/null; echo "$CRON_LINE") | crontab -
    ok "Cron ajouté"
    info "Vérifiez avec : crontab -l"
  else
    warn "Cron non ajouté — ajoutez manuellement : crontab -e"
    echo "   Copiez cette ligne :"
    echo -e "   ${CYAN}${CRON_LINE}${NC}"
  fi
fi

# ── Résumé ───────────────────────────────────────────────────────────────────
echo ""
echo "══════════════════════════════════════════════"
echo -e "${GREEN}${BOLD}✅ Installation terminée !${NC}"
echo ""
echo -e "${BOLD}Prochaines étapes :${NC}"
echo ""
echo "  1. ${CYAN}cp config.example.php config.php${NC} puis éditez ${CYAN}config.php${NC} (mot de passe, clé Brevo, e-mails)"
echo ""
echo "  2. Accédez à l'interface :"
echo -e "       ${CYAN}https://edsi.fr/tires/${NC}"
echo ""
echo "  3. Ajoutez vos dimensions et marques dans l'onglet 'Gérer les listes'"
echo ""
echo "  4. Test du scraping :"
echo -e "       ${CYAN}php ${SCRIPT_DIR}/run.php${NC}"
echo ""
echo "  5. Test email seul :"
echo -e "       ${CYAN}php -r \"require 'config.php'; require 'src/Mailer.php'; (new Mailer(BREVO_API_KEY))->sendAlert('Test OK');\"${NC}"
echo ""
