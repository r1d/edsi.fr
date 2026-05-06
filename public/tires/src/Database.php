<?php
/**
 * Tire Tracker — Accès SQLite
 * Toutes les opérations CRUD sur les deux tables de configuration.
 */
class Database
{
    private static ?PDO $pdo = null;

    // ── Connexion singleton ────────────────────────────────────────────────

    public static function get(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = new PDO('sqlite:' . DB_PATH);
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
            self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            self::$pdo->exec('PRAGMA journal_mode=WAL; PRAGMA foreign_keys=ON;');
        }
        return self::$pdo;
    }

    // ── Initialisation du schéma ───────────────────────────────────────────

    public static function init(): void
    {
        self::get()->exec("
            CREATE TABLE IF NOT EXISTS dimensions (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                largeur    INTEGER NOT NULL,
                hauteur    INTEGER NOT NULL,
                diametre   INTEGER NOT NULL,
                active     INTEGER NOT NULL DEFAULT 1,
                created_at TEXT    NOT NULL DEFAULT (datetime('now')),
                UNIQUE(largeur, hauteur, diametre)
            );

            CREATE TABLE IF NOT EXISTS dimensions_marques (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                largeur    INTEGER NOT NULL,
                hauteur    INTEGER NOT NULL,
                diametre   INTEGER NOT NULL,
                marque     TEXT    NOT NULL,
                active     INTEGER NOT NULL DEFAULT 1,
                created_at TEXT    NOT NULL DEFAULT (datetime('now')),
                UNIQUE(largeur, hauteur, diametre, marque)
            );
        ");
    }

    // ── Tableau 1 : dimensions (toutes marques) ────────────────────────────

    public static function getDimensions(bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM dimensions'
             . ($activeOnly ? ' WHERE active=1' : '')
             . ' ORDER BY diametre, largeur, hauteur';
        return self::get()->query($sql)->fetchAll();
    }

    public static function addDimension(int $largeur, int $hauteur, int $diametre): bool
    {
        try {
            self::get()
                ->prepare('INSERT INTO dimensions (largeur,hauteur,diametre) VALUES (?,?,?)')
                ->execute([$largeur, $hauteur, $diametre]);
            return true;
        } catch (PDOException) {
            return false; // contrainte UNIQUE
        }
    }

    public static function toggleDimension(int $id): void
    {
        self::get()
            ->prepare('UPDATE dimensions SET active = 1 - active WHERE id = ?')
            ->execute([$id]);
    }

    public static function deleteDimension(int $id): void
    {
        self::get()
            ->prepare('DELETE FROM dimensions WHERE id = ?')
            ->execute([$id]);
    }

    // ── Tableau 2 : dimensions + marques ──────────────────────────────────

    public static function getDimensionsMarques(bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM dimensions_marques'
             . ($activeOnly ? ' WHERE active=1' : '')
             . ' ORDER BY marque, diametre, largeur, hauteur';
        return self::get()->query($sql)->fetchAll();
    }

    public static function addDimensionMarque(
        int    $largeur,
        int    $hauteur,
        int    $diametre,
        string $marque
    ): bool {
        try {
            self::get()
                ->prepare('INSERT INTO dimensions_marques (largeur,hauteur,diametre,marque) VALUES (?,?,?,?)')
                ->execute([$largeur, $hauteur, $diametre, strtoupper(trim($marque))]);
            return true;
        } catch (PDOException) {
            return false;
        }
    }

    public static function toggleDimensionMarque(int $id): void
    {
        self::get()
            ->prepare('UPDATE dimensions_marques SET active = 1 - active WHERE id = ?')
            ->execute([$id]);
    }

    public static function deleteDimensionMarque(int $id): void
    {
        self::get()
            ->prepare('DELETE FROM dimensions_marques WHERE id = ?')
            ->execute([$id]);
    }
}
